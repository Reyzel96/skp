<?php

namespace App\Http\Controllers\Rektorat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, SkpHeader};

class RektoratController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Statistik SKP
        $skpStats = [
            'pending'   => SkpHeader::whereIn('status', ['Diajukan', 'Pending Utama', 'Pending Akhir'])->count(),
            'disetujui' => SkpHeader::where('status', 'Disetujui')->count(),
            'draft'     => SkpHeader::where('status', 'Draft')->count(),
        ];

        return view('rektorat.dashboard', compact(
            'user',
            'skpStats'
        ));
    }
}
