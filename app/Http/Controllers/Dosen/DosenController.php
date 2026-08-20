<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;

class DosenController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // LOGIKA BARU: Ambil nama prodi dari Role Spatie
        $kodeProdi = 'unknown';
        foreach ($user->getRoleNames() as $role) {
            if (str_starts_with($role, 'Dosen ')) {
                $kodeProdi = strtolower(trim(str_replace('Dosen', '', $role)));
                break;
            }
        }

        $data = match($kodeProdi) {
            'informatika' => $this->getDataInformatika(),
            'elektro'     => $this->getDataElektro(),
            'sipil'       => $this->getDataSipil(),
            'mesin'       => $this->getDataMesin(),
            'perkapalan'  => $this->getDataPerkapalan(),
            'industri'    => $this->getDataIndustri(),
            'arsitektur'  => $this->getDataArsitektur(),
            default       => $this->getDefaultData()
        };

        return view('dosen.dashboard', compact('user', 'data'));
    }

    // --- DATA DUMMY TETAP SAMA ---
    private function getDataInformatika()
    {
        return [
            'prodi_name' => 'Teknik Informatika',
            'total_matkul' => 6,
            'total_sks' => 12,
            'total_mahasiswa' => 145,
            'pending_tupoksi' => 3
        ];
    }

    private function getDataElektro()
    {
        return [
            'prodi_name' => 'Teknik Elektro',
            'total_matkul' => 5,
            'total_sks' => 10,
            'total_mahasiswa' => 132,
            'pending_tupoksi' => 2
        ];
    }

    private function getDataSipil()
    {
        return [
            'prodi_name' => 'Teknik Sipil',
            'total_matkul' => 7,
            'total_sks' => 14,
            'total_mahasiswa' => 168,
            'pending_tupoksi' => 4
        ];
    }

    private function getDataMesin()
    {
        return [
            'prodi_name' => 'Teknik Mesin',
            'total_matkul' => 6,
            'total_sks' => 12,
            'total_mahasiswa' => 154,
            'pending_tupoksi' => 1
        ];
    }

    private function getDataPerkapalan()
    {
        return [
            'prodi_name' => 'Teknik Perkapalan',
            'total_matkul' => 4,
            'total_sks' => 8,
            'total_mahasiswa' => 98,
            'pending_tupoksi' => 2
        ];
    }

    private function getDataIndustri()
    {
        return [
            'prodi_name' => 'Teknik Industri',
            'total_matkul' => 5,
            'total_sks' => 11,
            'total_mahasiswa' => 127,
            'pending_tupoksi' => 3
        ];
    }

    private function getDataArsitektur()
    {
        return [
            'prodi_name' => 'Arsitektur',
            'total_matkul' => 8,
            'total_sks' => 16,
            'total_mahasiswa' => 112,
            'pending_tupoksi' => 5
        ];
    }

    private function getDefaultData()
    {
        return [
            'prodi_name' => 'Unknown',
            'total_matkul' => 0,
            'total_sks' => 0,
            'total_mahasiswa' => 0,
            'pending_tupoksi' => 0
        ];
    }

    public function jadwal()
    {
        return view('dosen.jadwal');
    }

    public function krs()
    {
        return view('dosen.krs');
    }

    public function administrasi()
    {
        return view('dosen.administrasi');
    }
}
