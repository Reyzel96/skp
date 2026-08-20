<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KaprodiController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Ambil nama prodi dari Role Spatie
        $namaProdiRole = '';
        foreach ($user->getRoleNames() as $role) {
            if (str_starts_with($role, 'Kaprodi ')) {
                $namaProdiRole = trim(str_replace('Kaprodi', '', $role));
                break;
            }
        }

        $normalized = strtolower($namaProdiRole);

        $data = match(true) {
            str_contains($normalized, 'informatika') => $this->getDataInformatika(),
            str_contains($normalized, 'elektro') => $this->getDataElektro(),
            str_contains($normalized, 'sipil')       => $this->getDataSipil(),
            str_contains($normalized, 'mesin')       => $this->getDataMesin(),
            str_contains($normalized, 'perkapalan')  => $this->getDataPerkapalan(),
            str_contains($normalized, 'industri')    => $this->getDataIndustri(),
            str_contains($normalized, 'arsitektur') => $this->getDataArsitektur(),
            default                                  => $this->getDefaultData($namaProdiRole)
        };

        return view('kaprodi.dashboard', compact('user', 'data'));
    }

    private function getDataInformatika()
    {
        return [
            'total_mahasiswa' => 450,
            'total_dosen' => 18,
            'prodi_name' => 'Informatika',
            'akreditasi' => 'A'
        ];
    }

    private function getDataElektro()
    {
        return [
            'total_mahasiswa' => 380,
            'total_dosen' => 15,
            'prodi_name' => 'Teknik Eletreo',
            'akreditasi' => 'B'
        ];
    }

    private function getDataSipil()
    {
        return [
            'total_mahasiswa' => 420,
            'total_dosen' => 16,
            'prodi_name' => 'Teknik Sipil',
            'akreditasi' => 'A'
        ];
    }

    private function getDataMesin()
    {
        return [
            'total_mahasiswa' => 350,
            'total_dosen' => 14,
            'prodi_name' => 'Teknik Mesin',
            'akreditasi' => 'B'
        ];
    }

    private function getDataPerkapalan()
    {
        return [
            'total_mahasiswa' => 280,
            'total_dosen' => 12,
            'prodi_name' => 'Teknik Perkapalan',
            'akreditasi' => 'B'
        ];
    }

    private function getDataIndustri()
    {
        return [
            'total_mahasiswa' => 320,
            'total_dosen' => 13,
            'prodi_name' => 'Teknik Industri',
            'akreditasi' => 'A'
        ];
    }

    private function getDataArsitektur()
    {
        return [
            'total_mahasiswa' => 300,
            'total_dosen' => 14,
            'prodi_name' => 'Arsiterktur',
            'akreditasi' => 'A'
        ];
    }

    private function getDefaultData($fallbackName = 'Unknown')
    {
        return [
            'total_mahasiswa' => 0,
            'total_dosen' => 0,
            'prodi_name' => $fallbackName ?: 'Unknown',
            'akreditasi' => '-'
        ];
    }

    public function dataMahasiswa()
    {
        return view('kaprodi.mahasiswa');
    }

    public function dataDosen()
    {
        return view('kaprodi.dosen');
    }

    public function kurikulum()
    {
        return view('kaprodi.kurikulum');
    }
}
