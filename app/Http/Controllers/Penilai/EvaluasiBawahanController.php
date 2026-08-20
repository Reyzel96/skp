<?php

namespace App\Http\Controllers\Penilai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SkpHeader, SkpTendik, SkpDosen, PenilaianPerilaku, SkpTugasTambahan};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluasiBawahanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']) || 
                        $user->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));

        $query = SkpHeader::whereIn('status', ['Diajukan', 'Review Akhir', 'Disetujui', 'Selesai', 'Ditolak', 'Revisi'])
            ->with(['user.profile', 'periode']);

        // Jika bukan pimpinan, hanya tampilkan yang dia terlibat
        if (!$isBypassRole) {
            $query->where(function($q) use ($user) {
                $q->where('pejabat_penilai_id', $user->id)
                  ->orWhere('atasan_pejabat_penilai_id', $user->id)
                  ->orWhere('penilai_utama_id', $user->id)
                  ->orWhere('penilai_akhir_id', $user->id);
            });
        }
        
        $bawahanHeaders = $query->orderBy('updated_at', 'desc')->get();

        // Categorize for Tabs
        // Tugas Review Anda: Semua yang belum Selesai (Final)
        $pendingHeaders = $bawahanHeaders->filter(fn($h) => $h->status !== 'Selesai');

        // Riwayat Penilaian: Semua yang sudah Selesai (Final)
        $historyHeaders = $bawahanHeaders->filter(fn($h) => $h->status === 'Selesai');

        return view('penilai.evaluasi.index', compact('pendingHeaders', 'historyHeaders', 'isBypassRole'));
    }

    public function show($header_id)
    {
        $penilai = Auth::user();
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
                'perilakuIndicators'
            ])->findOrFail($header_id);
        
        $isBypassRole = $penilai->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']) || 
                        $penilai->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = ($header->penilai_utama_id == $penilai->id || $header->penilai_akhir_id == $penilai->id || $header->pejabat_penilai_id == $penilai->id || $header->atasan_pejabat_penilai_id == $penilai->id);

        // Pastikan user yang login adalah salah satu penilai di SKP ini ATAU pimpinan (Bypass/Monitor)
        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat SKP ini.');
        }

        $bawahan = $header->user;
        $periodeId = $header->periode_skp_id;

        $isTendik = SkpTendik::where('user_id', $bawahan->id)->where('periode_skp_id', $periodeId)->exists();
        $isKualitatif = \App\Models\SkpKualitatif::where('skp_header_id', $header->id)->exists();

        if ($isKualitatif) {
            $skpKualitatif = \App\Models\SkpKualitatif::where('skp_header_id', $header->id)->first();
            $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $header->id)->get();
            $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $header->id)->get();
            $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $header->id)->get();
            $realisasis = \App\Models\SkpKualitatifRealisasi::where('skp_header_id', $header->id)->get();
            $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $header->id)->where('jenis', 'utama')->with('acuanParent.children')->get();
            $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();

            $distribusiNilai = \Illuminate\Support\Facades\Cache::get('distribusi_nilai_skp', [
                'Istimewa' => [0, 1, 20, 158, 542],
                'Baik' => [83, 125, 250, 458, 83],
                'Butuh Perbaikan' => [125, 167, 417, 167, 125],
                'Kurang/Misconduct' => [83, 458, 250, 125, 83],
                'Sangat Kurang' => [542, 292, 125, 42, 0]
            ]);
            $hasilKerja = \Illuminate\Support\Facades\Cache::get('hasil_kerja_skp', [3, 2, 1]);

            return view('penilai.evaluasi.show-kualitatif', compact(
                'header', 'penilai', 'bawahan', 'skpKualitatif', 'tambahans', 
                'perilakus', 'lampirans', 'realisasis', 'distribusiNilai', 'hasilKerja', 
                'skpUtamas', 'perilakuAcuan', 'isBypassRole', 'isAssignedEvaluator'
            ));
        }
        $tugasTambahan = SkpTugasTambahan::where('skp_header_id', $header->id)->get();
        $perilaku = PenilaianPerilaku::where('user_id', $bawahan->id)->where('periode_skp_id', $periodeId)->first();
        
        $rataUtama = 0;
        if ($isTendik) {
            $skpUtama = SkpTendik::where('skp_header_id', $header->id)->with(['details', 'penilaian'])->get();
            $semuaPenilaian = collect();
            foreach($skpUtama as $induk) { if($induk->penilaian) $semuaPenilaian->push($induk->penilaian); }
            $rataUtama = $semuaPenilaian->count() > 0 ? $semuaPenilaian->avg('nilai_capaian') : 0;
            $jenisSkp = 'Tendik';
        } else {
            $skpUtama = SkpDosen::where('skp_header_id', $header->id)->with(['penilaian' => fn($q) => $q->where('user_id', $bawahan->id)])->get();
            $rataUtama = $skpUtama->whereNotNull('penilaian')->count() > 0 ? $skpUtama->avg('penilaian.nilai_capaian') : 0;
            $jenisSkp = 'Dosen';
        }

        $totalTambahan = $tugasTambahan->sum('penghitungan') ?? 0;
        $nilaiAkhirSkp = $rataUtama + $totalTambahan;
        $isLengkap = true; 

        $masterActivities = \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->get();

        return view('penilai.evaluasi.show', compact('header', 'penilai', 'bawahan', 'skpUtama', 'jenisSkp', 'perilaku', 'tugasTambahan', 'nilaiAkhirSkp', 'isLengkap', 'isBypassRole', 'isAssignedEvaluator', 'masterActivities'));
    }

    // Halaman ini untuk menampilkan detail dari SKP Tendik yang diajukan, mirip seperti halaman superadmin
    public function showTendikDetail($skp_id)
    {
        $skp = SkpTendik::with('details', 'user.profile')->findOrFail($skp_id);
        $header = SkpHeader::where('user_id', $skp->user_id)->where('periode_skp_id', $skp->periode_skp_id)->first();
        $penilai = Auth::user();
        
        $isBypassRole = $penilai->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']) || 
                        $penilai->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = ($header->penilai_utama_id == $penilai->id || $header->penilai_akhir_id == $penilai->id || $header->pejabat_penilai_id == $penilai->id || $header->atasan_pejabat_penilai_id == $penilai->id);

        // Pastikan penilai yang sedang login berhak melihat (Pimpinan bypass OK)
        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat detail SKP ini.');
        }

        return view('penilai.evaluasi.show_tendik_detail', compact('skp', 'header'));
    }

    public function approveTarget(Request $request, $header_id)
    {
        $header = SkpHeader::findOrFail($header_id);
        $user = Auth::user();

        if ($header->status === 'Draft') {
            return back()->with('error', 'Gagal! Pegawai belum melakukan finalisasi terhadap rencana SKP ini.');
        }

        $updated = false;
        $isSuperAdmin = $user->hasRole('Super Admin');

        // Handle Force Role (Bypass for Super Admin)
        if ($isSuperAdmin && $request->has('force_role')) {
            if ($request->force_role === 'penilai') {
                $header->is_target_approved_penilai = true;
                $updated = true;
            } elseif ($request->force_role === 'atasan') {
                $header->is_target_approved_atasan = true;
                $updated = true;
            }
        } else {
            // Standar ownership check
            if ($header->pejabat_penilai_id == $user->id) {
                $header->is_target_approved_penilai = true;
                $updated = true;
            }

            if ($header->atasan_pejabat_penilai_id == $user->id) {
                $header->is_target_approved_atasan = true;
                $updated = true;
            }
        }

        if (!$updated) {
            return back()->with('error', 'Anda tidak memiliki otoritas untuk menyetujui target SKP ini.');
        }

        if ($header->is_target_approved_penilai && $header->is_target_approved_atasan) {
            $header->target_approved_at = now();
            $header->status = 'Disetujui';
        }

        $header->save();
        return back()->with('success', 'Target SKP berhasil diverifikasi.');
    }

    public function verify(Request $request, $header_id)
    {
        $header = SkpHeader::findOrFail($header_id);
        $user = Auth::user();

        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']) || 
                        $user->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = ($header->penilai_utama_id == $user->id || $header->penilai_akhir_id == $user->id || $header->pejabat_penilai_id == $user->id || $header->atasan_pejabat_penilai_id == $user->id);

        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403);
        }

        if ($resp = $this->pastikanPenilaianBuka($header->periode_skp_id)) return $resp;

        $request->validate([
            'status' => 'required|string|in:Selesai,Ditolak',
            'catatan_penilai' => 'nullable|string',
            'perilaku' => 'required|array',
            'perilaku.*' => 'sometimes|array',
            'perilaku.*.*.indikator' => 'nullable|string',
            'perilaku.*.*.nilai' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // 1. Update Status SKP Header & Catatan
            $isDraftSave = $request->input('is_draft') == '1';
            $pesan = 'Perubahan evaluasi berhasil disimpan sementara.';

            if (!$isDraftSave) {
                if ($header->status === 'Diajukan' || $header->status === 'Review Akhir' || $header->status === 'Disetujui') {
                    if ($request->status == 'Selesai') {
                        // Cek jika penilai adalah Atasan Pejabat Penilai (Level 1) ATAU Penilai Akhir (Dekan/Rektor)
                        if ($header->atasan_pejabat_penilai_id == $user->id || $header->penilai_akhir_id == $user->id) {
                            $header->status = 'Selesai';
                            $pesan = 'Evaluasi Realisasi SKP telah difinalisasi.';
                        } 
                        // Cek jika penilai adalah Pejabat Penilai (Level 2)
                        elseif ($header->pejabat_penilai_id == $user->id || $header->penilai_utama_id == $user->id) {
                            $header->status = 'Review Akhir';
                            $pesan = 'Realisasi SKP disetujui dan diteruskan ke Atasan Pejabat Penilai.';
                        }
                    } else {
                        $header->status = 'Ditolak';
                        $pesan = 'Evaluasi Realisasi SKP ditolak dan dikembalikan.';
                    }
                }
            }

            $header->catatan_penilai = $request->catatan_penilai;
            $header->save();

            // 2. Simpan Nilai Perilaku yang diinput penilai
            $perilakuData = $request->perilaku;
            foreach ($perilakuData as $aspek => $items) {
                foreach ($items as $item) {
                    $nilaiInput = isset($item['nilai']) ? (float)$item['nilai'] : 0;
                    $indikatorText = trim($item['indikator'] ?? '');
                    $indicatorId = $item['id'] ?? null;

                    // Skip jika tidak ada ID dan teks indikator kosong (mencegah data sampah)
                    if (!$indicatorId && empty($indikatorText)) continue;

                    if ($indicatorId) {
                        \App\Models\SkpPerilakuIndicator::where('id', $indicatorId)
                            ->where('skp_header_id', $header->id)
                            ->update([
                                'indikator' => $indikatorText,
                                'nilai' => $nilaiInput,
                                'is_realized' => true,
                                'is_from_admin' => filter_var($item['is_from_admin'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
                            ]);
                    } else {
                        \App\Models\SkpPerilakuIndicator::create([
                            'skp_header_id' => $header->id,
                            'aspek'         => $aspek,
                            'indikator'     => $indikatorText,
                            'nilai'         => $nilaiInput,
                            'bobot_persen'  => 0, // Akan dihitung ulang jika pegawai mengubahnya
                            'is_realized'   => true,
                            'is_from_admin' => true
                        ]);
                    }
                }
            }

            // 3. Rekapitulasi ke PenilaianPerilaku & Update Bobot (Sync dari DB)
            $mapAspek = [
                'Orientasi Pelayanan' => 'orientasi_pelayanan', 
                'Integritas'          => 'integritas', 
                'Komitmen'            => 'komitmen', 
                'Disiplin'            => 'disiplin', 
                'Kerjasama'           => 'kerjasama', 
                'Kepemimpinan'        => 'kepemimpinan'
            ];
            
            $updateSummaryData = [];
            foreach ($mapAspek as $label => $column) {
                $aspekItems = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)
                    ->where('aspek', $label)
                    ->get();
                
                if ($aspekItems->count() > 0) {
                    $avgScore = 0;
                    foreach ($aspekItems as $item) {
                        $avgScore += ($item->nilai * ($item->bobot_persen / 100));
                    }
                    $updateSummaryData[$column] = $avgScore;
                } else {
                    $updateSummaryData[$column] = 0;
                }
            }
            
            if (!empty($updateSummaryData)) {
                \App\Models\PenilaianPerilaku::updateOrCreate(
                    ['user_id' => $header->user_id, 'periode_skp_id' => $header->periode_skp_id],
                    $updateSummaryData
                );
            }

            DB::commit();
            return back()->with('success', $pesan);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    
    public function verifyKualitatif(Request $request, $header_id)
    {
        $header = SkpHeader::findOrFail($header_id);
        $user = Auth::user();

        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']) || 
                        $user->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = ($header->penilai_utama_id == $user->id || $header->penilai_akhir_id == $user->id || $header->pejabat_penilai_id == $user->id || $header->atasan_pejabat_penilai_id == $user->id);

        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403);
        }

        if ($resp = $this->pastikanPenilaianBuka($header->periode_skp_id)) return $resp;

        $request->validate([
            'status' => 'required|string|in:Selesai,Ditolak,Draft',
            'catatan_penilai' => 'nullable|string',
            'hasil_kerja' => 'nullable|array',
            'umpan_balik' => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            $isDraftSave = $request->input('status') == 'Draft';
            $pesan = 'Perubahan evaluasi (Kualitatif) berhasil disimpan sementara.';

            if (!$isDraftSave) {
                if (in_array($header->status, ['Diajukan', 'Review Akhir', 'Disetujui'])) {
                    if ($request->status == 'Selesai') {
                        if ($header->atasan_pejabat_penilai_id == $user->id || $header->penilai_akhir_id == $user->id) {
                            $header->status = 'Selesai';
                            $pesan = 'Evaluasi Realisasi SKP Kualitatif telah difinalisasi.';
                        } elseif ($header->pejabat_penilai_id == $user->id || $header->penilai_utama_id == $user->id) {
                            $header->status = 'Review Akhir';
                            $pesan = 'Realisasi SKP Kualitatif disetujui dan diteruskan ke Atasan Pejabat Penilai.';
                        }
                    } else {
                        $header->status = 'Ditolak';
                        $pesan = 'Evaluasi Realisasi SKP ditolak dan dikembalikan.';
                    }
                }
            }

            $header->catatan_penilai = $request->catatan_penilai;
            $header->save();

            if ($request->has('hasil_kerja')) {
                foreach($request->hasil_kerja as $type_id => $val) {
                    if (str_starts_with($type_id, 'utama_')) {
                        $id = str_replace('utama_', '', $type_id);
                        \App\Models\SkpKualitatif::where('id', $id)->update(['hasil_kerja' => $val]);
                    } elseif (str_starts_with($type_id, 'tambahan_')) {
                        $id = str_replace('tambahan_', '', $type_id);
                        \App\Models\SkpKualitatifTugasTambahan::where('id', $id)->update(['hasil_kerja' => $val]);
                    } elseif (str_starts_with($type_id, 'perilaku_')) {
                        $id = str_replace('perilaku_', '', $type_id);
                        \App\Models\SkpKualitatifPerilaku::where('id', $id)->update(['hasil_kerja' => $val]);
                    }
                }
            }

            if ($request->has('umpan_balik')) {
                foreach($request->umpan_balik as $type_id => $val) {
                    if (str_starts_with($type_id, 'utama_')) {
                        $id = str_replace('utama_', '', $type_id);
                        \App\Models\SkpKualitatif::where('id', $id)->update(['umpan_balik' => $val]);
                    } elseif (str_starts_with($type_id, 'tambahan_')) {
                        $id = str_replace('tambahan_', '', $type_id);
                        \App\Models\SkpKualitatifTugasTambahan::where('id', $id)->update(['umpan_balik' => $val]);
                    } elseif (str_starts_with($type_id, 'perilaku_')) {
                        $id = str_replace('perilaku_', '', $type_id);
                        \App\Models\SkpKualitatifPerilaku::where('id', $id)->update(['umpan_balik' => $val]);
                    }
                }
            }

            // Hitung & simpan predikat_kinerja + capaian_kinerja_organisasi otomatis
            $this->calculateAndSavePredikat($header->id);

            DB::commit();
            return back()->with('success', $pesan);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function downloadBukti(Request $request)
    {
        $path = $request->query('path');
        if (!$path) abort(404);

        // Security check: ensure path is within skp-tendik directory
        if (!str_starts_with($path, 'skp-tendik/')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            abort(404, 'File bukti tidak ditemukan.');
        }

        return response()->file($fullPath);
    }

    /**
     * Hitung predikat kinerja dari hasil_kerja items dan simpan ke skp_headers.
     * Hanya dihitung jika SEMUA item (Utama, Tambahan, Perilaku) sudah terisi.
     */
    private function calculateAndSavePredikat(int $headerId): void
    {
        $header = SkpHeader::findOrFail($headerId);

        $utamaItems = \App\Models\SkpKualitatif::where('skp_header_id', $headerId)->get();
        $tambahanItems = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $headerId)->get();
        $perilakuItems = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $headerId)->get();

        $totalItems = $utamaItems->count() + $tambahanItems->count() + $perilakuItems->count();

        if ($totalItems === 0) {
            $header->update([
                'predikat_kinerja' => null,
                'capaian_kinerja_organisasi' => null,
            ]);
            return;
        }

        $filledItems = $utamaItems->whereNotNull('hasil_kerja')->where('hasil_kerja', '!=', '')->count()
            + $tambahanItems->whereNotNull('hasil_kerja')->where('hasil_kerja', '!=', '')->count()
            + $perilakuItems->whereNotNull('hasil_kerja')->where('hasil_kerja', '!=', '')->count();

        if ($filledItems < $totalItems) {
            $header->update([
                'predikat_kinerja' => null,
                'capaian_kinerja_organisasi' => null,
            ]);
            return;
        }

        $sumUtama = 0;
        foreach ($utamaItems as $item) {
            $sumUtama += $this->hasilKerjaToScore($item->hasil_kerja);
        }
        $avgUtama = $utamaItems->count() > 0 ? $sumUtama / $utamaItems->count() : 0;

        $sumPerilaku = 0;
        foreach ($perilakuItems as $item) {
            $sumPerilaku += $this->hasilKerjaToScore($item->hasil_kerja);
        }
        $avgPerilaku = $perilakuItems->count() > 0 ? $sumPerilaku / $perilakuItems->count() : 0;

        $ratingH = $avgUtama >= 2.5 ? 'DIATAS EKSPEKTASI' : ($avgUtama >= 1.5 ? 'SESUAI EKSPEKTASI' : 'DIBAWAH EKSPEKTASI');
        $ratingP = $avgPerilaku >= 2.5 ? 'DIATAS EKSPEKTASI' : ($avgPerilaku >= 1.5 ? 'SESUAI EKSPEKTASI' : 'DIBAWAH EKSPEKTASI');

        $predikat = $this->calculatePredikatFromRatings($ratingH, $ratingP);

        $chartCategory = config('skp.predikat_to_chart_map')[$predikat] ?? null;

        $header->update([
            'predikat_kinerja' => $predikat,
            'capaian_kinerja_organisasi' => $chartCategory,
        ]);
    }

    private function hasilKerjaToScore(?string $hasilKerja): int
    {
        return match ($hasilKerja) {
            'Diatas Ekspektasi' => 3,
            'Sesuai Ekspektasi' => 2,
            'Dibawah Ekspektasi' => 1,
            default => 0,
        };
    }

    private function calculatePredikatFromRatings(string $ratingH, string $ratingP): string
    {
        if ($ratingH === 'DIATAS EKSPEKTASI') {
            if ($ratingP === 'DIATAS EKSPEKTASI') return 'SANGAT BAIK';
            if ($ratingP === 'SESUAI EKSPEKTASI') return 'BAIK';
            return 'KURANG';
        }

        if ($ratingH === 'SESUAI EKSPEKTASI') {
            if ($ratingP === 'DIBAWAH EKSPEKTASI') return 'BUTUH PERBAIKAN';
            return 'BAIK';
        }

        if ($ratingP === 'DIATAS EKSPEKTASI') return 'BUTUH PERBAIKAN';
        if ($ratingP === 'SESUAI EKSPEKTASI') return 'KURANG';
        return 'SANGAT KURANG';
    }
}