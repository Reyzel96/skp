<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SkpDosenKualitatifController extends Controller
{
    public function index()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Ambil semua header SKP milik dosen ini yang memiliki kualitatif
        $headers = \App\Models\SkpHeader::with(['periode', 'pejabatPenilai.profile'])
                            ->where('user_id', $user->id)
                            ->whereHas('skpKualitatifs')
                            ->latest()
                            ->get();

        return view('user.skp-dosen-kualitatif.index', compact('headers'));
    }

    public function show($id)
    {
        $skpKualitatif = \App\Models\SkpKualitatif::with([
            'skpHeader.user.profile', 
            'skpHeader.user.roles', 
            'skpHeader.periode', 
            'skpHeader.pejabatPenilai.profile', 
            'skpHeader.pejabatPenilai.roles'
        ])->where('skp_header_id', $id)->firstOrFail();

        // Keamanan: hanya pemilik SKP, penilai/atasan yang tercatat di header,
        // atau pimpinan (Dekan/Rektorat/Rektor/Wadek/Kaprodi/Super Admin)
        // yang boleh melihat. Pola sama dengan EvaluasiBawahanController.
        // Variabel $currentUser dipakai (bukan $user) karena $user di bawah
        // adalah pemilik SKP yang dikirim ke view.
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $header = $skpKualitatif->skpHeader;
        $isOwner = $header->user_id == $currentUser->id;
        $isAssignedEvaluator = in_array($currentUser->id, array_filter([
            $header->penilai_utama_id,
            $header->penilai_akhir_id,
            $header->pejabat_penilai_id,
            $header->atasan_pejabat_penilai_id,
        ]));
        $isBypassRole = $currentUser->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2', 'Super Admin'])
            || $currentUser->roles->contains(fn ($r) => str_contains($r->name, 'Kaprodi'));
        if (!$isOwner && !$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki akses ke SKP ini.');
        }

        $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $id)->get();
        $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $id)->get();
        $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $id)->get();

        // Acuan perilaku dipakai di view (section D) untuk menentukan status
        // "wajib lampiran" berdasarkan checkbox di halaman acuan superadmin.
        // Checkbox tersebut tersimpan di field butuh_lampiran pada CHILD (sub-indikator),
        // jadi di view kita cek dari children->contains('butuh_lampiran', true) --
        // sama persis dengan pola section A. UTAMA.
        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();
        $realisasis = \App\Models\SkpKualitatifRealisasi::where('skp_header_id', $id)->get();
        $isRealisasiSaved = $realisasis->count() > 0;

        $defaultDistribusiNilai = [
            'Istimewa' => [0, 1, 20, 158, 542],
            'Baik' => [83, 125, 250, 458, 83],
            'Butuh Perbaikan' => [125, 167, 417, 167, 125],
            'Kurang/Misconduct' => [83, 458, 250, 125, 83],
            'Sangat Kurang' => [542, 292, 125, 42, 0]
        ];

        $distribusiNilai = \Illuminate\Support\Facades\Cache::get(
            \App\Models\PeriodeSkp::cacheRumusKey('distribusi_nilai_skp', $skpKualitatif->skpHeader->periode_skp_id)
        ) ?? \Illuminate\Support\Facades\Cache::get('distribusi_nilai_skp', $defaultDistribusiNilai);
        $hasilKerja = \Illuminate\Support\Facades\Cache::get(
            \App\Models\PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $skpKualitatif->skpHeader->periode_skp_id)
        ) ?? \Illuminate\Support\Facades\Cache::get('hasil_kerja_skp', [3, 2, 1]);

        $user = $skpKualitatif->skpHeader->user;
        $penilai = $skpKualitatif->skpHeader->pejabatPenilai;

        $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $id)->where('jenis', 'utama')->with('acuanParent.children')->get();

        return view('user.skp-dosen-kualitatif.show', compact('skpKualitatif', 'skpUtamas', 'tambahans', 'perilakus', 'lampirans', 'distribusiNilai', 'hasilKerja', 'user', 'penilai', 'realisasis', 'perilakuAcuan', 'isRealisasiSaved'));
    }

    public function print($id)
    {
        // Data identik dengan show(): satu sumber data, beda hanya tampilan.
        $skpKualitatif = \App\Models\SkpKualitatif::with([
            'skpHeader.user.profile',
            'skpHeader.user.roles',
            'skpHeader.periode',
            'skpHeader.pejabatPenilai.profile',
            'skpHeader.pejabatPenilai.roles'
        ])->where('skp_header_id', $id)->firstOrFail();

        // Keamanan: guard sama dengan show() — pemilik, penilai/atasan tercatat,
        // atau pimpinan (Dekan/Rektorat/Rektor/Wadek/Kaprodi/Super Admin).
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $header = $skpKualitatif->skpHeader;
        $isOwner = $header->user_id == $currentUser->id;
        $isAssignedEvaluator = in_array($currentUser->id, array_filter([
            $header->penilai_utama_id,
            $header->penilai_akhir_id,
            $header->pejabat_penilai_id,
            $header->atasan_pejabat_penilai_id,
        ]));
        $isBypassRole = $currentUser->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2', 'Super Admin'])
            || $currentUser->roles->contains(fn ($r) => str_contains($r->name, 'Kaprodi'));
        if (!$isOwner && !$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki akses ke SKP ini.');
        }

        $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $id)->get();
        $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $id)->get();
        $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $id)->get();

        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();
        $realisasis = \App\Models\SkpKualitatifRealisasi::where('skp_header_id', $id)->get();
        $isRealisasiSaved = $realisasis->count() > 0;

        $user = $skpKualitatif->skpHeader->user;
        $penilai = $skpKualitatif->skpHeader->pejabatPenilai;
        $atasan = $skpKualitatif->skpHeader->atasanPejabatPenilai;

        $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $id)->where('jenis', 'utama')->with('acuanParent.children')->get();

        // Rating/predikat dihitung di PHP (pengganti perhitungan Alpine di show).
        // Pemetaan label -> angka dari rumus superadmin (Cache), fallback Diatas=3,
        // Sesuai=2, Dibawah=1 — sama dengan layar.
        $hasilKerja = \Illuminate\Support\Facades\Cache::get(
            \App\Models\PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $header->periode_skp_id)
        ) ?? \Illuminate\Support\Facades\Cache::get('hasil_kerja_skp', [3, 2, 1]);
        $hasilKerjaMap = $hasilKerja;

        // Distribusi nilai untuk POLA DISTRIBUSI (chart) di tab evaluasi.
        $defaultDistribusiNilai = [
            'Istimewa' => [0, 1, 20, 158, 542],
            'Baik' => [83, 125, 250, 458, 83],
            'Butuh Perbaikan' => [125, 167, 417, 167, 125],
            'Kurang/Misconduct' => [83, 458, 250, 125, 83],
            'Sangat Kurang' => [542, 292, 125, 42, 0]
        ];
        $distribusiNilai = \Illuminate\Support\Facades\Cache::get(
            \App\Models\PeriodeSkp::cacheRumusKey('distribusi_nilai_skp', $header->periode_skp_id)
        ) ?? \Illuminate\Support\Facades\Cache::get('distribusi_nilai_skp', $defaultDistribusiNilai);

        // POLA DISTRIBUSI riil: dibekukan saat periode dokumen ditutup.
        // state 1 = periode belum ditutup; state 2 = ditutup, data < ambang; state 3 = tampilkan chart beku.
        $minDistribusi = (int) config('skp.distribusi_min_pegawai', 5);
        $distribusiRiil = ['state' => 1, 'data' => [0, 0, 0, 0, 0], 'total' => 0, 'min' => $minDistribusi];
        if ($periodeDokumen = $skpKualitatif->skpHeader->periode) {
            if ($beku = \App\Support\DistribusiRiil::beku($periodeDokumen)) {
                $labels = \App\Support\DistribusiRiil::labels();
                $dataArray = [];
                foreach ($labels as $lbl) {
                    $dataArray[] = (int) ($beku['data'][$lbl] ?? 0);
                }
                $distribusiRiil = [
                    'state' => ($beku['total'] ?? 0) >= $minDistribusi ? 3 : 2,
                    'data' => $dataArray,
                    'total' => (int) ($beku['total'] ?? 0),
                    'min' => $minDistribusi,
                ];
            }
        }

        $avgUtama = $this->hitungRataRata($skpUtamas, $hasilKerjaMap);
        $avgPerilaku = $this->hitungRataRataPerilaku($perilakus, $hasilKerjaMap);

        $rating = $this->kategoriRating($avgUtama);
        $ratingPerilaku = $this->kategoriRating($avgPerilaku);
        $predikatKinerja = $this->predikatKinerja($rating, $ratingPerilaku);

        return view('user.skp-dosen-kualitatif.print', compact(
            'skpKualitatif', 'skpUtamas', 'tambahans', 'perilakus', 'lampirans',
            'user', 'penilai', 'atasan', 'realisasis', 'perilakuAcuan',
            'isRealisasiSaved', 'rating', 'ratingPerilaku', 'predikatKinerja',
            'distribusiNilai', 'hasilKerja', 'distribusiRiil'
        ));
    }

    /**
     * Rata-rata skor hasil kerja untuk kumpulan target (A. UTAMA).
     * Pemetaan label -> angka: Diatas=3, Sesuai=2, Dibawah=1 (default, sama dengan layar).
     */
    private function hitungRataRata($skpUtamas, array $hasilKerjaMap): ?float
    {
        $skor = [];
        foreach ($skpUtamas as $u) {
            $val = $u->hasil_kerja ?? null;
            if ($val === null || $val === '') continue;
            $skor[] = match ($val) {
                'Diatas Ekspektasi'  => $hasilKerjaMap[0],
                'Sesuai Ekspektasi'  => $hasilKerjaMap[1],
                'Dibawah Ekspektasi' => $hasilKerjaMap[2],
                default => null,
            };
        }
        $skor = array_filter($skor, fn($s) => $s !== null);
        if (empty($skor)) return null;
        return array_sum($skor) / count($skor);
    }

    /**
     * Rata-rata skor untuk tabel PERILAKU KERJA.
     */
    private function hitungRataRataPerilaku($perilakus, array $hasilKerjaMap): ?float
    {
        return $this->hitungRataRata($perilakus, $hasilKerjaMap);
    }

    private function kategoriRating(?float $avg): string
    {
        if ($avg === null) return '';
        if ($avg >= 2.5) return 'DIATAS EKSPEKTASI';
        if ($avg >= 1.5) return 'SESUAI EKSPEKTASI';
        return 'DIBAWAH EKSPEKTASI';
    }

    private function predikatKinerja(string $h, string $p): string
    {
        if ($h === '' || $p === '') return '';
        if ($h === 'DIATAS EKSPEKTASI') {
            if ($p === 'DIATAS EKSPEKTASI') return 'SANGAT BAIK';
            if ($p === 'SESUAI EKSPEKTASI') return 'BAIK';
            return 'KURANG';
        }
        if ($h === 'SESUAI EKSPEKTASI') {
            if ($p === 'DIBAWAH EKSPEKTASI') return 'BUTUH PERBAIKAN';
            return 'BAIK';
        }
        if ($p === 'DIATAS EKSPEKTASI') return 'BUTUH PERBAIKAN';
        if ($p === 'SESUAI EKSPEKTASI') return 'KURANG';
        return 'SANGAT KURANG';
    }

    public function storeRealisasi(Request $request, $id)
    {
        $request->validate([
            'realisasi' => 'nullable|array',
            'bukti_dukung' => 'nullable|array',
            // Validasi tipe file bukti dukung: array bersarang tak tentu bentuknya
            // (utama[{child}], tambahan[{id}][], perilaku[{child}]), jadi tiap file
            // divalidasi manual via validator bawah, bukan rule wildcard di sini.
            'bukti_dukung.*' => 'nullable|array',
        ]);

        // Validasi setiap file bukti dukung (mime + ekstensi) — mencegah upload
        // file berbahaya (.php dll) ke folder public yang web-accessible.
        if ($request->hasFile('bukti_dukung')) {
            $fileRules = ['file', 'max:5120', 'mimes:pdf,xls,xlsx,doc,docx,rtf,txt,jpg,jpeg,png'];
            foreach (['utama', 'tambahan', 'perilaku'] as $section) {
                $files = $request->file("bukti_dukung.$section");
                if (!$files) continue;
                array_walk_recursive($files, function ($file) use ($fileRules, $section) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $validator = \Illuminate\Support\Facades\Validator::make(
                            ['file' => $file],
                            ['file' => $fileRules]
                        );
                        if ($validator->fails()) {
                            abort(422, "File bukti dukung ($section) tidak valid: " . $validator->errors()->first('file'));
                        }
                    }
                });
            }
        }

        $header = \App\Models\SkpHeader::find($id);

        if (!$header) {
            return redirect()->back()->with('error', 'Data SKP tidak ditemukan.');
        }

        // Keamanan: pastikan yang submit realisasi ini memang pemilik SKP-nya,
        // bukan user lain yang kebetulan tahu/menebak header_id.
        // Dicek DI LUAR try-catch supaya abort(403) tidak ikut ketangkap
        // oleh catch(\Exception) di bawah (HttpException adalah turunan Exception).
        if ($header->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403, 'Anda tidak memiliki otoritas untuk mengubah realisasi SKP ini.');
        }

        // Kunci form: realisasi tidak boleh diubah lagi kalau SKP sudah
        // masuk tahap review pimpinan atau sudah selesai dinilai.
        // Pola sama persis dengan pastikanPengisianBuka() di UserSkpController.
        if (in_array($header->status, ['Review Akhir', 'Selesai'])) {
            return redirect()->back()->with('error', 'Anda tidak bisa lagi mengubah realisasi karena SKP sedang dalam review pimpinan atau sudah selesai.');
        }

        try {
            $realisasiData = $request->input('realisasi', []);
            $buktiDukungData = $request->file('bukti_dukung', []);
            // Label lampiran kustom yang diketik dosen (per sub-indikator/realisasi).
            $labelData = $request->input('label_realisasi', []);

            // Batas panjang teks realisasi berbasis KATA (konsisten dengan batas
            // frontend di resources/js/pdf-bukti-dukung.js via App\Support\RealisasiRingkas).
            // Mencegah teks hasil ekstraksi PDF puluhan ribu kata masuk DB dan
            // merusak tampilan/print. Data lama yang sudah tersimpan tidak diubah.
            $limitRealisasi = function ($text) {
                return \App\Support\RealisasiRingkas::ringkas($text);
            };

            // Guard: jika tidak ada data realisasi di request (field terkunci/disabled tidak terkirim),
            // jangan hapus data lama — batalkan untuk mencegah kehilangan data.
            if (empty($realisasiData)) {
                return redirect()->back()->with('error', 'Tidak ada data realisasi untuk disimpan. Data sebelumnya tetap utuh.');
            }

            // Hapus (beserta file bukti dukung) HANYA untuk section yang ada dan tidak kosong di request.
            // target_type row DB memetakan langsung ke section: 'utama','tambahan','perilaku'.
            // Section yang absen di request dibiarkan utuh agar tidak kehilangan data.
            $sectionsInRequest = [];
            foreach (['utama', 'tambahan', 'perilaku'] as $section) {
                if (!empty($realisasiData[$section])) {
                    $sectionsInRequest[$section] = true;
                }
            }

            $existing = \App\Models\SkpKualitatifRealisasi::where('skp_header_id', $id)->get();
            foreach ($existing as $ex) {
                if (isset($sectionsInRequest[$ex->target_type])) {
                    if ($ex->bukti_dukung_url && file_exists(public_path($ex->bukti_dukung_url))) {
                        @unlink(public_path($ex->bukti_dukung_url));
                    }
                    $ex->delete();
                }
            }

          // Process Utama (dinamis per child/sub-indikator yang dicentang "Lampiran")
            // Struktur form sekarang: realisasi[utama][{child_id}] (1 kotak per child)
            // dan bukti_dukung[utama][{child_id}] (1 file per child).
            if (isset($realisasiData['utama']) && is_array($realisasiData['utama'])) {
                foreach ($realisasiData['utama'] as $childId => $text) {
                    // Toleransi kalau ada field lama berbent array
                    $text = is_array($text) ? (string) ($text[0] ?? '') : (string) $text;
                    $text = $limitRealisasi($text);
                    $file = $buktiDukungData['utama'][$childId] ?? null;

                    if (empty(trim($text)) && !$file) {
                        continue;
                    }

                    // Hubungkan ke parent (target_id) DAN ke child spesifik (acuan_child_id)
                    $child = \App\Models\SkpKualitatifAcuanChild::find($childId);
                    $parentId = $child?->parent_id ?? $childId;

                    $model = new \App\Models\SkpKualitatifRealisasi();
                    $model->skp_header_id = $id;
                    $model->target_id = $parentId;
                    $model->target_type = 'utama';
                    $model->acuan_child_id = $child?->id;
                    $model->realisasi = $text;
                    // Simpan label kustom dosen jika ada; kosongkan (NULL) bila tak diisi.
                    $label0 = $labelData['utama'][$childId] ?? null;
                    if (is_array($label0)) { $label0 = $label0[0] ?? null; }
                    $label0 = trim((string) ($label0 ?? ''));
                    $model->label_realisasi = ($label0 === '') ? null : $label0;

                    if ($file) {
                        $filename = time() . '_uc' . $childId . '_' . preg_replace('/[^\w.\-]+/', '_', $file->getClientOriginalName());
                        $file->move(public_path('uploads/skp_realisasi'), $filename);
                        $model->bukti_dukung_nama = $file->getClientOriginalName();
                        $model->bukti_dukung_url = 'uploads/skp_realisasi/' . $filename;
                    }
                    $model->save();
                }
            }
            
            // Process Tambahan
            if (isset($realisasiData['tambahan']) && is_array($realisasiData['tambahan'])) {
                foreach ($realisasiData['tambahan'] as $tambahanId => $texts) {
                    foreach ($texts as $index => $text) {
                        $model = new \App\Models\SkpKualitatifRealisasi();
                        $model->skp_header_id = $id;
                        $model->target_id = $tambahanId;
                        $model->target_type = 'tambahan';
                        $model->realisasi = $limitRealisasi($text);
                        
                        if (isset($buktiDukungData['tambahan'][$tambahanId][$index])) {
                            $file = $buktiDukungData['tambahan'][$tambahanId][$index];
                            $filename = time() . '_t' . $tambahanId . '_' . $index . '_' . preg_replace('/[^\w.\-]+/', '_', $file->getClientOriginalName());
                            $file->move(public_path('uploads/skp_realisasi'), $filename);
                            $model->bukti_dukung_nama = $file->getClientOriginalName();
                            $model->bukti_dukung_url = 'uploads/skp_realisasi/' . $filename;
                        }
                        $model->save();
                    }
                }
            }

            // Process Perilaku (dinamis per child/sub-indikator yang dicentang "Lampiran")
            // Struktur form sekarang: realisasi[perilaku][{child_id}] (1 kotak per child)
            // dan bukti_dukung[perilaku][{child_id}] (1 file per child).
            if (isset($realisasiData['perilaku']) && is_array($realisasiData['perilaku'])) {
                foreach ($realisasiData['perilaku'] as $childId => $text) {
                    // Toleransi kalau ada field lama berbentuk array
                    $text = is_array($text) ? (string) ($text[0] ?? '') : (string) $text;
                    $text = $limitRealisasi($text);
                    $file = $buktiDukungData['perilaku'][$childId] ?? null;

                    // Lewati baris kosong tanpa file
                    if (empty(trim($text)) && !$file) {
                        continue;
                    }

                    // Hubungkan ke perilaku (target_id) DAN ke child spesifik (acuan_child_id).
                    $child = \App\Models\SkpKualitatifAcuanChild::find($childId);
                    // Cari row perilaku (skp_kualitatif_perilakus) yang aspeknya sama dengan
                    // sasaran_kinerja parent dari child ini, supaya target_id tetap terisi benar.
                    $perilakuTargetId = null;
                    if ($child && $child->parent_id) {
                        $parent = \App\Models\SkpKualitatifAcuanParent::find($child->parent_id);
                        if ($parent) {
                            $perilakuRow = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $id)
                                ->where('aspek', $parent->sasaran_kinerja)->first();
                            $perilakuTargetId = $perilakuRow?->id ?? $childId;
                        }
                    }
                    $perilakuTargetId = $perilakuTargetId ?? $childId;

                    $model = new \App\Models\SkpKualitatifRealisasi();
                    $model->skp_header_id = $id;
                    $model->target_id = $perilakuTargetId;
                    $model->target_type = 'perilaku';
                    $model->acuan_child_id = $child?->id;
                    $model->realisasi = $text;
                    // Simpan label kustom dosen jika ada; kosongkan (NULL) bila tak diisi.
                    $label0 = $labelData['perilaku'][$childId] ?? null;
                    if (is_array($label0)) { $label0 = $label0[0] ?? null; }
                    $label0 = trim((string) ($label0 ?? ''));
                    $model->label_realisasi = ($label0 === '') ? null : $label0;

                    if ($file) {
                        $filename = time() . '_pc' . $childId . '_' . preg_replace('/[^\w.\-]+/', '_', $file->getClientOriginalName());
                        $file->move(public_path('uploads/skp_realisasi'), $filename);
                        $model->bukti_dukung_nama = $file->getClientOriginalName();
                        $model->bukti_dukung_url = 'uploads/skp_realisasi/' . $filename;
                    }
                    $model->save();
                }
            }
            
            return redirect()->back()->with('success', 'Berhasil menyimpan realisasi dan bukti dukung pada Evaluasi Pegawai.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan realisasi: ' . $e->getMessage());
        }
    }
}