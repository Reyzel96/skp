<?php

namespace App\Http\Controllers\Sekprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SekprodiController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Ambil nama prodi dari Role Spatie
        $namaProdiRole = '';
        foreach ($user->getRoleNames() as $role) {
            if (str_starts_with($role, 'Sekprodi ')) {
                $namaProdiRole = trim(str_replace('Sekprodi', '', $role));
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

        return view('sekprodi.dashboard', compact('user', 'data'));
    }

    private function getDataInformatika()
    {
        return [
            'prodi_name' => 'Informatika',
            'total_surat' => 45,
            'total_jadwal' => 120,
            'pending_approval' => 8
        ];
    }

    private function getDataElektro()
    {
        return [
            'prodi_name' => 'Teknik Eletreo',
            'total_surat' => 38,
            'total_jadwal' => 105,
            'pending_approval' => 5
        ];
    }

    private function getDataSipil()
    {
        return [
            'prodi_name' => 'Teknik Sipil',
            'total_surat' => 42,
            'total_jadwal' => 115,
            'pending_approval' => 7
        ];
    }

    private function getDataMesin()
    {
        return [
            'prodi_name' => 'Teknik Mesin',
            'total_surat' => 35,
            'total_jadwal' => 98,
            'pending_approval' => 4
        ];
    }

    private function getDataPerkapalan()
    {
        return [
            'prodi_name' => 'Teknik Perkapalan',
            'total_surat' => 30,
            'total_jadwal' => 85,
            'pending_approval' => 3
        ];
    }

    private function getDataIndustri()
    {
        return [
            'prodi_name' => 'Teknik Industri',
            'total_surat' => 33,
            'total_jadwal' => 92,
            'pending_approval' => 6
        ];
    }

    private function getDataArsitektur()
    {
        return [
            'prodi_name' => 'Arsiterktur',
            'total_surat' => 32,
            'total_jadwal' => 88,
            'pending_approval' => 5
        ];
    }

    private function getDefaultData($fallbackName = 'Unknown')
    {
        return [
            'prodi_name' => $fallbackName ?: 'Unknown',
            'total_surat' => 0,
            'total_jadwal' => 0,
            'pending_approval' => 0
        ];
    }

    public function administrasi()
    {
        return view('sekprodi.administrasi');
    }
}
