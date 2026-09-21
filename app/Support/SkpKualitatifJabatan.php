<?php

namespace App\Support;

/**
 * Sumber tunggal kalimat resmi "Melaksanakan tugas jabatan pimpinan..."
 * untuk lokasi: acuan.blade.php (statis) + create/edit.blade.php (base formula).
 * Kalimat dikonfirmasi dari dokumen master/template SKP institusi
 * ("jabatan" + "Surabaya" kapital). SUMBER TUNGGAL — jangan hardcode di view lagi.
 */
class SkpKualitatifJabatan
{
    /** Kalimat resmi tanpa suffix jabatan (master Acuan / fallback pengguna dosen). */
    public const KALIMAT_RESMI = 'Melaksanakan tugas jabatan pimpinan perguruan tinggi swasta berdasarkan Izin Menduduki Jabatan dari Rektor Universitas Muhammadiyah Surabaya';

    /**
     * Kalimat + suffix " | Jabatan X" bila pegawai menduduki jabatan.
     * Kondisi identik dengan formula JS lama di create/edit:
     * kosong / placeholder / lowercase 'dosen' → tanpa suffix.
     */
    public static function kalimatDenganJabatan(?string $jabatan): string
    {
        if ($jabatan === null || trim($jabatan) === '' || $jabatan === '......................' || mb_strtolower(trim($jabatan)) === 'dosen') {
            return self::KALIMAT_RESMI;
        }
        return self::KALIMAT_RESMI . ' | Jabatan ' . $jabatan;
    }
}
