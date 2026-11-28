<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SkpTendik, SkpTendikDetail, PenilaianSkpTendik, SkpDosen, PeriodeSkp, PenilaianSkpDosen, SkpHeader, User, SkpDosenPerkuliahan, SkpDosenSeminar, SkpDosenPkl, SkpDosenBimbing, SkpDosenPenguji, SkpDosenKemahasiswaan, SkpDosenBahanKuliah, SkpDosenKaryaIlmiah, SkpDosenAbdimas, SkpDosenJabatan, SkpDosenPenunjang, SkpTugasTambahan};
use Illuminate\Support\Facades\{Auth, DB};

class UserSkpController extends Controller
{
    // =========================================================================
    // HELPER: Cek Deadline Pengisian
    // =========================================================================
    private function pastikanPengisianBuka($periodeId)
    {
        $periode = PeriodeSkp::find($periodeId);
        $user = Auth::user();

        if (!$periode || !$periode->isPengisianTerbuka()) {
            abort(403, 'Waktu pengisian SKP untuk periode ini telah ditutup atau periode tidak aktif.');
        }

        $header = SkpHeader::where('periode_skp_id', $periodeId)
                            ->where('user_id', $user->id)
                            ->first();

        if (!$header) {
            abort(403, 'Data SKP tidak ditemukan.');
        }

        // ✅ VALIDASI 3 LEVEL APPROVAL TARGET
        $allActivitiesApproved = !\App\Models\MasterSkpTendik::where('user_id', $user->id)
            ->where('status_approval', '!=', 'approved')
            ->exists();

        if (!$header->is_target_approved_penilai || !$header->is_target_approved_atasan || !$allActivitiesApproved) {
            abort(403, 'Anda belum bisa mengisi realisasi. Target SKP Anda masih memerlukan persetujuan dari Pejabat Penilai, Atasan Pejabat Penilai, atau Penilai Kegiatan.');
        }

        if (in_array($header->status, ['Review Akhir', 'Selesai'])) {
            abort(403, 'Anda tidak bisa lagi mengubah realisasi karena SKP sedang dalam review pimpinan atau sudah selesai.');
        }
    }

    // =========================================================================
    // HELPER: Cari Calon Penilai
    // =========================================================================
    private function getCalonPenilai($user)
    {
        $query = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Wadek 1', 'Wadek 2'])
              ->orWhere('name', 'LIKE', 'Kaprodi%');
        });

        if (!$user->hasRole('Dekan')) {
            $query->where('id', '!=', $user->id);
        }

        return $query->with('profile', 'roles')
                     ->get()
                     ->sortBy('name');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->getRoleNames();
        if ($roles->isEmpty()) return redirect()->route('dashboard')->with('error', 'Akun belum memiliki hak akses.');

        // Ambil semua header SKP milik pegawai ini
        $headers = SkpHeader::with(['periode', 'pejabatPenilai.profile'])
                            ->where('user_id', $user->id)
                            ->latest()
                            ->get();

        return view('user.skp-tendik.index', compact('headers'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $skpHeader = SkpHeader::with(['perilakuIndicators', 'pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'periode'])
                              ->findOrFail($id);

        if ($skpHeader->user_id !== $user->id) abort(403);

        $atasan = $skpHeader->atasanPejabatPenilai;
        $penilai = $skpHeader->pejabatPenilai;
        $periodeId = $skpHeader->periode_skp_id;
        $periodeTerpilih = $skpHeader->periode;
        $isPengisianTerbuka = $periodeTerpilih ? $periodeTerpilih->isPengisianTerbuka() : false;
        
        $calonPenilai = [];
        if (!$skpHeader->pejabat_penilai_id) $calonPenilai = $this->getCalonPenilai($user);

        // Ambil data Penilaian Perilaku secara manual untuk menghindari eror relasi
        $penilaianPerilaku = \App\Models\PenilaianPerilaku::where('user_id', $user->id)
                                ->where('periode_skp_id', $periodeId)
                                ->first();

        $skpSaya = SkpTendik::where('skp_header_id', $skpHeader->id)
            ->with(['details', 'penilaian' => fn($q) => $q->where('user_id', $user->id)])
            ->get();

        $tugasTambahan = SkpTugasTambahan::where('skp_header_id', $skpHeader->id)->get();

        $semuaPenilaian = collect();
        $totalTarget = $skpSaya->count();
        $totalRealisasi = 0;

        foreach($skpSaya as $induk) {
            if($induk->penilaian && $induk->penilaian->nilai_capaian > 0) {
                $totalRealisasi++;
                $semuaPenilaian->push($induk->penilaian);
            }
        }

        $isLengkap = ($totalTarget > 0 && $totalTarget === $totalRealisasi);

        $rataUtama = 0;
        if ($semuaPenilaian->count() > 0) {
            $rataUtama = $semuaPenilaian->avg('nilai_capaian');
        }
        $totalTambahan = $tugasTambahan->sum('penghitungan') ?? 0;
        $nilaiAkhirSkp = $rataUtama + $totalTambahan;

        if ($isLengkap) {
            if ($nilaiAkhirSkp > 90) $predikat = 'Sangat Baik';
            elseif ($nilaiAkhirSkp >= 76) $predikat = 'Baik';
            elseif ($nilaiAkhirSkp >= 61) $predikat = 'Cukup';
            elseif ($nilaiAkhirSkp >= 51) $predikat = 'Kurang';
            else $predikat = 'Sangat Kurang';
        } else {
            $predikat = 'Belum Lengkap';
        }

        // ✅ CEK STATUS APPROVAL TARGET (3 LEVEL)
        $allActivitiesApproved = !\App\Models\MasterSkpTendik::where('skp_header_id', $skpHeader->id)
            ->where('status_approval', '!=', 'approved')
            ->exists();

        $isRealizable = $skpHeader->is_target_approved_penilai && 
                        $skpHeader->is_target_approved_atasan && 
                        $allActivitiesApproved;

        $approvalStatus = [
            'level1' => $skpHeader->is_target_approved_atasan,
            'level2' => $skpHeader->is_target_approved_penilai,
            'level3' => $allActivitiesApproved,
        ];

        // Ambil Master Activities spesifik header ini
        $masterActivities = \App\Models\MasterSkpTendik::where('skp_header_id', $skpHeader->id)->get();

        // ✅ Data untuk editing Master (Tabel 1)
        // HANYA JABATAN PEGAWAI YANG DINILAI
        $bidangList = collect($user->getRoleNames())->unique()->sort()->values();

        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
            ->where(function($q) use ($requestedRoles) {
                foreach($requestedRoles as $r) {
                    $q->orWhere('name', 'like', $r . '%');
                }
            })
            ->get();

        return view('user.skp-tendik.show', compact('user', 'skpSaya', 'skpHeader', 'calonPenilai', 'tugasTambahan', 'nilaiAkhirSkp', 'predikat', 'isPengisianTerbuka', 'isLengkap', 'penilaianPerilaku', 'isRealizable', 'approvalStatus', 'masterActivities', 'bidangList', 'penilaiRoles', 'periodeId', 'atasan', 'penilai'));
    }

    public function finalisasiTarget(Request $request, $header_id)
    {
        $header = SkpHeader::findOrFail($header_id);
        if ($header->user_id !== Auth::id()) abort(403);
        
        $header->update(['status' => 'Target Diajukan']);
        
        return back()->with('success', 'Tupoksi & Target SKP telah difinalisasi dan diajukan ke pimpinan untuk disetujui.');
    }

    public function storeActivity(Request $request)
    {
        $user = Auth::user();
        $periodeId = $request->periode_id;
        
        $header = SkpHeader::where('user_id', $user->id)->where('periode_skp_id', $periodeId)->first();
        if (!$header) return back()->with('error', 'Header SKP tidak ditemukan.');

        // Hanya boleh tambah jika belum disetujui pimpinan
        if ($header->is_target_approved_penilai && $header->is_target_approved_atasan) {
             return back()->with('error', 'Target sudah disetujui pimpinan, tidak dapat menambah data lagi.');
        }

        $request->validate([
            'role_name' => 'required|string',
            'tupoksi' => 'required|string',
            'jenis_aktivitas' => 'required|string',
            'penilai_role' => 'required|string',
            'periode' => 'required|string',
            'frekuensi_per_tahun' => 'required|numeric|min:1',
            'durasi_menit' => 'required|numeric|min:1',
            'jumlah_sdm' => 'required|numeric|min:1',
            'target_ak' => 'nullable|numeric|min:0',
            'target_kuantitas' => 'nullable|numeric|min:0',
            'target_kualitas' => 'nullable|numeric|min:0|max:100',
            'target_waktu' => 'nullable|numeric|min:0',
            'target_biaya' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan ke Master (Template)
            \App\Models\MasterSkpTendik::create([
                'user_id'             => $user->id,
                'skp_header_id'       => $header->id,
                'role_name'           => $request->role_name,
                'tupoksi'             => trim($request->tupoksi),
                'jenis_aktivitas'     => trim($request->jenis_aktivitas),
                'penilai_role'        => $request->penilai_role,
                'kategori'            => $request->kategori ?? ['Utama'],
                'periode'             => $request->periode,
                'frekuensi_per_tahun' => $request->frekuensi_per_tahun,
                'durasi_menit'        => $request->durasi_menit,
                'jumlah_sdm'          => $request->jumlah_sdm,
                'status_approval'     => 'pending',
            ]);

            // 2. Simpan ke Operasional (SkpTendik & Detail)
            $skpTendik = SkpTendik::firstOrCreate(
                ['user_id' => $user->id, 'periode_skp_id' => $periodeId, 'skp_header_id' => $header->id],
                ['role_name' => $user->getRoleNames()->first() ?? 'Tendik', 'tupoksi' => '-']
            );

            SkpTendikDetail::create([
                'skp_tendik_id'   => $skpTendik->id,
                'jenis_aktivitas' => trim($request->jenis_aktivitas),
                'ak'              => $request->target_ak ?? 0,
                'kuantitas_output'=> $request->target_kuantitas ?? 0,
                'kualitas_mutu'   => $request->target_kualitas ?? 0,
                'waktu'           => $request->target_waktu ?? 0,
                'biaya'           => $request->target_biaya ?? 0,
            ]);

            DB::commit();
            return back()->with('success', 'Kegiatan berhasil ditambahkan ke rencana SKP Anda.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    public function destroyActivity($id)
    {
        $activity = \App\Models\MasterSkpTendik::findOrFail($id);
        $user = Auth::user();
        if ($activity->user_id !== $user->id) abort(403);

        // Header milik aktivitas ini (bukan header "terbaru" milik user)
        $header = SkpHeader::find($activity->skp_header_id);
        
        // Hanya boleh hapus jika belum disetujui pimpinan
        if ($header && $header->is_target_approved_penilai && $header->is_target_approved_atasan) {
             return back()->with('error', 'Target sudah disetujui pimpinan, tidak dapat menghapus data lagi.');
        }

        DB::beginTransaction();
        try {
            if ($header) {
                \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($activity, $header) {
                    $q->where('user_id', $activity->user_id)
                      ->where('periode_skp_id', $header->periode_skp_id);
                })->where('jenis_aktivitas', $activity->jenis_aktivitas)->delete();

                SkpTendik::where('user_id', $activity->user_id)
                    ->where('periode_skp_id', $header->periode_skp_id)
                    ->whereDoesntHave('details')
                    ->delete();
            }

            $activity->delete();

            DB::commit();
            return back()->with('success', 'Kegiatan berhasil dihapus dari rencana SKP Anda.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();
        $periodeId = $request->periode_id;
        $headerId = $request->header_id;

        if ($headerId) {
            $header = SkpHeader::where('id', $headerId)->where('user_id', $user_id)->first();
        } else {
            $header = SkpHeader::where('user_id', $user_id)->where('periode_skp_id', $periodeId)->first();
        }
        
        if (!$header) abort(403, 'Data SKP tidak ditemukan.');
        
        $periodeId = $header->periode_skp_id; // Pastikan menggunakan ID periode dari header

        $periode = PeriodeSkp::find($periodeId);
        if (!$periode || $periode->status !== 'aktif') {
            abort(403, 'Periode SKP tidak aktif.');
        }

        $request->validate([
            'realisasi.*.bukti.*' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png|max:5120',
            'realisasi_tugas.*.file_bukti' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png|max:5120',
            'new_tugas.*.file_bukti' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan Realisasi & Update Target (jika ada)
            if ($request->has('realisasi')) {
                foreach ($request->realisasi as $induk_id => $data) {
                    $induk = SkpTendik::with('details')->find($induk_id);
                    if(!$induk) continue;

                    // ✅ Pastikan skp_tendik terhubung ke header yang benar jika belum
                    if (!$induk->skp_header_id) {
                        $induk->update(['skp_header_id' => $header->id]);
                    }

                    // ✅ UPDATE TARGET (AK, KUANT, KUAL, WAKTU, BIAYA)
                    $targetFields = [
                        'target_ak' => 'ak',
                        'target_kuantitas' => 'kuantitas_output',
                        'target_kualitas' => 'kualitas_mutu',
                        'target_waktu' => 'waktu',
                        'target_biaya' => 'biaya',
                    ];

                    $updateTarget = [];
                    foreach ($targetFields as $formField => $dbField) {
                        if (isset($data[$formField]) && $data[$formField] !== '') {
                            $updateTarget[$dbField] = $data[$formField];
                        }
                    }

                    if (!empty($updateTarget)) {
                        $firstDetail = $induk->details->first();
                        if ($firstDetail) {
                            $firstDetail->update($updateTarget);
                        } else {
                            $createData = array_merge([
                                'skp_tendik_id' => $induk->id,
                                'jenis_aktivitas' => '-',
                            ], $updateTarget);
                            SkpTendikDetail::create($createData);
                        }
                        $induk->load('details');
                    }

                    // HANYA PROSES REALISASI JIKA SUDAH DI-APPROVE (Bukan Draft/Target Diajukan)
                    if (!in_array($header->status, ['Draft', 'Target Diajukan'])) {
                        $targetAgregat = (object) [
                            'kuantitas_output' => $induk->details->sum('kuantitas_output'),
                            'kualitas_mutu'    => $induk->details->avg('kualitas_mutu') ?? 100,
                            'waktu'            => $induk->details->max('waktu') ?? 0,
                            'biaya'            => $induk->details->sum('biaya') ?? 0,
                        ];

                        $hasil = $this->hitungCapaianTendik($targetAgregat, $data);

                        // ✅ PROSES BUKTI (FILE UPLOAD)
                        $existingPenilaian = PenilaianSkpTendik::where('user_id', $user_id)->where('skp_tendik_id', $induk_id)->first();
                        $buktiPaths = $existingPenilaian->bukti ?? [];

                        if ($request->hasFile("realisasi.$induk_id.bukti")) {
                            $files = $request->file("realisasi.$induk_id.bukti");
                            // Append new files to existing ones instead of replacing
                            foreach ($files as $file) {
                                $path = $file->store("skp-tendik/bukti/$user_id", 'public');
                                $buktiPaths[] = $path;
                            }
                        }

                        // Re-calculate with manual quantity if provided
                        $kuantitasFinal = (int)($data['kuantitas'] ?? 0);
                        if ($kuantitasFinal <= 0 && count($buktiPaths) > 0) {
                            $kuantitasFinal = count($buktiPaths);
                        }

                        $data['kuantitas'] = $kuantitasFinal;
                        $hasil = $this->hitungCapaianTendik($targetAgregat, $data);

                        PenilaianSkpTendik::updateOrCreate(
                            ['user_id' => $user_id, 'skp_tendik_id' => $induk_id],
                            [
                                'realisasi_ak' => $data['ak_realisasi'] ?? 0,
                                'realisasi_kuantitas' => $kuantitasFinal,
                                'realisasi_kualitas' => $data['kualitas'] ?? 0,
                                'realisasi_waktu' => $data['waktu'] ?? 0,
                                'realisasi_biaya' => $data['biaya'] ?? 0,
                                'bukti' => $buktiPaths,
                                'nilai_penghitungan' => $hasil['penghitungan'],
                                'nilai_capaian' => $hasil['capaian']
                            ]
                        );
                    }
                }
            }

            // 2. Simpan Perilaku Kerja (jika ada)
            if ($request->has('perilaku')) {
                $perilakuData = $request->perilaku;

                // $header sudah ditemukan di awal
                if ($header) {
                    \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
                    $rekapAspek = [];
                    foreach ($perilakuData as $aspek => $items) {
                        // Filter out empty items: indikator harus ada isinya ATAU nilai > 0
                        $validItems = array_filter($items, function($item) {
                            return !empty(trim($item['indikator'] ?? '')) || !empty($item['nilai']);
                        });

                        $count = count($validItems);
                        
                        $totalBobot = 0;
                        foreach ($validItems as $item) {
                            $totalBobot += (float) ($item['bobot_persen'] ?? 0);
                        }

                        $totalNilai = 0;

                        foreach ($validItems as $item) {
                            $bobot = ($totalBobot > 0) ? (float) ($item['bobot_persen'] ?? 0) : (100 / ($count > 0 ? $count : 1));
                            
                            \App\Models\SkpPerilakuIndicator::create([
                                'skp_header_id' => $header->id,
                                'aspek' => $aspek,
                                'indikator' => $item['indikator'] ?? '',
                                'nilai' => $item['nilai'] ?? 0,
                                'bobot_persen' => $bobot,
                                'is_realized' => true,
                                'is_from_admin' => filter_var($item['is_from_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
                            ]);
                            $totalNilai += ((float) ($item['nilai'] ?? 0) * $bobot / 100);
                        }
                        $rekapAspek[$aspek] = $totalNilai;
                    }

                    $mapAspek = [
                        'Orientasi Pelayanan' => 'orientasi_pelayanan',
                        'Integritas' => 'integritas',
                        'Komitmen' => 'komitmen',
                        'Disiplin' => 'disiplin',
                        'Kerjasama' => 'kerjasama',
                        'Kepemimpinan' => 'kepemimpinan',
                    ];

                    $updateData = [];
                    foreach ($mapAspek as $label => $column) {
                        $updateData[$column] = $rekapAspek[$label] ?? 0;
                    }

                    \App\Models\PenilaianPerilaku::updateOrCreate(
                        ['user_id' => $user_id, 'periode_skp_id' => $periodeId],
                        $updateData
                    );
                }
            }

            // 3. Simpan Tugas Tambahan Baru (jika ada)
            if ($request->has('new_tugas')) {
                $jumlahSaatIni = SkpTugasTambahan::where('user_id', $user_id)->where('periode_skp_id', $periodeId)->count();
                foreach ($request->new_tugas as $tugasData) {
                    if (!empty($tugasData['kegiatan']) && $jumlahSaatIni < 7) {
                        $p_val = 0;
                        if (!empty($tugasData['realisasi_kegiatan'])) {
                            $p_val = $tugasData['ak_realisasi'] ?? $tugasData['ak'] ?? 1.00;
                        }

                        SkpTugasTambahan::create([
                            'user_id' => $user_id,
                            'skp_header_id' => $header->id,
                            'periode_skp_id' => $periodeId,
                            'jenis' => $tugasData['jenis'],
                            'kegiatan' => $tugasData['kegiatan'],
                            'ak' => $tugasData['ak'] ?? 1.00,
                            'ak_realisasi' => $tugasData['ak_realisasi'] ?? 1.00,
                            'realisasi_kegiatan' => $tugasData['realisasi_kegiatan'] ?? null,
                            'penghitungan' => $p_val,
                            'nilai_capaian' => $p_val
                        ]);
                        $jumlahSaatIni++;
                    }
                }
            }

            // 4. Simpan Update Tugas Tambahan Eksisting (jika ada)
            if ($request->has('realisasi_tugas')) {
                foreach ($request->realisasi_tugas as $tugas_id => $data) {
                    $tugas = SkpTugasTambahan::find($tugas_id);
                    if (!$tugas) continue;

                    $updateTugas = [
                        'ak_realisasi'       => $data['ak_realisasi'] ?? 1.00,
                        'realisasi_kegiatan' => $data['realisasi_kegiatan'] ?? null,
                    ];

                    // Hitung penghitungan & nilai_capaian
                    if (!empty($data['realisasi_kegiatan'])) {
                        $val = $data['ak_realisasi'] ?? $tugas->ak;
                        $updateTugas['penghitungan'] = $val;
                        $updateTugas['nilai_capaian'] = $val;
                    } else {
                        $updateTugas['penghitungan'] = 0;
                        $updateTugas['nilai_capaian'] = 0;
                    }

                    if ($request->hasFile("realisasi_tugas.$tugas_id.file_bukti")) {
                        $file = $request->file("realisasi_tugas.$tugas_id.file_bukti");
                        $path = $file->store("skp-tendik/tugas-tambahan/$user_id", 'public');
                        $updateTugas['realisasi_bukti'] = $path;
                    }

                    $tugas->update($updateTugas);
                }
            }

            // 5. Simpan Tupoksi & Operasional Baru (jika ada)
            if ($request->has('new_activities')) {
                foreach ($request->new_activities as $act) {
                    if (!empty($act['jenis_aktivitas'])) {
                        // a. Simpan ke Master (Template)
                        \App\Models\MasterSkpTendik::create([
                            'user_id'             => $user_id,
                            'skp_header_id'       => $header->id,
                            'role_name'           => $act['role_name'],
                            'tupoksi'             => trim($act['tupoksi'] ?? '-'),
                            'jenis_aktivitas'     => trim($act['jenis_aktivitas']),
                            'penilai_role'        => $act['penilai_role'],
                            'kategori'            => $act['kategori'] ?? ['Utama'],
                            'periode'             => $act['periode'],
                            'frekuensi_per_tahun' => $act['frekuensi_per_tahun'] ?? 1,
                            'durasi_menit'        => $act['durasi_menit'] ?? 60,
                            'jumlah_sdm'          => $act['jumlah_sdm'] ?? 1,
                            'status_approval'     => 'pending',
                        ]);

                        // b. Simpan ke Operasional (SkpTendik & Detail)
                        $tupoksiName = trim($act['tupoksi'] ?? '-');
                        if ($tupoksiName === '') $tupoksiName = '-';

                        $skpTendik = SkpTendik::firstOrCreate(
                            ['user_id' => $user_id, 'periode_skp_id' => $periodeId, 'skp_header_id' => $header->id, 'tupoksi' => $tupoksiName],
                            ['role_name' => $act['role_name'] ?? (Auth::user()->getRoleNames()->first() ?? 'Tendik')]
                        );

                        SkpTendikDetail::create([
                            'skp_tendik_id'   => $skpTendik->id,
                            'jenis_aktivitas' => trim($act['jenis_aktivitas']),
                            'ak'              => (isset($act['target_ak']) && $act['target_ak'] !== '') ? (float)$act['target_ak'] : 0,
                            'kuantitas_output'=> (isset($act['target_kuantitas']) && $act['target_kuantitas'] !== '') ? (int)$act['target_kuantitas'] : 0,
                            'kualitas_mutu'   => (isset($act['target_kualitas']) && $act['target_kualitas'] !== '') ? (float)$act['target_kualitas'] : 100,
                            'waktu'           => (isset($act['target_waktu']) && $act['target_waktu'] !== '') ? (int)$act['target_waktu'] : 12,
                            'biaya'           => (isset($act['target_biaya']) && $act['target_biaya'] !== '') ? (float)$act['target_biaya'] : 0,
                        ]);
                    }
                }
            }

            // 5. Finalisasi (jika diminta)
            if ($request->is_finalisasi == '1') {
                if ($header) {
                    $header->update(['status' => 'Target Diajukan']);
                }
            }

            DB::commit();
            
            $msg = ($request->is_finalisasi == '1') 
                ? 'Data berhasil disimpan dan SKP telah difinalisasi!' 
                : 'Data capaian SKP dan Perilaku berhasil disimpan!';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function hitungCapaianTendik($target, $realisasi)
    {
        $targetKuantitas = (float) ($target->kuantitas_output > 0 ? $target->kuantitas_output : 1);
        $realKuantitas = (float) ($realisasi['kuantitas'] ?? 0);

        $targetKualitas = (float) ($target->kualitas_mutu > 0 ? $target->kualitas_mutu : 100);
        $realKualitas = (float) ($realisasi['kualitas'] ?? 0);

        $targetWaktu = (float) ($target->waktu > 0 ? $target->waktu : 1);
        $realWaktu = (float) ($realisasi['waktu'] ?? 0);

        $targetBiaya = (float) ($target->biaya > 0 ? $target->biaya : 0);
        $realBiaya = (float) ($realisasi['biaya'] ?? 0);

        $kuantitas = ($realKuantitas / $targetKuantitas) * 100;
        $kualitas  = ($realKualitas / $targetKualitas) * 100;

        $efisiensiWaktu = 100 - (($realWaktu / $targetWaktu) * 100);
        $nilaiWaktu = ($efisiensiWaktu <= 24)
            ? ((1.76 * $targetWaktu - $realWaktu) / $targetWaktu) * 100
            : 76 - ((((1.76 * $targetWaktu - $realWaktu) / $targetWaktu) * 100) - 100);

        $nilaiBiaya = 0;
        $pembagi = 3;

        if ($targetBiaya > 0) {
            $efisiensiBiaya = 100 - (($realBiaya / $targetBiaya) * 100);
            $nilaiBiaya = ($efisiensiBiaya <= 24)
                ? ((1.76 * $targetBiaya - $realBiaya) / $targetBiaya) * 100
                : 76 - ((((1.76 * $targetBiaya - $realBiaya) / $targetBiaya) * 100) - 100);

            $totalCapaian = $kuantitas + $kualitas + $nilaiWaktu + $nilaiBiaya;
            $pembagi = 4;
        } else {
            $totalCapaian = $kuantitas + $kualitas + $nilaiWaktu;
        }

        $capaian = $totalCapaian / $pembagi;

        // ✅ Batasi nilai capaian maksimal 92 jika realisasi melebihi target (sesuai permintaan user)
        if ($capaian > 92) {
            $capaian = 92;
        }

        return [
            'penghitungan' => round($totalCapaian, 2),
            'capaian'      => round($capaian, 2)
        ];
    }

    public function indexDosen(Request $request)
    {
        $user = Auth::user();

        $isDosen = $user->getRoleNames()->filter(function($role) {
            return str_contains($role, 'Dosen') ||
                   str_contains($role, 'Kaprodi') ||
                   str_contains($role, 'Sekprodi') ||
                   str_contains($role, 'Kepala Lab') ||
                   str_contains($role, 'Dekan') ||
                   str_contains($role, 'Wadek') ||
                   str_contains($role, 'Kepala UPF');
        })->isNotEmpty();

        if (!$isDosen) {
            return redirect()->route('dashboard')->with('error', 'Akses Ditolak: Menu ini khusus untuk Dosen dan Pejabat Akademik.');
        }

        // Ambil semua header SKP milik dosen ini
        $headers = SkpHeader::with(['periode', 'pejabatPenilai.profile'])
                            ->where('user_id', $user->id)
                            ->whereHas('skpDosens')
                            ->latest()
                            ->get();

        return view('user.skp-dosen.index', compact('headers'));
    }

    public function showDosen($id)
    {
        $user = Auth::user();
        $skpHeader = SkpHeader::with(['perilakuIndicators', 'pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'periode'])
                              ->findOrFail($id);

        if ($skpHeader->user_id !== $user->id) abort(403);

        $periodeId = $skpHeader->periode_skp_id;
        $periode = PeriodeSkp::orderBy('mulai', 'desc')->get();
        $periodeTerpilih = $skpHeader->periode;
        $isPengisianTerbuka = $periodeTerpilih ? $periodeTerpilih->isPengisianTerbuka() : false;

        $calonPenilai = [];
        if (!$skpHeader->pejabat_penilai_id) $calonPenilai = $this->getCalonPenilai($user);

        // Ambil data Penilaian Perilaku secara manual untuk menghindari eror relasi
        $penilaianPerilaku = \App\Models\PenilaianPerilaku::where('user_id', $user->id)
                                ->where('periode_skp_id', $periodeId)
                                ->first();

        $skpDosen = SkpDosen::where('skp_header_id', $skpHeader->id)
            ->with(['penilaian' => fn($q) => $q->where('user_id', $user->id)])
            ->get();

        $tugasTambahan = SkpTugasTambahan::where('skp_header_id', $skpHeader->id)->get();

        $totalTarget = $skpDosen->count();
        $totalRealisasi = $skpDosen->filter(fn($item) => $item->penilaian !== null)->count();
        $isLengkap = ($totalTarget > 0 && $totalTarget === $totalRealisasi);

        $rataUtama = 0;
        $dinilai = $skpDosen->filter(fn($item) => $item->penilaian !== null);
        if ($dinilai->count() > 0) {
            $rataUtama = $dinilai->avg('penilaian.nilai_capaian');
        }
        $totalTambahan = $tugasTambahan->sum('penghitungan') ?? 0;
        $nilaiAkhirSkp = $rataUtama + $totalTambahan;

        if ($isLengkap) {
            if ($nilaiAkhirSkp > 90) $predikat = 'Sangat Baik';
            elseif ($nilaiAkhirSkp >= 76) $predikat = 'Baik';
            elseif ($nilaiAkhirSkp >= 61) $predikat = 'Cukup';
            elseif ($nilaiAkhirSkp >= 51) $predikat = 'Kurang';
            else $predikat = 'Sangat Kurang';
        } else {
            $predikat = 'Belum Lengkap';
        }

        // Ambil Master Activities spesifik header ini
        $activities = \App\Models\MasterSkpTendik::where('skp_header_id', $skpHeader->id)->get();

        // ✅ Data untuk editing Master (Tabel 1)
        $roleBidang = \Spatie\Permission\Models\Role::whereIn('kategori', ['utama', 'tendik'])->pluck('name')->toArray();
        $kamusBidang = \App\Models\DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))->unique()->sort()->values();

        $userJabatan = $user->userProfile->jabatan_pekerjaan ?? '';

        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
            ->where(function($q) use ($requestedRoles) {
                foreach($requestedRoles as $r) {
                    $q->orWhere('name', 'like', $r . '%');
                }
            })
            ->get();

        // ✅ Filter bidangs to only those the user actually has
        $userRoles = $user->getRoleNames()->toArray();
        $userBidangs = collect($bidangList)->filter(function($b) use ($userRoles) {
            if ($b === 'Dosen') {
                foreach ($userRoles as $role) {
                    if (str_starts_with(strtolower($role), 'dosen')) return true;
                }
            }
            return in_array($b, $userRoles);
        })->values();

        $userJabatan = $userBidangs->contains('Dosen') ? 'Dosen' : ($userBidangs->first() ?? '');
        if ($userBidangs->isEmpty()) $userBidangs = $bidangList; // Fallback if no specific role matches

        $masterBahanKuliah = [
            'Buku Ajar' => 20, 'Diktat' => 5, 'Modul' => 5, 'Petunjuk Praktikum' => 5,
            'Model' => 5, 'Alat Bantu' => 5, 'Audio Visual' => 5, 'Naskah Tutorial' => 5,
        ];

        $masterKaryaIlmiah = [
            'Monograf' => 20, 'Buku referensi' => 40, 'Artikel di jurnal ilmiah internasional' => 40,
            'Artikel di jurnal ilmiah nasional terakreditasi' => 25, 'Artikel di jurnal ilmiah nasional' => 10,
            'Makalah disajikan di seminar internasional' => 15, 'Makalah disajikan di seminar nasional' => 10,
            'Poster internasional' => 10, 'Poster nasional' => 5, 'Artikel di majalah/koran' => 1,
            'Hasil Penelitian / hasil pemikiran yg tidak dipublikasikan' => 2,
            'Menerjemahkan/menyadur buku ilmiah Nasional' => 15, 'Mengedit/menyunting karya ilmiah Nasional' => 10,
            'Membuat rencana dan karya teknologi tingkat Internasional' => 60,
            'Membuat rencana dan karya teknologi tingkat Nasional' => 40,
            'Karya seni monumental/karya sastra Tk. Internasional' => 20,
            'Karya seni monumental/karya sastra Tk. Nasional' => 15,
            'Karya seni monumental/karya sastra Tk. Lokal' => 10,
        ];

        $masterAbdimas = [
            'A' => ['title' => 'Abdimas > 6 bln', 'items' => ['Memberikan pelatihan masyarakat Tingkat Internasional' => 4, 'Memberikan pelatihan masyarakat Tingkat Nasional' => 3, 'Memberikan pelatihan masyarakat Tingkat Lokal' => 2]],
            'B' => ['title' => 'Abdimas < 6 bln', 'items' => ['Memberikan pelatihan masyarakat Tingkat Internasional' => 3, 'Memberikan pelatihan masyarakat Tingkat Nasional' => 2, 'Memberikan pelatihan masyarakat Tingkat Lokal' => 1, 'Insidental' => 1]],
            'C' => ['title' => 'Abdimas 1 thn', 'items' => ['Menduduki jabatan pimpinan lembaga pemerintahan' => 5.5, 'Mengembangkan hasil penelitian' => 3, 'Memberi pelayanan berdasarkan keahlian' => 1.5, 'Memberi pelayanan penugasan lembaga PT' => 1, 'Memberi pelayanan berdasarkan fungsi/jabatan' => 0.5, 'Membuat karya pengabdian tidak dipublikasikan' => 3]],
        ];

        $masterJabatan = $this->getMasterJabatan();

        return view('user.skp-dosen.show', compact('skpDosen', 'periode', 'periodeId', 'skpHeader', 'calonPenilai', 'tugasTambahan', 'nilaiAkhirSkp', 'predikat', 'isPengisianTerbuka', 'isLengkap', 'penilaianPerilaku', 'activities', 'masterBahanKuliah', 'masterKaryaIlmiah', 'masterAbdimas', 'masterJabatan', 'bidangList', 'penilaiRoles', 'userBidangs', 'userJabatan'));
    }

    public function storeDosen(Request $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;

        DB::beginTransaction();
        try {
            $headerId = $request->skp_header_id;
            $header = SkpHeader::findOrFail($headerId);

            // Cek Otoritas: Pengguna adalah pemilik SKP atau Pimpinan (Atasan/Penilai/Reviewer)
            $isOwner = ($header->user_id == $authUser->id);

            // Cek apakah authUser adalah penilai kegiatan (Level 3)
            $isLevel3 = \App\Models\MasterSkpTendik::where('user_id', $header->user_id)
                        ->where('penilai_role', '!=', null)
                        ->get()
                        ->filter(fn($act) => $authUser->hasRole($act->penilai_role))
                        ->isNotEmpty();

            $isPimpinan = ($header->pejabat_penilai_id == $authUser->id || 
                           $header->atasan_pejabat_penilai_id == $authUser->id ||
                           $isLevel3 ||
                           $authUser->hasRole('Super Admin'));

            if (!$isOwner && !$isPimpinan) {
                return back()->with('error', 'Anda tidak memiliki otoritas untuk mengedit data ini.');
            }

            // Gunakan ID pemilik SKP untuk updateOrCreate ke tabel terkait
            $target_user_id = $header->user_id;

            // 1. SIMPAN REALISASI SKP (Tabel Utama)
            if ($request->has('realisasi')) {
                foreach ($request->realisasi as $skp_id => $data) {
                    $target = SkpDosen::find($skp_id);
                    if (!$target) continue;

                    // Update Target jika dikirim (Editing Target)
                    if (isset($data['target_kuantitas'])) {
                        $target->update([
                            'kuantitas_output' => $data['target_kuantitas'],
                            'kualitas_mutu'    => $data['target_kualitas'] ?? 100,
                            'waktu'            => $data['target_waktu'] ?? 12,
                            'biaya'            => $data['target_biaya'] ?? 0,
                            'ak'               => $data['target_ak'] ?? 0,
                        ]);
                    }

                    $realisasiData = [
                        'kuantitas' => $data['kuantitas'] ?? 0,
                        'kualitas'  => $data['kualitas'] ?? 0,
                        'waktu'     => $data['waktu'] ?? 0,
                        'biaya'     => $data['biaya'] ?? 0,
                    ];

                    $hasil = $this->hitungCapaianLogikaBaru($target, $realisasiData);

                    PenilaianSkpDosen::updateOrCreate(
                        ['user_id' => $target_user_id, 'skp_dosen_id' => $skp_id],
                        [
                            'realisasi_ak'        => $data['ak'] ?? 0,
                            'realisasi_kuantitas' => $realisasiData['kuantitas'],
                            'realisasi_kualitas'  => $realisasiData['kualitas'],
                            'realisasi_waktu'     => $realisasiData['waktu'],
                            'realisasi_biaya'     => $realisasiData['biaya'],
                            'nilai_penghitungan'  => $hasil['penghitungan'],
                            'nilai_capaian'       => $hasil['capaian']
                        ]
                    );
                }
            }

            // 2. SIMPAN PERILAKU KERJA
            if ($request->has('perilaku')) {
                $submittedIds = [];
                $rekapAspek = [];
                foreach ($request->perilaku as $aspek => $items) {
                    $validItems = array_filter($items, fn($i) => !empty(trim($i['indikator'] ?? '')) || (isset($i['nilai']) && $i['nilai'] !== ''));
                    $count = count($validItems);
                    
                    $totalBobot = 0;
                    foreach ($validItems as $item) {
                        $totalBobot += (float) ($item['bobot_persen'] ?? 0);
                    }

                    $totalNilai = 0;

                    foreach ($validItems as $item) {
                        $bobot = ($totalBobot > 0) ? (float) ($item['bobot_persen'] ?? 0) : (($count > 0) ? (100 / $count) : 0);
                        
                        $indicator = $header->perilakuIndicators()->updateOrCreate(
                            ['id' => $item['id'] ?? null],
                            [
                                'aspek'         => $aspek,
                                'indikator'     => trim($item['indikator'] ?? ''),
                                'nilai'         => $item['nilai'] ?? 0,
                                'bobot_persen'  => $bobot,
                                'is_realized'   => true,
                                'is_from_admin' => filter_var($item['is_from_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
                            ]
                        );
                        $submittedIds[] = $indicator->id;
                        $totalNilai += ((float) ($item['nilai'] ?? 0) * $bobot / 100);
                    }
                    $rekapAspek[$aspek] = $totalNilai;
                }
                // Hapus indikator yang tidak ada dalam draf terbaru
                $header->perilakuIndicators()->whereNotIn('id', $submittedIds)->delete();

                $mapAspek = [
                    'Orientasi Pelayanan' => 'orientasi_pelayanan', 'Integritas' => 'integritas',
                    'Komitmen' => 'komitmen', 'Disiplin' => 'disiplin',
                    'Kerjasama' => 'kerjasama', 'Kepemimpinan' => 'kepemimpinan'
                ];
                $updatePerilaku = [];
                foreach ($mapAspek as $label => $column) { $updatePerilaku[$column] = $rekapAspek[$label] ?? 0; }
                \App\Models\PenilaianPerilaku::updateOrCreate(
                    ['user_id' => $target_user_id, 'periode_skp_id' => $header->periode_skp_id],
                    $updatePerilaku
                );
            }

            // 3. SIMPAN UPDATE TUPOKSI (Tabel 1)
            if ($request->has('tupoksi_updates')) {
                foreach ($request->tupoksi_updates as $act_id => $data) {
                    $activity = \App\Models\MasterSkpTendik::find($act_id);
                    if ($activity && ($activity->user_id == $target_user_id || $authUser->hasRole('Super Admin'))) {
                        $activity->update([
                            'role_name'           => $data['role_name'] ?? $activity->role_name,
                            'tupoksi'             => $data['tupoksi'] ?? $activity->tupoksi,
                            'jenis_aktivitas'     => $data['jenis_aktivitas'] ?? $activity->jenis_aktivitas,
                            'periode'             => $data['periode'] ?? $activity->periode,
                            'frekuensi_per_tahun' => $data['freq'] ?? $activity->frekuensi_per_tahun,
                            'durasi_menit'        => $data['dur'] ?? $activity->durasi_menit,
                            'jumlah_sdm'          => $data['sdm'] ?? $activity->jumlah_sdm,
                        ]);
                    }
                }
            }

            // 3.1 SIMPAN TUPOKSI BARU (Tabel 1 - New Rows)
            if ($request->has('tupoksi_new')) {
                foreach ($request->tupoksi_new as $data) {
                    if (empty($data['role_name']) || empty($data['tupoksi'])) continue;

                    // A. Simpan ke Master (Rincian)
                    \App\Models\MasterSkpTendik::create([
                        'user_id'             => $target_user_id,
                        'role_name'           => $data['role_name'],
                        'tupoksi'             => trim($data['tupoksi']),
                        'jenis_aktivitas'     => trim($data['jenis_aktivitas'] ?? '-'),
                        'penilai_role'        => $data['penilai_role'] ?? 'Dekan',
                        'kategori'            => $data['kategori'] ?? ['Utama'],
                        'periode'             => $data['periode'] ?? 'Tahunan',
                        'frekuensi_per_tahun' => $data['freq'] ?? 1,
                        'durasi_menit'        => $data['dur'] ?? 0,
                        'jumlah_sdm'          => $data['sdm'] ?? 1,
                        'status_approval'     => 'pending',
                        'skp_header_id'       => $header ? $header->id : null,
                    ]);

                    // B. Simpan ke Operasional (Tugas Tambahan) jika ini SKP Dosen
                    if ($header) {
                        \App\Models\SkpTugasTambahan::updateOrCreate(
                            [
                                'user_id'         => $target_user_id,
                                'periode_skp_id'  => $header->periode_skp_id,
                                'skp_header_id'   => $header->id,
                                'kegiatan'        => trim($data['tupoksi']),
                            ],
                            [
                                'jenis'           => 'Tugas Tambahan', // Explicitly set required enum field
                                'ak'              => 1, // Default AK 1 as requested
                            ]
                        );
                    }
                }
            }

            // 4. SIMPAN REALISASI TUGAS TAMBAHAN
            if ($request->has('tugas_tambahan_realisasi')) {
                foreach ($request->tugas_tambahan_realisasi as $tambahan_id => $data) {
                    $tambahan = SkpTugasTambahan::find($tambahan_id);
                    if (!$tambahan || $tambahan->user_id != $target_user_id) continue;

                    $updateData = [
                        'realisasi_kegiatan' => $data['detail'] ?? $tambahan->realisasi_kegiatan,
                    ];

                    // Jika detail diisi, kalkulasi Penghitungan & Nilai Capaian berdasarkan AK Realisasi
                    if (!empty(trim($updateData['realisasi_kegiatan']))) {
                        $updateData['ak_realisasi']        = $data['ak_realisasi'] ?? $tambahan->ak;

                        // Rumus baru: Penghitungan & Capaian = AK Realisasi
                        $valScore = (float) $updateData['ak_realisasi'];
                        $updateData['penghitungan']  = $valScore;
                        $updateData['nilai_capaian'] = $valScore;
                    }

                    // Handle File Upload Bukti
                    if ($request->hasFile("tugas_tambahan_realisasi.{$tambahan_id}.bukti")) {
                        $file = $request->file("tugas_tambahan_realisasi.{$tambahan_id}.bukti");
                        // Validasi tipe file — cegah upload file berbahaya (.php dll)
                        // ke disk public yang web-accessible via /storage.
                        $validator = \Illuminate\Support\Facades\Validator::make(
                            ['bukti' => $file],
                            ['bukti' => ['file', 'max:5120', 'mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png']]
                        );
                        if ($validator->fails()) {
                            return redirect()->back()
                                ->with('error', 'File bukti tugas tambahan tidak valid: ' . $validator->errors()->first('bukti'))
                                ->withInput();
                        }
                        // Hapus file lama jika ada
                        if ($tambahan->realisasi_bukti && \Illuminate\Support\Facades\Storage::disk('public')->exists($tambahan->realisasi_bukti)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($tambahan->realisasi_bukti);
                        }

                        $path = $file->store('bukti_tugas_tambahan', 'public');
                        $updateData['realisasi_bukti'] = $path;
                    }

                    $tambahan->update($updateData);
                }
            }

            // 5. PENANGANAN FINALISASI (Optional)
            if ($request->has('do_finalisasi') && $request->do_finalisasi == 1) {
                $header->update(['status' => 'Target Diajukan']);
                $msg = 'Perubahan berhasil disimpan dan target SKP telah difinalisasi!';
            } else {
                $msg = 'Perubahan berhasil disimpan!';
            }

            DB::commit();
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function updatePenilai(Request $request)
    {
        $request->validate([
            'skp_header_id' => 'required|exists:skp_headers,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
        ]);

        $header = SkpHeader::where('id', $request->skp_header_id)->where('user_id', Auth::id())->firstOrFail();
        $this->pastikanPengisianBuka($header->periode_skp_id);

        $header->update(['pejabat_penilai_id' => $request->pejabat_penilai_id]);
        return back()->with('success', 'Pejabat Penilai berhasil dipilih.');
    }

    // ==========================================
    // BAGIAN TUGAS TAMBAHAN
    // ==========================================
    public function storeTugasTambahan(Request $request)
    {
        $request->validate([
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'jenis' => 'required|string|max:255',
            'kegiatan' => 'required|string|max:255',
            'ak' => 'nullable|numeric',
            'realisasi_bukti' => 'nullable|file|mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png|max:5120',
        ]);

        $header = SkpHeader::where('user_id', Auth::id())->where('periode_skp_id', $request->periode_skp_id)->first();
        if (!$header) return back()->with('error', 'Data SKP tidak ditemukan.');

        // Jika status Review Akhir atau Selesai, tidak boleh tambah lagi
        if (in_array($header->status, ['Review Akhir', 'Selesai'])) {
            return back()->with('error', 'Anda tidak dapat lagi mengubah data karena SKP sedang dalam review pimpinan atau sudah selesai.');
        }

        $user_id = Auth::id();
        $jumlahTugasSaatIni = SkpTugasTambahan::where('user_id', $user_id)->where('periode_skp_id', $request->periode_skp_id)->count();

        if ($jumlahTugasSaatIni >= 7) {
            return back()->with('error', 'Gagal: Anda sudah mencapai batas maksimal 7 Tugas Tambahan/Kreativitas.');
        }

        DB::beginTransaction();
        try {
            $ak = $request->ak ?? 1.00;
            
            $path = null;
            if ($request->hasFile('realisasi_bukti')) {
                $path = $request->file('realisasi_bukti')->store('skp/tugas-tambahan', 'public');
            }

            $header = \App\Models\SkpHeader::where('user_id', $user_id)->where('periode_skp_id', $request->periode_skp_id)->first();

            SkpTugasTambahan::create([
                'user_id' => $user_id,
                'skp_header_id' => $header ? $header->id : null,
                'periode_skp_id' => $request->periode_skp_id,
                'jenis' => $request->jenis,
                'kegiatan' => $request->kegiatan,
                'ak' => $ak,
                'realisasi_bukti' => $path,
                'penghitungan' => $ak
            ]);
            DB::commit();
            return back()->with('success', 'Tugas Tambahan/Kreativitas berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function destroyTugasTambahan($id)
    {
        try {
            $tugas = SkpTugasTambahan::findOrFail($id);
            $user = Auth::user();
            
            // Cek Otoritas: Pemilik, Super Admin, atau Penilai/Atasan yang terdaftar di header
            $header = SkpHeader::where('user_id', $tugas->user_id)->where('periode_skp_id', $tugas->periode_skp_id)->first();
            
            $isOwner = ($tugas->user_id == $user->id);
            $isAdmin = $user->hasRole('Super Admin');
            $isPenilai = $header && ($header->pejabat_penilai_id == $user->id || $header->atasan_pejabat_penilai_id == $user->id);

            if (!$isOwner && !$isAdmin && !$isPenilai) {
                abort(403, 'Anda tidak memiliki otoritas untuk menghapus data ini.');
            }

            // Jika status Draft/Target Diajukan, kita hanya butuh cek periode aktif
            $header = SkpHeader::where('user_id', $tugas->user_id)->where('periode_skp_id', $tugas->periode_skp_id)->first();
            if ($header && in_array($header->status, ['Draft', 'Target Diajukan'])) {
                $periode = PeriodeSkp::find($tugas->periode_skp_id);
                if (!$periode || !$periode->isPengisianTerbuka()) {
                    abort(403, 'Waktu pengisian SKP telah ditutup.');
                }
            } else {
                // Pengecekan periode tetap berlaku untuk semua (tahap realisasi)
                $this->pastikanPengisianBuka($tugas->periode_skp_id);
            }
            
            $tugas->delete();
            return back()->with('success', 'Data Tugas Tambahan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // ==========================================
    // PENGAJUAN SKP KE ATASAN
    // ==========================================
    public function ajukanSkp(Request $request, $header_id)
    {
        $header = SkpHeader::where('id', $header_id)->where('user_id', Auth::id())->firstOrFail();
        $this->pastikanPengisianBuka($header->periode_skp_id);

        $isTendik = SkpTendik::where('user_id', Auth::id())->where('periode_skp_id', $header->periode_skp_id)->exists();

        if ($isTendik) {
            $skpUtama = SkpTendik::where('user_id', Auth::id())->where('periode_skp_id', $header->periode_skp_id)->with('penilaian')->get();
            $totalTarget = $skpUtama->count();
            $totalRealisasi = $skpUtama->filter(fn($i) => $i->penilaian !== null)->count();
        } else {
            $skpUtama = SkpDosen::where('periode_skp_id', $header->periode_skp_id)->with(['penilaian' => fn($q) => $q->where('user_id', Auth::id())])->get();
            $totalTarget = $skpUtama->count();
            $totalRealisasi = $skpUtama->filter(fn($i) => $i->penilaian !== null)->count();
        }

        if ($totalTarget == 0 || $totalTarget !== $totalRealisasi) {
            return back()->with('error', 'SKP belum lengkap! Pastikan semua Tupoksi sudah diisi realisasinya sebelum mengajukan.');
        }

        // Tentukan status berdasarkan jenis pegawai dan siapa penilainya
        $newStatus = 'Diajukan'; // Default Tendik

        if (!$isTendik) {
            // Untuk Dosen, kita lihat siapa penilai UTAMA-nya
            if ($header->penilai_utama_id) {
                $newStatus = 'Diajukan'; // Menunggu Wadek (di filter controller penilai: Wadek cari 'Diajukan')
            } elseif ($header->penilai_akhir_id) {
                $newStatus = 'Review Akhir'; // Menunggu Dekan
            }
        }

        $header->update(['status' => $newStatus]);
        return back()->with('success', 'SKP berhasil diajukan! Formulir terkunci dan sedang menunggu verifikasi Atasan.');
    }

    // ==========================================
    // BAGIAN PENILAIAN PERILAKU
    // ==========================================
    public function storePerilaku(Request $request, $id)
    {
        $header = SkpHeader::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $this->pastikanPengisianBuka($header->periode_skp_id);

        $request->validate([
            'perilaku' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan/Update Indikator Detail
            \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();

            $rekapAspek = []; // Untuk menyimpan rata-rata tiap aspek

            foreach ($request->perilaku as $aspek => $items) {
                // Filter out empty items
                $validItems = array_filter($items, function($item) {
                    return !empty(trim($item['indikator'] ?? '')) || !empty($item['nilai']);
                });

                $count = count($validItems);
                
                $totalBobot = 0;
                foreach ($validItems as $item) {
                    $totalBobot += (float) ($item['bobot_persen'] ?? 0);
                }
                
                $totalNilai = 0;

                foreach ($validItems as $item) {
                    $bobot = ($totalBobot > 0) ? (float) ($item['bobot_persen'] ?? 0) : (100 / ($count > 0 ? $count : 1));
                    
                    \App\Models\SkpPerilakuIndicator::create([
                        'skp_header_id' => $header->id,
                        'aspek' => $aspek,
                        'indikator' => $item['indikator'] ?? '',
                        'nilai' => $item['nilai'] ?? 0,
                        'bobot_persen' => $bobot,
                        'is_realized' => true,
                        'is_from_admin' => filter_var($item['is_from_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
                    ]);
                    $totalNilai += ((float) ($item['nilai'] ?? 0) * $bobot / 100);
                }

                $rekapAspek[$aspek] = $totalNilai;
            }

            // 2. Update/Create PenilaianPerilaku (Rekapitulasi)
            $mapAspek = [
                'Orientasi Pelayanan' => 'orientasi_pelayanan',
                'Integritas' => 'integritas',
                'Komitmen' => 'komitmen',
                'Disiplin' => 'disiplin',
                'Kerjasama' => 'kerjasama',
                'Kepemimpinan' => 'kepemimpinan',
            ];

            $updateData = [];
            foreach ($mapAspek as $label => $column) {
                $updateData[$column] = $rekapAspek[$label] ?? 0;
            }

            \App\Models\PenilaianPerilaku::updateOrCreate(
                ['user_id' => Auth::id(), 'periode_skp_id' => $header->periode_skp_id],
                $updateData
            );

            DB::commit();
            return back()->with('success', 'Penilaian Perilaku Kerja berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // ==========================================
    // LOGIKA PERHITUNGAN SENTRAL DOSEN
    // ==========================================
    private function hitungCapaianLogikaBaru($target, $realisasi)
    {
        $tKuant = (float) ($target->kuantitas_output > 0 ? $target->kuantitas_output : 1);
        $rKuant = (float) ($realisasi['kuantitas'] ?? 0);

        $tKual = (float) ($target->kualitas_mutu > 0 ? $target->kualitas_mutu : 100);
        $rKual = (float) ($realisasi['kualitas'] ?? 0);

        $tWaktu = (float) ($target->waktu ?? 0);
        $rWaktu = (float) ($realisasi['waktu'] ?? 0);

        $tBiaya = (float) ($target->biaya ?? 0);
        $rBiaya = (float) ($realisasi['biaya'] ?? 0);

        $persenKuantitas = min(($rKuant / $tKuant) * 100, 100);
        $persenKualitas = min(($rKual / $tKual) * 100, 100);

        $nilaiWaktu = 0;
        $pembagi = 2;

        if ($tWaktu > 0) {
            $pembagi += 1;
            if ($rWaktu <= 0 || $rWaktu < $tWaktu) $rWaktu = $tWaktu;
            $persenWaktu = 100 - (($rWaktu / $tWaktu) * 100);
            $efisiensiWaktu = ((1.76 * $tWaktu - $rWaktu) / $tWaktu) * 100;
            $koreksiWaktu = 76 - ($efisiensiWaktu - 100);
            $nilaiWaktu = min((($persenWaktu > 24) ? $koreksiWaktu : $efisiensiWaktu), 76);
        }

        $nilaiBiaya = 0;
        if ($tBiaya > 0) {
            $pembagi += 1;
            if ($rBiaya <= 0 || $rBiaya < $tBiaya) $rBiaya = $tBiaya;
            $persenBiaya = 100 - (($rBiaya / $tBiaya) * 100);
            $efisiensiBiaya = ((1.76 * $tBiaya - $rBiaya) / $tBiaya) * 100;
            $koreksiBiaya = 76 - ($efisiensiBiaya - 100);
            $nilaiBiaya = min((($persenBiaya > 24) ? $koreksiBiaya : $efisiensiBiaya), 76);
        }

        $totalPenghitungan = $persenKuantitas + $persenKualitas + $nilaiWaktu + $nilaiBiaya;

        return [
            'penghitungan' => round($totalPenghitungan, 2),
            'capaian'      => round($totalPenghitungan / $pembagi, 2)
        ];
    }

    // ==========================================
    // DETAIL DOSEN
    // ==========================================
    public function detailPerkuliahan($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        $targetSkp = SkpDosen::findOrFail($skp_id);
        if ($targetSkp->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');

        $penilaian = PenilaianSkpDosen::firstOrCreate(
            ['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id],
            ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]
        );
        $daftarMatkul = SkpDosenPerkuliahan::where('penilaian_skp_dosen_id', $penilaian->id)->orderBy('semester', 'asc')->get();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-perkuliahan', compact('penilaian', 'targetSkp', 'daftarMatkul', 'isFormLocked'));
    }

    public function storePerkuliahan(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenPerkuliahan::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkPerkuliahan($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data perkuliahan berhasil direset dan dihapus.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.semester' => 'required|in:gasal,genap',
            'items.*.nama_mata_kuliah' => 'required|string',
            'items.*.sks_mata_kuliah' => 'required|numeric|min:0',
            'items.*.jumlah_kelas' => 'required|numeric|min:1',
            'items.*.jumlah_dosen_pengampu' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $key => $item) {
                $total_sks_riel = ($item['sks_mata_kuliah'] * $item['jumlah_kelas']) / $item['jumlah_dosen_pengampu'];
                // Validasi tipe file bukti (cegah upload .php dll ke disk public).
                if ($request->hasFile("items.$key.file_bukti")) {
                    $buktiValidator = \Illuminate\Support\Facades\Validator::make(
                        ['bukti' => $request->file("items.$key.file_bukti")],
                        ['bukti' => ['file', 'max:5120', 'mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png']]
                    );
                    if ($buktiValidator->fails()) {
                        DB::rollBack();
                        return redirect()->back()
                            ->with('error', 'File bukti tidak valid: ' . $buktiValidator->errors()->first('bukti'))
                            ->withInput();
                    }
                }
                $filePath = $request->hasFile("items.$key.file_bukti") ? $request->file("items.$key.file_bukti")->store('bukti_ajar', 'public') : null;

                SkpDosenPerkuliahan::create([
                    'penilaian_skp_dosen_id' => $request->penilaian_id,
                    'semester' => $item['semester'],
                    'nama_mata_kuliah' => $item['nama_mata_kuliah'],
                    'sks_mata_kuliah' => $item['sks_mata_kuliah'],
                    'jumlah_kelas' => $item['jumlah_kelas'],
                    'jumlah_dosen_pengampu' => $item['jumlah_dosen_pengampu'],
                    'total_sks_riel' => $total_sks_riel,
                    'bukti_dokumen' => $filePath
                ]);
            }
            $this->rehitungAkPerkuliahan($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data perkuliahan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroyPerkuliahan($id) {
        $item = SkpDosenPerkuliahan::findOrFail($id);
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($item->penilaian_skp_dosen_id);
        // Guard IDOR: penilaian wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        $item->delete();
        $this->rehitungAkPerkuliahan($penilaian->id);
        return back()->with('success', 'Mata kuliah dihapus.');
    }

    private function rehitungAkPerkuliahan($penilaian_id) {
        $semuaMatkul = SkpDosenPerkuliahan::where('penilaian_skp_dosen_id', $penilaian_id)->get();

        if ($semuaMatkul->isEmpty()) {
            $penilaian = PenilaianSkpDosen::find($penilaian_id);
            $penilaian->update([
                'realisasi_ak' => 0,
                'realisasi_kuantitas' => 0,
                'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0,
                'realisasi_biaya' => 0,
                'nilai_penghitungan' => 0,
                'nilai_capaian' => 0
            ]);
            return;
        }

        $logicSem = function($items) {
            $sks = $items->sum('total_sks_riel');
            $ak10 = ($sks >= 10) ? (10 * 0.5) : ($sks * 0.5);
            $akNext = ($sks > 10) ? (($sks - 10) * 0.25) : 0;
            return ['ak' => $ak10 + $akNext, 'waktu' => ($sks >= 1) ? 6 : 0, 'count' => $items->count()];
        };

        $genap = $logicSem($semuaMatkul->where('semester', 'genap'));
        $gasal = $logicSem($semuaMatkul->where('semester', 'gasal'));

        $totalWaktu = $genap['waktu'] + $gasal['waktu'];
        $totalKuantitas = $genap['count'] + $gasal['count'];

        $penilaian = PenilaianSkpDosen::find($penilaian_id);
        $target = $penilaian->skpDosen;

        $hasil = $this->hitungCapaianLogikaBaru($target, [
            'kuantitas' => $totalKuantitas,
            'kualitas' => 100,
            'waktu' => $totalWaktu,
            'biaya' => 0
        ]);

        $penilaian->update([
            'realisasi_ak' => $genap['ak'] + $gasal['ak'],
            'realisasi_kuantitas' => $totalKuantitas,
            'realisasi_kualitas' => 100,
            'realisasi_waktu' => $totalWaktu,
            'realisasi_biaya' => 0,
            'nilai_penghitungan' => $hasil['penghitungan'],
            'nilai_capaian' => $hasil['capaian']
        ]);
    }

    public function detailSeminar($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataGasal = SkpDosenSeminar::where('penilaian_skp_dosen_id', $penilaian->id)->where('semester', 'gasal')->first();
        $dataGenap = SkpDosenSeminar::where('penilaian_skp_dosen_id', $penilaian->id)->where('semester', 'genap')->first();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-seminar', compact('penilaian', 'targetSkp', 'dataGasal', 'dataGenap', 'isFormLocked'));
    }

    public function storeSeminar(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenSeminar::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkSeminar($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data seminar berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach(['gasal', 'genap'] as $sem) {
                SkpDosenSeminar::updateOrCreate(
                    ['penilaian_skp_dosen_id' => $request->penilaian_id, 'semester' => $sem],
                    ['jumlah_mahasiswa' => $request->jumlah_mahasiswa[$sem] ?? 0]
                );
            }
            $this->rehitungAkSeminar($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data seminar berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkSeminar($penilaian_id) {
        $data = SkpDosenSeminar::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($data->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalAk = 0; $totalKuantitas = 0; $totalWaktu = 0;

        foreach($data as $item) {
            if ($item->jumlah_mahasiswa >= 1) {
                $totalAk += 1;
                $totalWaktu += 6;
            }
            $totalKuantitas += $item->jumlah_mahasiswa;
        }

        $kualitasRealisasi = ($totalKuantitas > 0) ? 100 : 0;
        $waktuRealisasi = ($totalKuantitas > 0) ? min($totalWaktu, 12) : 0;

        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, [
            'kuantitas' => $totalKuantitas,
            'kualitas' => $kualitasRealisasi,
            'waktu' => $waktuRealisasi,
            'biaya' => 0
        ]);

        $penilaian->update([
            'realisasi_ak' => $totalAk,
            'realisasi_kuantitas' => $totalKuantitas,
            'realisasi_kualitas' => $kualitasRealisasi,
            'realisasi_waktu' => $waktuRealisasi,
            'realisasi_biaya' => 0,
            'nilai_penghitungan' => $hasil['penghitungan'],
            'nilai_capaian' => $hasil['capaian']
        ]);
    }

    public function detailPkl($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenPkl::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $data = ['KKN' => $dataDB->where('jenis_kegiatan', 'KKN')->first(), 'PKN' => $dataDB->where('jenis_kegiatan', 'PKN')->first(), 'PKL' => $dataDB->where('jenis_kegiatan', 'PKL')->first()];

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-pkl', compact('penilaian', 'targetSkp', 'data', 'isFormLocked'));
    }

    public function storePkl(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenPkl::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkPkl($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data PKL/KKN berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->data as $jenis => $nilai) {
                SkpDosenPkl::updateOrCreate(
                    ['penilaian_skp_dosen_id' => $request->penilaian_id, 'jenis_kegiatan' => $jenis],
                    ['jml_mhs_genap' => $nilai['genap'] ?? 0, 'jml_mhs_gasal' => $nilai['gasal'] ?? 0]
                );
            }
            $this->rehitungAkPkl($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data PKL/KKN berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkPkl($penilaian_id) {
        $items = SkpDosenPkl::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalAk = 0; $totalWaktu = 0; $totalKegiatan = 0;

        foreach ($items as $item) {
            $jml = ($item->jml_mhs_genap >= 1 ? 1 : 0) + ($item->jml_mhs_gasal >= 1 ? 1 : 0);
            $totalAk += $jml * 1; $totalKegiatan += $jml;
            $totalWaktu += ($jml >= 2) ? 12 : ($jml == 1 ? 6 : 0);
        }

        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalKegiatan, 'kualitas' => 100, 'waktu' => min($totalWaktu, 12), 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalKegiatan, 'realisasi_kualitas' => 100, 'realisasi_waktu' => min($totalWaktu, 12), 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    public function detailBimbing($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenBimbing::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $data = [];
        foreach(['Skripsi', 'Tesis', 'Disertasi'] as $kat) {
            foreach(['Genap', 'Gasal'] as $sem) $data[$kat][$sem] = $dataDB->where('kategori', $kat)->where('semester', $sem)->first();
        }

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-bimbing', compact('penilaian', 'targetSkp', 'data', 'isFormLocked'));
    }

    public function storeBimbing(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenBimbing::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkBimbing($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data bimbingan berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->bimbing as $kategori => $semesters) {
                foreach ($semesters as $semester => $nilai) {
                    SkpDosenBimbing::updateOrCreate(
                        ['penilaian_skp_dosen_id' => $request->penilaian_id, 'kategori' => $kategori, 'semester' => $semester],
                        ['jml_mhs_utama' => $nilai['utama'] ?? 0, 'jml_mhs_penyerta' => $nilai['penyerta'] ?? 0]
                    );
                }
            }
            $this->rehitungAkBimbing($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data bimbingan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkBimbing($penilaian_id) {
        $items = SkpDosenBimbing::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalAk = 0; $totalMahasiswa = 0; $semAct = ['Genap' => false, 'Gasal' => false];

        foreach ($items as $item) {
            $totalMhsItem = $item->jml_mhs_utama + $item->jml_mhs_penyerta;
            if ($totalMhsItem > 0) $semAct[$item->semester] = true;

            $pembagi = match($item->kategori) { 'Skripsi' => 6, 'Tesis' => 3, 'Disertasi' => 2, default => 1 };

            $totalAk += (($item->jml_mhs_utama / $pembagi) * 1) + ((($item->jml_mhs_penyerta / $pembagi) * 0.5) * 0.5);
            $totalMahasiswa += $totalMhsItem;
        }

        $activeCount = ($semAct['Genap'] ? 1 : 0) + ($semAct['Gasal'] ? 1 : 0);
        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalMahasiswa, 'kualitas' => 100, 'waktu' => ($activeCount >= 2) ? 12 : ($activeCount == 1 ? 6 : 0), 'biaya' => 0]);

        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalMahasiswa, 'realisasi_kualitas' => 100, 'realisasi_waktu' => ($activeCount >= 2) ? 12 : ($activeCount == 1 ? 6 : 0), 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    public function detailPenguji($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenPenguji::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $data = ['Genap' => $dataDB->where('semester', 'Genap')->first(), 'Gasal' => $dataDB->where('semester', 'Gasal')->first()];

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-penguji', compact('penilaian', 'targetSkp', 'data', 'isFormLocked'));
    }

    public function storePenguji(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenPenguji::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkPenguji($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data penguji berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->data as $semester => $nilai) {
                SkpDosenPenguji::updateOrCreate(
                    ['penilaian_skp_dosen_id' => $request->penilaian_id, 'semester' => $semester],
                    ['jml_mhs_ketua' => $nilai['ketua'] ?? 0, 'jml_mhs_anggota' => $nilai['anggota'] ?? 0]
                );
            }
            $this->rehitungAkPenguji($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data penguji berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkPenguji($penilaian_id) {
        $items = SkpDosenPenguji::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalAk = 0; $totalMahasiswa = 0; $semAct = ['Genap' => false, 'Gasal' => false];

        foreach ($items as $item) {
            $totalMhs = $item->jml_mhs_ketua + $item->jml_mhs_anggota;
            if ($totalMhs > 0) $semAct[$item->semester] = true;
            $totalAk += ($item->jml_mhs_ketua * 1) + ($item->jml_mhs_anggota * 0.5);
            $totalMahasiswa += $totalMhs;
        }

        $activeCount = ($semAct['Genap'] ? 1 : 0) + ($semAct['Gasal'] ? 1 : 0);
        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalMahasiswa, 'kualitas' => 100, 'waktu' => ($activeCount >= 2) ? 12 : ($activeCount == 1 ? 6 : 0), 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalMahasiswa, 'realisasi_kualitas' => 100, 'realisasi_waktu' => ($activeCount >= 2) ? 12 : ($activeCount == 1 ? 6 : 0), 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    public function detailKemahasiswaan($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $daftarKegiatan = SkpDosenKemahasiswaan::where('penilaian_skp_dosen_id', $penilaian->id)->get();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-kemahasiswaan', compact('penilaian', 'targetSkp', 'daftarKegiatan', 'isFormLocked'));
    }

    public function storeKemahasiswaan(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenKemahasiswaan::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkKemahasiswaan($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data kemahasiswaan berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                if (!empty($item['nama_kegiatan'])) {
                    SkpDosenKemahasiswaan::create([
                        'penilaian_skp_dosen_id' => $request->penilaian_id,
                        'nama_kegiatan' => $item['nama_kegiatan'],
                        'jumlah_genap' => $item['jumlah_genap'] ?? 0,
                        'jumlah_gasal' => $item['jumlah_gasal'] ?? 0,
                    ]);
                }
            }
            $this->rehitungAkKemahasiswaan($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data kegiatan kemahasiswaan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroyKemahasiswaan($id) {
        $item = SkpDosenKemahasiswaan::findOrFail($id);
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($item->penilaian_skp_dosen_id);
        // Guard IDOR: penilaian wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        $item->delete();
        $this->rehitungAkKemahasiswaan($penilaian->id);
        return back()->with('success', 'Data kegiatan dihapus.');
    }

    private function rehitungAkKemahasiswaan($penilaian_id) {
        $items = SkpDosenKemahasiswaan::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalAk = 0;
        $totalKegiatan = 0;

        foreach ($items as $item) {
            $kegiatanAktif = ($item->jumlah_genap > 0 || $item->jumlah_gasal > 0) ? 1 : 0;
            $aktifGenap = $item->jumlah_genap > 0 ? 1 : 0;
            $aktifGasal = $item->jumlah_gasal > 0 ? 1 : 0;
            $akBaris = ($aktifGenap + $aktifGasal) * 2;

            $totalAk += $akBaris;
            $totalKegiatan += $kegiatanAktif;
        }

        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, [
            'kuantitas' => $totalKegiatan,
            'kualitas' => 100,
            'waktu' => ($totalKegiatan >= 2) ? 12 : ($totalKegiatan >= 1 ? 6 : 0),
            'biaya' => 0
        ]);

        $penilaian->update([
            'realisasi_ak' => $totalAk,
            'realisasi_kuantitas' => $totalKegiatan,
            'realisasi_kualitas' => 100,
            'realisasi_waktu' => ($totalKegiatan >= 2) ? 12 : ($totalKegiatan >= 1 ? 6 : 0),
            'realisasi_biaya' => 0,
            'nilai_penghitungan' => $hasil['penghitungan'],
            'nilai_capaian' => $hasil['capaian']
        ]);
    }

    public function detailBahan($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenBahanKuliah::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $masterBahan = ['Buku Ajar' => 20, 'Diktat' => 5, 'Modul' => 5, 'Petunjuk Praktikum' => 5, 'Model' => 5, 'Alat Bantu' => 5, 'Audio Visual' => 5, 'Naskah Tutorial' => 5];

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-bahan', compact('penilaian', 'targetSkp', 'dataDB', 'masterBahan', 'isFormLocked'));
    }

    public function storeBahan(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenBahanKuliah::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkBahan($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data bahan kuliah berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->data as $semester => $items) {
                foreach ($items as $jenis => $jumlah) {
                    SkpDosenBahanKuliah::updateOrCreate(
                        ['penilaian_skp_dosen_id' => $request->penilaian_id, 'semester' => $semester, 'jenis_bahan' => $jenis],
                        ['jumlah' => $jumlah ?? 0]
                    );
                }
            }
            $this->rehitungAkBahan($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data bahan kuliah berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkBahan($penilaian_id) {
        $items = SkpDosenBahanKuliah::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $tarif = ['Buku Ajar' => 20, 'Diktat' => 5, 'Modul' => 5, 'Petunjuk Praktikum' => 5, 'Model' => 5, 'Alat Bantu' => 5, 'Audio Visual' => 5, 'Naskah Tutorial' => 5];
        $totalAk = 0; $totalQty = 0; $sem = ['Genap' => 0, 'Gasal' => 0];

        foreach ($items as $item) {
            $totalAk += $item->jumlah * ($tarif[$item->jenis_bahan] ?? 0);
            $totalQty += $item->jumlah; $sem[$item->semester] += $item->jumlah;
        }

        $flagCount = ($sem['Genap'] >= 1 ? 1 : 0) + ($sem['Gasal'] >= 1 ? 1 : 0);
        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalQty, 'kualitas' => 100, 'waktu' => ($flagCount >= 2) ? 12 : ($flagCount == 1 ? 6 : 0), 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalQty, 'realisasi_kualitas' => 100, 'realisasi_waktu' => ($flagCount >= 2) ? 12 : ($flagCount == 1 ? 6 : 0), 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    private function getMasterKaryaIlmiah() { return ['Monograf' => 20, 'Buku referensi' => 40, 'Artikel di jurnal ilmiah internasional' => 40, 'Artikel di jurnal ilmiah nasional terakreditasi' => 25, 'Artikel di jurnal ilmiah nasional' => 10, 'Makalah disajikan di seminar internasional' => 15, 'Makalah disajikan di seminar nasional' => 10, 'Poster internasional' => 10, 'Poster nasional' => 5, 'Artikel di majalah/koran' => 1, 'Hasil Penelitian / hasil pemikiran yg tidak dipublikasikan (tersimpan diperpustakaan PT)' => 2, 'Menerjemahkan/menyadur buku ilmiah diterbitkan/diedarkan secara Nasional' => 15, 'Mengedit/menyunting karya ilmiah, diterbitkan dan diedarkan secara Nasional' => 10, 'Membuat rencana dan karya teknologi yang dipatenkan tingkat Internasional' => 60, 'Membuat rencana dan karya teknologi yang dipatenkan tingkat Nasional' => 40, 'Membuat rancangan dan karya teknologi, rancangan dan karya seni monumental/seni pertunjukan/karya sastra Tk. Internasional' => 20, 'Membuat rancangan dan karya teknologi, rancangan dan karya seni monumental/seni pertunjukan/karya sastra Tk. Nasional' => 15, 'Membuat rancangan dan karya teknologi, rancangan dan karya seni monumental/seni pertunjukan/karya sastra Tk. Lokal' => 10]; }

    public function detailKaryaIlmiah($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenKaryaIlmiah::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $masterKarya = $this->getMasterKaryaIlmiah();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-karya-ilmiah', compact('penilaian', 'targetSkp', 'dataDB', 'masterKarya', 'isFormLocked'));
    }

    public function storeKaryaIlmiah(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenKaryaIlmiah::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkKaryaIlmiah($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data karya ilmiah berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->data as $jenis => $jumlah) {
                SkpDosenKaryaIlmiah::updateOrCreate(
                    ['penilaian_skp_dosen_id' => $request->penilaian_id, 'jenis_karya' => $jenis],
                    ['jumlah' => $jumlah ?? 0]
                );
            }
            $this->rehitungAkKaryaIlmiah($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data karya ilmiah berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkKaryaIlmiah($penilaian_id) {
        $items = SkpDosenKaryaIlmiah::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $tarif = $this->getMasterKaryaIlmiah();
        $totalAk = 0; $totalKarya = 0;

        foreach ($items as $item) {
            $totalAk += $item->jumlah * ($tarif[$item->jenis_karya] ?? 0);
            $totalKarya += $item->jumlah;
        }

        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalKarya, 'kualitas' => 100, 'waktu' => ($totalKarya >= 1) ? 12 : 0, 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalKarya, 'realisasi_kualitas' => 100, 'realisasi_waktu' => ($totalKarya >= 1) ? 12 : 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    private function getMasterAbdimas() { return ['A' => ['title' => 'Kegiatan Abdimas lebih dari 6 bulan', 'items' => ['Satu semester/lebih Memberikan pelatihan/penyuluhan/penataran/ ceramah pada masyarakat Tingkat Internasional' => 4, 'Satu Semester / Lebih Memberikan pelatihan/ penyuluhan/ penataran/ ceramah pada masyarakat Tingkat Nasional' => 3, 'Satu Semester / Lebih Memberikan pelatihan/ penyuluhan/ penataran/ ceramah pada masyarakat Tingkat Lokal' => 2]], 'B' => ['title' => 'Kegiatan Abdimas kurang dari 6 bulan', 'items' => ['Kurang dari satu Semester Memberikan pelatihan/ penyuluhan/ penataran/ ceramah pada masyarakat Tingkat Internasional' => 3, 'Kurang dari satu semester Memberikan pelatihan/ penyuluhan/ penataran/ ceramah pada masyarakat Tingkat Nasional' => 2, 'Kurang dari Satu semester Memberikan pelatihan/ penyuluhan/ penataran/ ceramah pada masyarakat Tingkat Lokal' => 1, 'Insidental' => 1]], 'C' => ['title' => 'Kegiatan Abdimas yang dilaksanakan dalam periode waktu 1 tahun', 'items' => ['Menduduki jabatan pimpinan pada lembaga pemerintahan/pejabat negara yang harus dibebaskan dari jabatan organiknya' => 5.5, 'Melaksanakan pengembangan hasil pendidikan dan penelitian yang dapat dimanfaatkan' => 3, 'Memberi pelayanan kepada masyarakat atau kegiatan lain yang menunjang pelaksanaan tugas umum pemerintah dan pembangunan berdasarkan keahlian' => 1.5, 'Memberi pelayanan kepada masyarakat atau kegiatan lain yang menunjang pelaksanaan tugas umum pemerintah dan pembangunan berdasarkan penugasan lembaga PT' => 1, 'Memberi pelayanan kepada masyarakat atau kegiatan lain yang menunjang pelaksanaan tugas umum pemerintah dan pembangunan berdasarkan fungsi/jabatan' => 0.5, 'Membuat/menulis karya pengabdian pada masyarakat yang tidak dipublikasikan' => 3]]]; }

    public function detailAbdimas($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenAbdimas::where('penilaian_skp_dosen_id', $penilaian->id)->get();
        $masterData = $this->getMasterAbdimas();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-abdimas', compact('penilaian', 'targetSkp', 'dataDB', 'masterData', 'isFormLocked'));
    }

    public function storeAbdimas(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenAbdimas::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkAbdimas($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data pengabdian masyarakat berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->data as $kategori => $items) {
                foreach ($items as $kegiatan => $jumlah) {
                    SkpDosenAbdimas::updateOrCreate(
                        ['penilaian_skp_dosen_id' => $request->penilaian_id, 'kategori' => $kategori, 'kegiatan' => $kegiatan],
                        ['jumlah' => $jumlah ?? 0]
                    );
                }
            }
            $this->rehitungAkAbdimas($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data pengabdian masyarakat berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkAbdimas($penilaian_id) {
        $items = SkpDosenAbdimas::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $master = $this->getMasterAbdimas();
        $totalAk = 0; $totalQty = 0; $sumK = ['A' => 0, 'B' => 0, 'C' => 0];

        foreach ($items as $item) {
            $akSatuan = $master[$item->kategori]['items'][$item->kegiatan] ?? 0;
            $totalAk += $item->jumlah * $akSatuan;
            $totalQty += $item->jumlah; $sumK[$item->kategori] += $item->jumlah;
        }

        $timeComp = ($sumK['C'] >= 1 ? 12 : 0) + ($sumK['B'] >= 1 ? 5 : 0) + ($sumK['A'] >= 1 ? 12 : 0);
        $waktuFinal = ($timeComp >= 6) ? 12 : ($sumK['B'] >= 1 ? 6 : 0);

        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalQty, 'kualitas' => 100, 'waktu' => $waktuFinal, 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $totalAk, 'realisasi_kuantitas' => $totalQty, 'realisasi_kualitas' => 100, 'realisasi_waktu' => $waktuFinal, 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    private function getMasterJabatan() { return [1 => ['nama' => 'Rektor', 'ak' => 6], 2 => ['nama' => 'Pembantu/Wakil Rektor', 'ak' => 5], 3 => ['nama' => 'Dekan', 'ak' => 5], 4 => ['nama' => 'Direktur Pasca Sarjana', 'ak' => 5], 5 => ['nama' => 'Ketua Sekolah Tinggi', 'ak' => 4], 10 => ['nama' => 'Pembantu/Wakil Dekan', 'ak' => 4], 13 => ['nama' => 'Ketua Jurusan/Bagian', 'ak' => 3], 15 => ['nama' => 'Sekretaris Jurusan', 'ak' => 3], 16 => ['nama' => 'Kepala Laboratorium', 'ak' => 3]]; }

    public function detailJabatan($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $dataDB = SkpDosenJabatan::where('penilaian_skp_dosen_id', $penilaian->id)->first();
        $masterJabatan = $this->getMasterJabatan();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-jabatan', compact('penilaian', 'targetSkp', 'dataDB', 'masterJabatan', 'isFormLocked'));
    }

    public function storeJabatan(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenJabatan::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkJabatan($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data jabatan pimpinan berhasil direset.');
        }

        DB::beginTransaction();
        try {
            SkpDosenJabatan::updateOrCreate(
                ['penilaian_skp_dosen_id' => $request->penilaian_id],
                ['kode_genap' => $request->kode_genap, 'kode_gasal' => $request->kode_gasal]
            );
            $this->rehitungAkJabatan($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data jabatan pimpinan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function rehitungAkJabatan($penilaian_id) {
        $item = SkpDosenJabatan::where('penilaian_skp_dosen_id', $penilaian_id)->first();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if (!$item) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $master = $this->getMasterJabatan();
        $akGenap = ($item && $item->kode_genap && isset($master[$item->kode_genap])) ? $master[$item->kode_genap]['ak'] : 0;
        $akGasal = ($item && $item->kode_gasal && isset($master[$item->kode_gasal])) ? $master[$item->kode_gasal]['ak'] : 0;

        $countAct = ($akGenap > 0 ? 1 : 0) + ($akGasal > 0 ? 1 : 0);
        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $countAct, 'kualitas' => 100, 'waktu' => $countAct * 6, 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $akGenap + $akGasal, 'realisasi_kuantitas' => $countAct, 'realisasi_kualitas' => 100, 'realisasi_waktu' => $countAct * 6, 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    public function updateTargetDetail(Request $request)
    {
        $request->validate([
            'skp_id' => 'required|exists:skp_dosen,id',
            'detail' => 'nullable|array',
            'ak' => 'nullable|numeric',
            'kuantitas' => 'nullable|numeric',
            'kualitas' => 'nullable|numeric',
            'waktu' => 'nullable|numeric',
            'biaya' => 'nullable|numeric',
        ]);

        $skp = SkpDosen::findOrFail($request->skp_id);
        $header = SkpHeader::where('periode_skp_id', $skp->periode_skp_id)->where('user_id', $skp->user_id)->first();
        
        $authUser = Auth::user();
        $isOwner = ($skp->user_id == $authUser->id);
        
        $isLevel3 = \App\Models\MasterSkpTendik::where('user_id', $skp->user_id)
                    ->where('penilai_role', '!=', null)
                    ->get()
                    ->filter(fn($act) => $authUser->hasRole($act->penilai_role))
                    ->isNotEmpty();

        $isPimpinan = ($header->pejabat_penilai_id == $authUser->id || 
                       $header->atasan_pejabat_penilai_id == $authUser->id ||
                       $isLevel3 ||
                       $authUser->hasRole('Super Admin'));
        
        if (!$isOwner && !$isPimpinan) {
            return back()->with('error', 'Anda tidak memiliki otoritas untuk mengedit data ini.');
        }

        $detail = $request->detail;
        if ($skp->jenis === 'melaksanakan_perkuliahan' || $skp->jenis === 'membina_kemahasiswaan') {
            $detail = array_values($detail ?? []);
        }

        $skp->update([
            'detail_kegiatan' => $detail,
            'ak' => $request->ak ?? $skp->ak,
            'kuantitas_output' => $request->kuantitas ?? $skp->kuantitas_output,
            'kualitas_mutu' => $request->kualitas ?? $skp->kualitas_mutu,
            'waktu' => $request->waktu ?? $skp->waktu,
            'biaya' => $request->biaya ?? $skp->biaya,
        ]);

        return back()->with('success', 'Detail target berhasil diperbarui.');
    }

    public function detailPenunjang($skp_id) {
        // Guard: skp_dosen_id harus milik user yang login (cegah IDOR antar user).
        if (SkpDosen::findOrFail($skp_id)->user_id != Auth::id()) abort(403, 'Anda tidak memiliki akses ke data SKP ini.');
        $penilaian = PenilaianSkpDosen::firstOrCreate(['user_id' => Auth::id(), 'skp_dosen_id' => $skp_id], ['realisasi_ak' => 0, 'realisasi_kuantitas' => 0]);
        $targetSkp = SkpDosen::findOrFail($skp_id);
        $daftarKegiatan = SkpDosenPenunjang::where('penilaian_skp_dosen_id', $penilaian->id)->get();

        $header = SkpHeader::where('periode_skp_id', $targetSkp->periode_skp_id)->where('user_id', Auth::id())->first();
        $isFormLocked = !$header || in_array($header->status, ['Review Akhir', 'Selesai']);

        return view('user.skp-dosen.detail-penunjang', compact('penilaian', 'targetSkp', 'daftarKegiatan', 'isFormLocked'));
    }

    public function storePenunjang(Request $request) {
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($request->penilaian_id);
        // Guard IDOR: penilaian yang diubah wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        if ($request->has('reset') && $request->reset == 'true') {
            SkpDosenPenunjang::where('penilaian_skp_dosen_id', $request->penilaian_id)->delete();
            $this->rehitungAkPenunjang($request->penilaian_id);
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data unsur penunjang berhasil direset.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                if (!empty($item['nama_kegiatan'])) {
                    SkpDosenPenunjang::create(['penilaian_skp_dosen_id' => $request->penilaian_id, 'nama_kegiatan' => $item['nama_kegiatan'], 'ak' => $item['ak']]);
                }
            }
            $this->rehitungAkPenunjang($request->penilaian_id);
            DB::commit();
            return redirect()->route('pegawai.skp-dosen.index')->with('success', 'Data unsur penunjang berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function destroyPenunjang($id) {
        $item = SkpDosenPenunjang::findOrFail($id);
        $penilaian = PenilaianSkpDosen::with('skpDosen')->findOrFail($item->penilaian_skp_dosen_id);
        // Guard IDOR: penilaian wajib milik user yang login.
        if ($penilaian->user_id != Auth::id()) abort(403, 'Anda tidak memiliki otoritas atas data penilaian ini.');
        $this->pastikanPengisianBuka($penilaian->skpDosen->periode_skp_id);

        $item->delete();
        $this->rehitungAkPenunjang($penilaian->id);
        return back()->with('success', 'Data dihapus.');
    }

    private function rehitungAkPenunjang($penilaian_id) {
        $items = SkpDosenPenunjang::where('penilaian_skp_dosen_id', $penilaian_id)->get();
        $penilaian = PenilaianSkpDosen::find($penilaian_id);

        if ($items->isEmpty()) {
            $penilaian->update([
                'realisasi_ak' => 0, 'realisasi_kuantitas' => 0, 'realisasi_kualitas' => 0,
                'realisasi_waktu' => 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 0, 'nilai_capaian' => 0
            ]);
            return;
        }

        $totalKegiatan = $items->where('ak', '>=', 1)->count();
        $hasil = $this->hitungCapaianLogikaBaru($penilaian->skpDosen, ['kuantitas' => $totalKegiatan, 'kualitas' => 100, 'waktu' => ($totalKegiatan >= 1) ? 12 : 0, 'biaya' => 0]);
        $penilaian->update(['realisasi_ak' => $items->sum('ak'), 'realisasi_kuantitas' => $totalKegiatan, 'realisasi_kualitas' => 100, 'realisasi_waktu' => ($totalKegiatan >= 1) ? 12 : 0, 'realisasi_biaya' => 0, 'nilai_penghitungan' => $hasil['penghitungan'], 'nilai_capaian' => $hasil['capaian']]);
    }

    public function downloadBukti(Request $request)
    {
        $path = $request->query('path');
        if (!$path) abort(404);
        if (!str_starts_with($path, 'skp-tendik/')) abort(403);

        // Cegah path traversal (mis. ?path=skp-tendik/../../.env): setelah
        // resolve realpath, file wajib tetap berada di dalam direktori
        // storage/app/public/skp-tendik.
        $fullPath = storage_path('app/public/' . $path);
        $realFullPath = realpath($fullPath);
        $realBaseDir = realpath(storage_path('app/public/skp-tendik'));
        if ($realFullPath === false || $realBaseDir === false || !str_starts_with($realFullPath, $realBaseDir . DIRECTORY_SEPARATOR)) {
            abort(403);
        }
        return response()->file($realFullPath);
    }
}
