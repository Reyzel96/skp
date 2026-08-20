<?php

namespace App\Http\Controllers;

use App\Models\PeriodeAnggaran;
use Illuminate\Http\Request;

class PeriodeAnggaranController extends Controller
{
    public function index()
    {
        $periodeList = PeriodeAnggaran::orderBy('tahun_anggaran', 'desc')->get();
        return view('periode-anggaran.index', compact('periodeList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_anggaran' => 'required|numeric',
            'tahun_akademik' => 'required|string',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        PeriodeAnggaran::create($validated);
        return redirect()->back()->with('success', 'Periode anggaran berhasil ditambahkan.');
    }

    public function update(Request $request, PeriodeAnggaran $periode)
    {
        $validated = $request->validate([
            'tahun_anggaran' => 'required|numeric',
            'tahun_akademik' => 'required|string',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status'         => 'required|in:aktif,nonaktif',
        ]);

        $periode->update($validated);
        return redirect()->back()->with('success', 'Periode anggaran berhasil diperbarui.');
    }

    public function destroy(PeriodeAnggaran $periode)
    {
        $periode->delete();
        return redirect()->back()->with('success', 'Periode anggaran berhasil dihapus.');
    }
}
