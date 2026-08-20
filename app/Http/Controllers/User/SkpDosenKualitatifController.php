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

        $defaultDistribusiNilai = [
            'Istimewa' => [0, 1, 20, 158, 542],
            'Baik' => [83, 125, 250, 458, 83],
            'Butuh Perbaikan' => [125, 167, 417, 167, 125],
            'Kurang/Misconduct' => [83, 458, 250, 125, 83],
            'Sangat Kurang' => [542, 292, 125, 42, 0]
        ];

        $distribusiNilai = \Illuminate\Support\Facades\Cache::get('distribusi_nilai_skp', $defaultDistribusiNilai);
        $hasilKerja = \Illuminate\Support\Facades\Cache::get('hasil_kerja_skp', [3, 2, 1]);

        $user = $skpKualitatif->skpHeader->user;
        $penilai = $skpKualitatif->skpHeader->pejabatPenilai;

        $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $id)->where('jenis', 'utama')->with('acuanParent.children')->get();

        return view('user.skp-dosen-kualitatif.show', compact('skpKualitatif', 'skpUtamas', 'tambahans', 'perilakus', 'lampirans', 'distribusiNilai', 'hasilKerja', 'user', 'penilai', 'realisasis', 'perilakuAcuan'));
    }

    public function storeRealisasi(Request $request, $id)
    {
        $request->validate([
            'realisasi' => 'nullable|array',
            'bukti_dukung' => 'nullable|array',
        ]);

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

            // Note: Since the UI doesn't dynamically load existing data yet, we'll append or just clear and insert.
            // Let's clear existing realisasi for this header first to prevent duplicates if they save multiple times.
            $existing = \App\Models\SkpKualitatifRealisasi::where('skp_header_id', $id)->get();
            foreach ($existing as $ex) {
                if ($ex->bukti_dukung_url && file_exists(public_path($ex->bukti_dukung_url))) {
                    @unlink(public_path($ex->bukti_dukung_url));
                }
                $ex->delete();
            }

          // Process Utama (dinamis per child/sub-indikator yang dicentang "Lampiran")
            // Struktur form sekarang: realisasi[utama][{child_id}] (1 kotak per child)
            // dan bukti_dukung[utama][{child_id}] (1 file per child).
            if (isset($realisasiData['utama']) && is_array($realisasiData['utama'])) {
                foreach ($realisasiData['utama'] as $childId => $text) {
                    // Toleransi kalau ada field lama berbent array
                    $text = is_array($text) ? (string) ($text[0] ?? '') : (string) $text;
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
                        $filename = time() . '_uc' . $childId . '_' . $file->getClientOriginalName();
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
                        $model->realisasi = $text;
                        
                        if (isset($buktiDukungData['tambahan'][$tambahanId][$index])) {
                            $file = $buktiDukungData['tambahan'][$tambahanId][$index];
                            $filename = time() . '_t' . $tambahanId . '_' . $index . '_' . $file->getClientOriginalName();
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
                        $filename = time() . '_pc' . $childId . '_' . $file->getClientOriginalName();
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