<?php

namespace App\Http\Controllers\AdminKeuanganFakultas;

use App\Http\Controllers\Controller;

class AdminKeuanganFakultasController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $data = [
            'total_anggaran' => 5000000000,
            'realisasi' => 3250000000,
            'sisa_anggaran' => 1750000000,
            'pending_pencairan' => 15
        ];

        return view('admin-keuangan-fakultas.dashboard', compact('user', 'data'));
    }

    public function anggaran()
    {
        return view('admin-keuangan-fakultas.anggaran');
    }

    public function pencairan()
    {
        return view('admin-keuangan-fakultas.pencairan');
    }

    public function laporan()
    {
        return view('admin-keuangan-fakultas.laporan');
    }
}
