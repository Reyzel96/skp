<?php

namespace App\Http\Controllers\AdminKeuanganFakultas;

use App\Http\Controllers\Controller;
use App\Models\{PeriodeAnggaran, ProgramKegiatan, ProgramKegiatanProdi, Lpj};

class AdminKeuanganFakultasController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // ===== STAT CARDS (query asli, tanpa hardcode) =====
        $totalPeriode     = PeriodeAnggaran::count();
        $totalProgram     = ProgramKegiatan::count();
        $usulanMenunggu   = ProgramKegiatanProdi::where('status_persetujuan', 'disetujui_wadek')->count();
        $lpjPending       = Lpj::where('status_verifikasi', 'pending')->count();

        // ===== DONUT: BREAKDOWN STATUS USULAN PROGRAM PRODI =====
        $prodiStatuses = [
            'pending', 'disetujui_dekan', 'disetujui_wadek',
            'disetujui_admin', 'dicairkan', 'selesai', 'ditolak'
        ];
        $prodiStatusCounts = [];
        foreach ($prodiStatuses as $s) {
            $prodiStatusCounts[$s] = ProgramKegiatanProdi::where('status_persetujuan', $s)->count();
        }
        $totalProdiUsulan = array_sum($prodiStatusCounts);

        // ===== TABEL: USULAN MENUNGGU ADMIN KEUANGAN (5 terbaru) =====
        $usulanList = ProgramKegiatanProdi::with(['prodi', 'programKegiatan'])
            ->where('status_persetujuan', 'disetujui_wadek')
            ->latest()
            ->take(5)
            ->get();

        return view('admin-keuangan-fakultas.dashboard', compact(
            'user',
            'totalPeriode',
            'totalProgram',
            'usulanMenunggu',
            'lpjPending',
            'prodiStatuses',
            'prodiStatusCounts',
            'totalProdiUsulan',
            'usulanList'
        ));
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
