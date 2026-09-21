<?php

namespace App\Http\Controllers\Dekan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, SkpHeader, SkpDosen, SkpTendik, MasterSkpTendik, Prodi};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DekanController extends Controller
{
    // =========================
    // DASHBOARD
    // =========================
    public function dashboard()
    {
        $user = auth()->user();

        // Akses eksklusif role Dekan.
        // Permission 'akses-dashboard-pimpinan' juga dipakai Wadek 1/2, sehingga
        // halaman ini bisa dibuka manual oleh mereka. Cek role spesifik & arahkan
        // ke dashboard masing-masing (logic serupa closure /dashboard web.php:51).
        $role = $user->roles()->orderBy('kategori', 'asc')->first();
        $intended = ($role && !empty($role->dashboard_route))
            ? $role->dashboard_route
            : 'pegawai.dashboard';
        if ($intended !== 'dekan.dashboard') {
            $target = Route::has($intended) ? $intended : 'pegawai.dashboard';
            return redirect()->route($target);
        }

        // ===== STAT CARDS (query asli, tanpa hardcode) =====
        $totalDosen  = User::whereHas('roles', fn($q) => $q->where('name', 'like', 'Dosen%'))->count();
        $totalTendik = User::whereHas('roles', fn($q) => $q->where('kategori', 'tendik'))->count();
        $totalProdi  = Prodi::count();

        // Status yang dianggap SUDAH final (bukan menunggu approval Dekan).
        // Sesuai arahan: exclude 'Disetujui', 'Selesai', 'Ditolak'.
        $finalStatus = ['Disetujui', 'Selesai', 'Ditolak'];

        $dosenPending = SkpHeader::whereHas('user.roles', fn($q) => $q->where('kategori', 'utama'))
            ->whereNotIn('status', $finalStatus)
            ->with(['user.profile', 'periode'])
            ->get();

        $kualitatifPending = SkpHeader::whereHas('skpKualitatifs')
            ->whereNotIn('status', $finalStatus)
            ->with(['user.profile', 'periode'])
            ->get();

        $tendikPending = MasterSkpTendik::where('status_approval', 'pending')
            ->with(['user.profile', 'header.periode'])
            ->get();

        $totalPending = $dosenPending->count() + $kualitatifPending->count() + $tendikPending->count();

        // ===== TABEL GABUNGAN (5 terbaru) =====
        $rows = collect();

        foreach ($dosenPending as $h) {
            $rows->push([
                'pegawai' => optional($h->user->profile)->nama_lengkap ?? ($h->user->name ?? '-'),
                'jenis'   => 'SKP Dosen',
                'periode' => optional($h->periode)->nama_periode ?? '-',
                'status'  => $h->status,
                'url'     => route('penilai.evaluasi.show', $h->id),
                'sort'    => $h->updated_at ?? $h->created_at,
            ]);
        }

        foreach ($kualitatifPending as $h) {
            $rows->push([
                'pegawai' => optional($h->user->profile)->nama_lengkap ?? ($h->user->name ?? '-'),
                'jenis'   => 'SKP Kualitatif',
                'periode' => optional($h->periode)->nama_periode ?? '-',
                'status'  => $h->status,
                'url'     => route('penilai.approval-skp-dosen-kualitatif.show', $h->id),
                'sort'    => $h->updated_at ?? $h->created_at,
            ]);
        }

        foreach ($tendikPending as $m) {
            $rows->push([
                'pegawai' => optional(optional($m->user)->profile)->nama_lengkap ?? ($m->user->name ?? '-'),
                'jenis'   => 'SKP Tendik',
                'periode' => optional(optional($m->header)->periode)->nama_periode ?? '-',
                'status'  => $m->status_approval,
                'url'     => route('penilai.approval-skp-tendik.show', $m->skp_header_id),
                'sort'    => $m->updated_at ?? $m->created_at,
            ]);
        }

        $approvalRows = $rows->sortByDesc(fn($r) => $r['sort'])->take(5)->values();

        return view('dekan.dashboard', compact(
            'user',
            'totalDosen',
            'totalTendik',
            'totalProdi',
            'totalPending',
            'approvalRows'
        ));
    }

    public function dataProdi()
    {
        return view('dekan.data-prodi');
    }

    public function laporanFakultas()
    {
        return view('dekan.laporan');
    }

    // =========================
    // PERSETUJUAN SKP
    // =========================

    // Halaman daftar pengajuan SKP
    public function persetujuanSkp(Request $request)
    {
        $user = Auth::user();
        $myRoles = $user->getRoleNames();
        
        $pengajuan = SkpHeader::with(['user.profile', 'periode', 'approver.profile'])
            ->whereHas('user.roles', function($q) {
                $q->where('kategori', 'utama');
            })
            ->where(function($q) use ($user, $myRoles) {
                // Tampilkan jika user adalah salah satu dari 4 level penilai struktural (Tanpa status Selesai)
                $q->where(function($sub1) use ($user) {
                    $sub1->where(function($sub2) use ($user) {
                        $sub2->whereIn('pejabat_penilai_id', [$user->id])
                             ->orWhereIn('atasan_pejabat_penilai_id', [$user->id])
                             ->orWhereIn('penilai_utama_id', [$user->id])
                             ->orWhereIn('penilai_akhir_id', [$user->id]);
                    })
                    ->whereIn('status', ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan', 'Disetujui']);
                })
                // ATAU Tampilkan jika user adalah penilai per kegiatan (Termasuk status Selesai untuk history)
                ->orWhere(function($sub1) use ($myRoles) {
                    $sub1->whereExists(function ($sub) use ($myRoles) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('master_skp_tendiks')
                            ->whereColumn('master_skp_tendiks.user_id', 'skp_headers.user_id')
                            ->whereIn('master_skp_tendiks.penilai_role', $myRoles);
                    })
                    ->whereIn('status', ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan', 'Disetujui', 'Selesai']);
                });
            })
            ->orderByRaw("FIELD(status, 'Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan', 'Disetujui', 'Selesai')")
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('dekan.persetujuan.index', compact('pengajuan'));
    }

    // Halaman detail untuk melihat target SKP sebelum approve
    public function showSkp($id)
    {
        $user = Auth::user();
        $myRoles = $user->getRoleNames();

        // Cari header pengajuan
        $header = SkpHeader::with([
                'user.profile', 
                'user.roles',
                'pejabatPenilai.profile',
                'pejabatPenilai.roles',
                'penilaiUtama.profile', 
                'penilaiUtama.roles',
                'penilaiAkhir.profile',
                'penilaiAkhir.roles',
                'periode', 
                'perilakuIndicators', 
                'approver.profile'
            ])
            ->where('id', $id)
            ->where(function($q) use ($user, $myRoles) {
                $q->where('pejabat_penilai_id', $user->id)
                  ->orWhere('atasan_pejabat_penilai_id', $user->id)
                  ->orWhere('penilai_utama_id', $user->id)
                  ->orWhere('penilai_akhir_id', $user->id)
                  ->orWhereExists(function ($sub) use ($myRoles) {
                      $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('master_skp_tendiks')
                          ->whereColumn('master_skp_tendiks.user_id', 'skp_headers.user_id')
                          ->whereIn('master_skp_tendiks.penilai_role', $myRoles);
                  });
            })
            ->first();

        if (!$header) {
            return redirect()->route('dekan.persetujuan-skp')->with('info', 'SKP tidak ditemukan atau Anda tidak lagi memiliki akses (misal: kegiatan yang perlu Anda nilai telah dihapus).');
        }

        // 1. Ambil ID Periode secara eksplisit langsung dari kolomnya
        $periodeId = $header->periode_skp_id;

        // Ambil data Penilaian Perilaku secara manual
        $penilaianPerilaku = \App\Models\PenilaianPerilaku::where('user_id', $header->user_id)
            ->where('periode_skp_id', $periodeId)
            ->first();

        // 2. Tambahkan withoutGlobalScopes() untuk membypass filter otomatis dari Model
        //    sehingga penilai (siapapun rolenya) tetap bisa melihat data user yang dinilainya.
        $skpDosen = SkpDosen::withoutGlobalScopes()
                            ->where('user_id', $header->user_id)
                            ->where('periode_skp_id', $periodeId)
                            ->get();

        $skpTendik = SkpTendik::withoutGlobalScopes()
                              ->where('user_id', $header->user_id)
                              ->where('periode_skp_id', $periodeId)
                              ->with('details')
                              ->get();

        // --- TAMBAHAN UNTUK PENYELARASAN DENGAN VIEW SUPER ADMIN ---
        $activities = \App\Models\MasterSkpTendik::with('approver.profile')
                            ->where('skp_header_id', $header->id)
                            ->get();
        $tugasTambahan = \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)
                            ->get();
        $indicators = $header->perilakuIndicators->groupBy('aspek');
        // -----------------------------------------------------------

        // ✅ Data untuk editing Master (Tabel 1)
        $roleBidang = \Spatie\Permission\Models\Role::whereIn('kategori', ['utama', 'tendik'])->pluck('name')->toArray();
        $kamusBidang = \App\Models\DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))->unique()->sort()->values();
        
        $userJabatan = $header->user->userProfile->jabatan_pekerjaan ?? '';

        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
            ->where(function($q) use ($requestedRoles) {
                foreach($requestedRoles as $r) {
                    $q->orWhere('name', 'like', $r . '%');
                }
            })
            ->get();

        // ✅ Filter bidangs to only those the TARGET user actually has
        $targetUser = $header->user;
        $userRoles = $targetUser->getRoleNames()->toArray();
        $userBidangs = collect($bidangList)->filter(fn($b) => in_array($b, $userRoles))->values();
        $userJabatan = $userBidangs->first() ?? '';
        if ($userBidangs->isEmpty()) $userBidangs = $bidangList;

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

        return view('dekan.persetujuan.show', compact('header', 'skpDosen', 'skpTendik', 'penilaianPerilaku', 'activities', 'tugasTambahan', 'indicators', 'masterBahanKuliah', 'masterKaryaIlmiah', 'masterAbdimas', 'bidangList', 'penilaiRoles', 'userBidangs', 'userJabatan'));
        }

    // Approve SKP
    public function approveSkp(Request $request, $id)
    {
        $user = Auth::user();
        $header = SkpHeader::where('id', $id)->firstOrFail();
        $target_user_id = $header->user_id;

        // Guard penilaian: blokir submit NILAI lewat batas_penilaian.
        // Kecuali sub-handler approval tupoksi (fase rencana, pakai batas_pengisian).
        $isTupoksiAction = $request->hasAny(['approve_tupoksi', 'reject_tupoksi', 'approve_all_tupoksi', 'reject_all_tupoksi']);
        if (!$isTupoksiAction && $resp = $this->pastikanPenilaianBuka($header->periode_skp_id)) return $resp;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // --- HANDLER UNTUK INDIVIDUAL ACTIVITY APPROVAL (Level 3) ---
            if ($request->has('approve_tupoksi')) {
                $activity = \App\Models\MasterSkpTendik::where('id', $request->approve_tupoksi)
                    ->where('skp_header_id', $header->id)
                    ->firstOrFail();
                
                $activity->update([
                    'status_approval' => 'approved',
                    'approved_by'     => $user->id,
                    'approved_at'     => now()
                ]);
                
                \Illuminate\Support\Facades\DB::commit();
                return back()->with('success', 'Kegiatan "' . $activity->tupoksi . '" berhasil diverifikasi.');
            }

            // --- HANDLER UNTUK INDIVIDUAL ACTIVITY REJECTION (Level 3 - MENOLAK) ---
            if ($request->has('reject_tupoksi')) {
                $activity = \App\Models\MasterSkpTendik::where('id', $request->reject_tupoksi)
                    ->where('skp_header_id', $header->id)
                    ->firstOrFail();
                
                $tupoksiName = $activity->tupoksi;

                // 1. Hapus dari Tabel 1 (Master)
                $activity->delete();

                // 2. Hapus dari Tabel 2 (Tugas Tambahan) yang tersinkron
                \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)
                    ->where('kegiatan', $tupoksiName)
                    ->delete();
                
                \Illuminate\Support\Facades\DB::commit();
                return back()->with('success', 'Kegiatan "' . $tupoksiName . '" telah ditolak dan dihapus.');
            }

            // --- HANDLER UNTUK BATCH APPROVAL SELURUH TUPOKSI (Level 1 & 2) ---
            if ($request->has('approve_all_tupoksi')) {
                \App\Models\MasterSkpTendik::where('user_id', $target_user_id)
                    ->where('status_approval', '!=', 'approved')
                    ->update([
                        'status_approval' => 'approved',
                        'approved_by'     => $user->id,
                        'approved_at'     => now()
                    ]);
                
                \Illuminate\Support\Facades\DB::commit();
                return back()->with('success', 'Seluruh kegiatan tupoksi berhasil disetujui.');
            }

            // --- HANDLER UNTUK BATCH REJECTION SELURUH TUPOKSI (Level 1 & 2 - MENOLAK) ---
            if ($request->has('reject_all_tupoksi')) {
                // 1. Hapus dari Tabel 1 (Master)
                \App\Models\MasterSkpTendik::where('user_id', $target_user_id)
                    ->where('skp_header_id', $header->id)
                    ->delete();

                // 2. Hapus dari Tabel 2 (Tugas Tambahan) yang tersinkron
                \App\Models\SkpTugasTambahan::where('user_id', $target_user_id)
                    ->where('periode_skp_id', $header->periode_skp_id)
                    ->delete();
                
                \Illuminate\Support\Facades\DB::commit();
                return back()->with('success', 'Seluruh kegiatan tupoksi telah ditolak dan dihapus.');
            }

            // 1. SIMPAN UPDATE TUPOKSI (Jika ada perubahan oleh pimpinan)
            if ($request->has('tupoksi_updates')) {
                foreach ($request->tupoksi_updates as $act_id => $data) {
                    $activity = \App\Models\MasterSkpTendik::find($act_id);
                    if ($activity && $activity->user_id == $target_user_id) {
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

            // 1.1 SIMPAN PERILAKU KERJA (Tabel 4)
            if ($request->has('perilaku')) {
                $submittedIds = [];
                $rekapAspek = [];
                foreach ($request->perilaku as $aspek => $items) {
                    // Filter item valid (indikator tidak kosong)
                    $validItems = array_filter($items, fn($i) => !empty(trim($i['indikator'] ?? '')));
                    $count = count($validItems);
                    $bobot = ($count > 0) ? (100 / $count) : 0;
                    $totalNilai = 0;

                    foreach ($validItems as $item) {
                        $indicator = $header->perilakuIndicators()->updateOrCreate(
                            ['id' => $item['id'] ?? null],
                            [
                                'aspek' => $aspek,
                                'indikator' => trim($item['indikator']),
                                'nilai' => $item['nilai'] ?? 0,
                                'bobot_persen' => $bobot,
                                'is_from_admin' => filter_var($item['is_from_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                            ]
                        );
                        $submittedIds[] = $indicator->id;
                        $totalNilai += (float) ($item['nilai'] ?? 0);
                    }
                    $rekapAspek[$aspek] = $count > 0 ? ($totalNilai / $count) : 0;
                }
                // Hapus indikator yang tidak ada dalam draf terbaru (yang dihapus user)
                $header->perilakuIndicators()->whereNotIn('id', $submittedIds)->delete();

                // Update Ringkasan Perilaku
                $mapAspek = [
                    'Orientasi Pelayanan' => 'orientasi_pelayanan', 'Integritas' => 'integritas',
                    'Komitmen' => 'komitmen', 'Disiplin' => 'disiplin',
                    'Kerjasama' => 'kerjasama', 'Kepemimpinan' => 'kepemimpinan'
                ];
                $updatePerilaku = [];
                foreach ($mapAspek as $label => $column) {
                    $updatePerilaku[$column] = $rekapAspek[$label] ?? 0;
                }
                
                \App\Models\PenilaianPerilaku::updateOrCreate(
                    ['user_id' => $target_user_id, 'periode_skp_id' => $header->periode_skp_id],
                    $updatePerilaku
                );
            }

            // 2. SIMPAN TUPOKSI BARU (Jika ditambahkan oleh pimpinan)
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

                    // B. Simpan ke Operasional (Tugas Tambahan)
                    \App\Models\SkpTugasTambahan::updateOrCreate(
                        [
                            'user_id'         => $target_user_id,
                            'periode_skp_id'  => $header->periode_skp_id,
                            'skp_header_id'   => $header->id,
                            'kegiatan'        => trim($data['tupoksi']),
                        ],
                        [
                            'jenis'           => 'Tugas Tambahan',
                            'ak'              => 1,
                        ]
                    );
                }
            }

            // 3. LOGIKA APPROVAL SKP
            $isPenilaiUtama = ($header->penilai_utama_id == $user->id);
            $isPenilaiAkhir = ($header->penilai_akhir_id == $user->id);

            $msg = 'Perubahan berhasil disimpan.';

            if ($request->has('action') && $request->action === 'approve') {
                // Auto-approve all pending tasks in Tabel 1
                \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)
                    ->where('status_approval', '!=', 'approved')
                    ->update([
                        'status_approval' => 'approved',
                        'approved_by'     => $user->id,
                        'approved_at'     => now()
                    ]);

                // Logika Bypass & Prioritas: Jika penilai akhir (Dekan/Rektor) ingin langsung approve
                if ($isPenilaiAkhir && in_array($header->status, ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan'])) {
                    $header->update([
                        'status' => 'Disetujui',
                        'catatan_penilai' => $request->catatan,
                        'approved_by_id' => $user->id,
                        'is_target_approved_atasan' => true,
                        'is_target_approved_penilai' => true, // Bypass penilai utama jika belum
                    ]);
                    $msg = 'SKP berhasil disetujui secara Final.';
                } 
                // Logika Standar: Penilai Utama
                elseif ($isPenilaiUtama && in_array($header->status, ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan'])) {
                    // Jika dia merangkap Penilai Akhir (seharusnya sudah kena blok atas, tapi jaga-jaga)
                    if ($header->penilai_akhir_id == $user->id) {
                        $header->update([
                            'status' => 'Disetujui',
                            'catatan_penilai' => $request->catatan,
                            'approved_by_id' => $user->id,
                            'is_target_approved_atasan' => true,
                            'is_target_approved_penilai' => true,
                        ]);
                        $msg = 'SKP berhasil disetujui secara Final.';
                    } else {
                        // Update status ke Pending Akhir (untuk Atasan) jika saat ini masih di draf/utama
                        $newStatus = ($header->status === 'Pending Akhir') ? 'Pending Akhir' : 'Pending Akhir';
                        
                        $header->update([
                            'status' => 'Pending Akhir',
                            'catatan_penilai' => $request->catatan,
                            'is_target_approved_penilai' => true,
                        ]);
                        $msg = 'SKP berhasil disetujui dan diteruskan ke Atasan Pejabat Penilai.';
                    }
                } else {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return back()->with('error', 'Anda tidak memiliki hak untuk menyetujui SKP ini pada tahap sekarang atau SKP sudah disetujui.');
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            
            // Redirect ke halaman show agar pimpinan bisa melihat perubahan status di Monitoring
            return redirect()->route('dekan.persetujuan-skp.show', $id)->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // Tolak / Kembalikan ke Draft (Opsional disembunyikan di view, tapi tetap ada jika dibutuhkan API/System)
    public function tolakSkp(Request $request, $id)
    {
        $user = Auth::user();
        $header = SkpHeader::where('id', $id)->firstOrFail();

        // Pastikan yang menolak adalah penilai yang relevan
        if ($header->penilai_utama_id != $user->id && $header->penilai_akhir_id != $user->id) {
             abort(403);
        }

        if ($resp = $this->pastikanPenilaianBuka($header->periode_skp_id)) return $resp;

        $header->update([
            'status' => 'Draft',
            'catatan_penilai' => $request->catatan
        ]);

        return redirect()->route('dekan.persetujuan-skp')->with('info', 'SKP dikembalikan ke Draft untuk diperbaiki oleh Super Admin.');
    }
}
