<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
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
