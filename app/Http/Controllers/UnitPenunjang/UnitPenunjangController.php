<?php

namespace App\Http\Controllers\UnitPenunjang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UnitPenunjangController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Ekstrak nama UPF dari Role (Contoh: "Kepala UPF Kemahasiswaan" -> "Kemahasiswaan")
        $namaUpf = 'Unit Penunjang Umum';
        foreach ($user->getRoleNames() as $role) {
            if (str_starts_with($role, 'Kepala UPF ')) {
                $namaUpf = trim(str_replace('Kepala UPF', '', $role));
                break;
            }
        }

        // Data dummy untuk tampilan dashboard UPF
        $data = [
            'upf_name' => $namaUpf,
            'total_kegiatan' => rand(10, 45),
            'anggaran_terserap' => rand(50, 95),
            'dokumen_pending' => rand(1, 5)
        ];

        return view('unit-penunjang.dashboard', compact('user', 'data'));
    }
}
