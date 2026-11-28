<?php

namespace App\Http\Controllers;

use App\Models\SkpHeader;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Status header SKP yang TERKUNCI: target/tugas tidak boleh diubah lagi
     * (dokumen sedang dinilai pimpinan atau sudah final).
     */
    public const STATUS_HEADER_TERKUNCI = ['Review Akhir', 'Selesai', 'Disetujui'];

    /**
     * Guard bersama: cek apakah header SKP berstatus terkunci.
     * Return true bila header null ATAU statusnya termasuk terkunci.
     */
    protected static function isHeaderTerkunci(?SkpHeader $header): bool
    {
        return $header === null || in_array($header->status, self::STATUS_HEADER_TERKUNCI, true);
    }

    /**
     * Guard bersama: blokir modifikasi bila header terkunci.
     * Kembalikan redirect error (atau response sesuai $wantsJson), atau null bila aman.
     */
    protected static function tolakBilaHeaderTerkunci(?SkpHeader $header, bool $wantsJson = false)
    {
        if (!self::isHeaderTerkunci($header)) {
            return null;
        }

        $status = $header?->status ?? '-';

        if ($wantsJson) {
            return response()->json([
                'message' => "Header SKP berstatus \"{$status}\" dan tidak dapat diubah lagi."
            ], 422);
        }

        return back()->with('error',
            "Header SKP berstatus \"{$status}\" (sedang dinilai/final) dan tidak dapat diubah lagi. " .
            "Minta penilai atau Super Admin mengembalikannya ke Draft bila perlu revisi."
        );
    }

    /**
     * Guard penilaian: blokir atasan menyimpan/mengubah NILAI lewat batas_penilaian.
     * Super Admin otomatis lolos (override). Kembalikan redirect error, atau null bila aman.
     */
    protected function pastikanPenilaianBuka($periodeId)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('Super Admin')) {
            return null;
        }

        $periode = \App\Models\PeriodeSkp::find($periodeId);

        if (!$periode || !$periode->isPenilaianTerbuka()) {
            $tanggal = ($periode && $periode->batas_penilaian)
                ? $periode->batas_penilaian->format('d M Y')
                : 'belum diatur';

            return back()->with('error',
                "Periode penilaian SKP ini sudah ditutup. Batas akhir penilaian: {$tanggal}. " .
                "Hubungi Super Admin untuk membukanya kembali."
            );
        }

        return null;
    }
}
