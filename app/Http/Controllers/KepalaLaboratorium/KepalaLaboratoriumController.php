<?php

namespace App\Http\Controllers\KepalaLaboratorium;

use App\Http\Controllers\Controller;

class KepalaLaboratoriumController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // LOGIKA BARU: Ambil nama prodi dari Role Spatie
        $kodeProdi = 'unknown';
        foreach ($user->getRoleNames() as $role) {
            if (str_starts_with($role, 'Kepala Lab ')) {
                $kodeProdi = strtolower(trim(str_replace('Kepala Lab', '', $role)));
                break;
            }
        }

        $data = $this->getLabData($kodeProdi);

        return view('kepala-laboratorium.dashboard', compact('user', 'data'));
    }

    private function getLabData($kode)
    {
        // UBAH KEY ARRAY: Sesuaikan dengan kode di tabel 'prodi'
        $labMapping = [
            'informatika' => ['name' => 'Lab Informatika', 'total_alat' => 150],
            'elektro'     => ['name' => 'Lab Elektro', 'total_alat' => 120],
            'sipil'       => ['name' => 'Lab Sipil', 'total_alat' => 95],
            'mesin'       => ['name' => 'Lab Mesin', 'total_alat' => 110],
            'perkapalan'  => ['name' => 'Lab Perkapalan', 'total_alat' => 85],
            'industri'    => ['name' => 'Lab Industri', 'total_alat' => 100],
            'arsitektur'  => ['name' => 'Lab Arsitektur', 'total_alat' => 75],
        ];

        $labInfo = $labMapping[$kode] ?? ['name' => 'Laboratorium Umum', 'total_alat' => 0];

        return [
            'lab_name' => $labInfo['name'],
            'total_alat' => $labInfo['total_alat'],
            'total_praktikum' => rand(15, 40),
            'maintenance_pending' => rand(3, 10)
        ];
    }

    public function inventaris()
    {
        return view('kepala-laboratorium.inventaris');
    }

    public function jadwalPraktikum()
    {
        return view('kepala-laboratorium.jadwal-praktikum');
    }
}
