<?php

namespace App\Support;

use App\Models\PeriodeSkp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Distribusi riil pegawai (SKP Kualitatif) per periode — dibekukan saat periode ditutup.
 *
 * Sumber data: skp_headers milik periode (SKP kualitatif saja, COUNT DISTINCT user_id,
 * predikat_kinerja terisi), dipetakan lewat config('skp.predikat_to_chart_map').
 *
 * Hasil disimpan (dibekukan) di periode_skp.distribusi_riil (JSON):
 *   ['total' => N, 'data' => [kategori => count], 'labels' => [kategori...], 'order' => [predikat...]]
 * beserta periode_skp.distribusi_riil_dihitung_pada (timestamp perhitungan).
 */
class DistribusiRiil
{
    public static function labels(): array
    {
        return ['Sangat Kurang', 'Kurang/Misconduct', 'Butuh Perbaikan', 'Baik', 'Sangat Baik'];
    }

    /**
     * Periode dianggap ditutup jika status 'selesai' ATAU
     * (tanggal selesai sudah lewat DAN penilaian tidak terbuka lagi).
     * Tidak mengubah isPenilaianTerbuka()/status DB — hanya membaca.
     */
    public static function isDitutup(PeriodeSkp $periode): bool
    {
        if ($periode->status === 'selesai') {
            return true;
        }

        if (!$periode->selesai || $periode->selesai->startOfDay()->gt(now()->startOfDay())) {
            return false; // belum lewat tanggal selesai
        }

        // Tanggal selesai sudah lewat; penutupan efektif menunggu batas penilaian lewat juga
        // (batas_penilaian NULL = dianggap terbuka selamanya → tidak dianggap ditutup).
        if (!$periode->batas_penilaian) {
            return false;
        }

        return ! $periode->isPenilaianTerbuka();
    }

    /**
     * Hitung distribusi riil untuk satu periode (tanpa menyimpan).
     * Return ['total' => N, 'data' => [kategori => count]].
     */
    public static function hitung(PeriodeSkp $periode): array
    {
        $map = config('skp.predikat_to_chart_map');

        $rows = DB::table('skp_headers')
            ->join('skp_kualitatifs', 'skp_kualitatifs.skp_header_id', '=', 'skp_headers.id')
            ->where('skp_headers.periode_skp_id', $periode->id)
            ->whereNotNull('skp_headers.predikat_kinerja')
            ->distinct()
            ->get(['skp_headers.user_id', 'skp_headers.predikat_kinerja']);

        $data = array_fill_keys(self::labels(), 0);
        $total = 0;

        foreach ($rows as $row) {
            $kategori = $map[$row->predikat_kinerja] ?? null;
            if ($kategori === null) {
                continue; // predikat tidak terpetakan → diabaikan
            }
            // Config memetakan SANGAT BAIK ke 'Istimewa' (key kurva acuan Cache);
            // samakan dengan label chart 'Sangat Baik'.
            if ($kategori === 'Istimewa') {
                $kategori = 'Sangat Baik';
            }
            $data[$kategori]++;
            $total++;
        }

        return ['total' => $total, 'data' => $data];
    }

    /**
     * Hitung + simpan (bekukan) distribusi riil periode. Menimpa hasil lama.
     */
    public static function freeze(PeriodeSkp $periode): array
    {
        $hasil = self::hitung($periode);

        $periode->forceFill([
            'distribusi_riil' => json_encode([
                'total' => $hasil['total'],
                'data' => $hasil['data'],
                'labels' => self::labels(),
            ]),
            'distribusi_riil_dihitung_pada' => now(),
        ])->saveQuietly();

        return $hasil;
    }

    /**
     * Hapus bekuan distribusi riil periode (dipakai saat periode dibuka kembali).
     */
    public static function unfreeze(PeriodeSkp $periode): void
    {
        $periode->forceFill([
            'distribusi_riil' => null,
            'distribusi_riil_dihitung_pada' => null,
        ])->saveQuietly();
    }

    /**
     * Lazy-freeze: jika periode sudah ditutup tetapi bekuan belum ada,
     * hitung + simpan sekali dengan lock (aman terhadap request bersamaan).
     * Return array bekuan atau null jika periode belum ditutup.
     */
    public static function beku(PeriodeSkp $periode): ?array
    {
        $beku = $periode->distribusi_riil;

        if (is_string($beku)) {
            $beku = json_decode($beku, true);
        }

        if ($beku !== null) {
            return $beku;
        }

        if (!self::isDitutup($periode)) {
            return null;
        }

        $lock = Cache::lock("distribusi_riil_periode_{$periode->id}", 30);

        try {
            // Cek ulang di dalam lock (request lain mungkin sudah membekukan).
            $lock->block(10);
            $periode->refresh();
            $beku = $periode->distribusi_riil;
            if (is_string($beku)) {
                $beku = json_decode($beku, true);
            }
            if ($beku === null) {
                $beku = self::freeze($periode);
            }
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Lock gagal: hitung tanpa menyimpan agar halaman tetap tampil.
            $hasil = self::hitung($periode);
            $beku = ['total' => $hasil['total'], 'data' => $hasil['data'], 'labels' => self::labels()];
        } finally {
            optional($lock)->release();
        }

        return $beku;
    }
}
