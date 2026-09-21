<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PangkatGolongan;
use Illuminate\Http\Request;

class PangkatGolonganController extends Controller
{
    public function byJenjang($jenjangJabatanId)
    {
        $list = PangkatGolongan::where('jenjang_jabatan_id', $jenjangJabatanId)
            ->orderBy('urutan')
            ->get();

        return response()->json($list);
    }
}
