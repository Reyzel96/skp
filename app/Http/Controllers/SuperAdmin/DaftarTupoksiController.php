<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DaftarTupoksi;
use Spatie\Permission\Models\Role;

class DaftarTupoksiController extends Controller
{
    public function index(Request $request)
    {
        $query = DaftarTupoksi::query();

        if ($request->filled('bidang')) {
            // Jika filter adalah 'Dosen', ambil semua yang start with Dosen
            if ($request->bidang === 'Dosen') {
                $query->where('bidang', 'LIKE', 'Dosen%');
            } else {
                $query->where('bidang', $request->bidang);
            }
        }

        // Ambil data raw
        $rawTupoksi = $query->select('bidang', \DB::raw('count(*) as jumlah_tupoksi'))
            ->groupBy('bidang')
            ->get();

        // Konsolidasi Dosen di level PHP agar lebih fleksibel
        $consolidatedTupoksi = collect();
        foreach ($rawTupoksi as $item) {
            $bidangName = $item->bidang;
            if (str_starts_with(strtolower($bidangName), 'dosen')) {
                $bidangName = 'Dosen';
            }
            
            if ($consolidatedTupoksi->has($bidangName)) {
                $consolidatedTupoksi[$bidangName]->jumlah_tupoksi += $item->jumlah_tupoksi;
            } else {
                $consolidatedTupoksi->put($bidangName, (object)[
                    'bidang' => $bidangName,
                    'jumlah_tupoksi' => $item->jumlah_tupoksi
                ]);
            }
        }

        $daftarTupoksi = $consolidatedTupoksi->values()->sortBy('bidang');

        // Dapatkan role tendik dan utama untuk filter (Kecuali Super Admin)
        $rawRoles = Role::whereIn('kategori', ['tendik', 'utama'])
            ->where('name', '!=', 'Super Admin')
            ->get();

        $roles = $this->getConsolidatedRoles($rawRoles);

        return view('super-admin.daftar-tupoksi.index', compact('daftarTupoksi', 'roles'));
    }

    public function showDetail($bidang)
    {
        if ($bidang === 'Dosen') {
            $daftarTupoksi = DaftarTupoksi::where('bidang', 'LIKE', 'Dosen%')->orderBy('id')->get();
        } else {
            $daftarTupoksi = DaftarTupoksi::where('bidang', $bidang)->orderBy('id')->get();
        }
        return view('super-admin.daftar-tupoksi.show', compact('daftarTupoksi', 'bidang'));
    }

    public function create()
    {
        $rawRoles = Role::whereIn('kategori', ['tendik', 'utama'])
            ->where('name', '!=', 'Super Admin')
            ->get();

        $roles = $this->getConsolidatedRoles($rawRoles);

        return view('super-admin.daftar-tupoksi.create', compact('roles'));
    }

    public function downloadTemplate()
    {
        $path = public_path('templates/Template Import Daftar Tupoksi SKP.xlsx');

        abort_unless(file_exists($path), 404, 'File template tidak ditemukan.');

        return response()->download($path, 'Template_Import_Daftar_Tupoksi.xlsx');
    }

    private function getConsolidatedRoles($roles)
    {
        $hasDosen = false;
        $filtered = $roles->reject(function ($role) use (&$hasDosen) {
            if (str_starts_with(strtolower($role->name), 'dosen')) {
                $hasDosen = true;
                return true;
            }
            return false;
        })->map(function($role) {
            return (object) [
                'name' => $role->name,
                'kategori' => $role->kategori
            ];
        });

        if ($hasDosen) {
            $filtered->push((object) [
                'name' => 'Dosen',
                'kategori' => 'utama'
            ]);
        }

        return $filtered->sortBy('name')->values();
    }

    public function store(Request $request)
    {
        $request->validate([
            'bidang' => 'required|string',
            'tupoksi' => 'required|string',
            'jenis_aktivitas' => 'required|string',
            'kategori' => 'nullable|array',
            'periode' => 'required|string',
            'frekuensi_per_tahun' => 'required|integer|min:0',
            'durasi_menit' => 'required|integer|min:0',
            'jumlah_sdm' => 'required|integer|min:1',
            'volume_kerja' => 'required|integer|min:0',
        ]);

        $bidang = $request->bidang;
        if (str_starts_with(strtolower($bidang), 'dosen')) {
            $bidang = 'Dosen';
        }

        DaftarTupoksi::create([
            'bidang' => $bidang,
            'tupoksi' => $request->tupoksi,
            'jenis_aktivitas' => $request->jenis_aktivitas,
            'kategori' => $request->kategori ?? ['Utama'],
            'periode' => $request->periode,
            'frekuensi_per_tahun' => $request->frekuensi_per_tahun,
            'durasi_menit' => $request->durasi_menit,
            'jumlah_sdm' => $request->jumlah_sdm,
            'volume_kerja' => $request->volume_kerja,
        ]);

        return redirect()->route('superadmin.daftar-tupoksi.index')->with('success', 'Daftar Tupoksi berhasil ditambahkan!');
    }

    public function bulkStore(Request $request)
    {
        // Jika data dikirim via JSON (untuk menghindari max_input_vars limit)
        if ($request->has('items_json')) {
            $decoded = json_decode($request->items_json, true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.bidang' => 'required|string',
            'items.*.tupoksi' => 'required|string',
            'items.*.jenis_aktivitas' => 'required|string',
            'items.*.kategori' => 'nullable|array',
            'items.*.periode' => 'required|string',
            'items.*.frekuensi_per_tahun' => 'required|integer|min:0',
            'items.*.durasi_menit' => 'required|integer|min:0',
            'items.*.jumlah_sdm' => 'required|integer|min:1',
            'items.*.volume_kerja' => 'required|integer|min:0',
        ]);

        \DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                $bidang = $item['bidang'];
                if (str_starts_with(strtolower($bidang), 'dosen')) {
                    $bidang = 'Dosen';
                }

                DaftarTupoksi::create([
                    'bidang' => $bidang,
                    'tupoksi' => $item['tupoksi'],
                    'jenis_aktivitas' => $item['jenis_aktivitas'],
                    'kategori' => $item['kategori'] ?? ['Utama'],
                    'periode' => $item['periode'],
                    'frekuensi_per_tahun' => $item['frekuensi_per_tahun'],
                    'durasi_menit' => $item['durasi_menit'],
                    'jumlah_sdm' => $item['jumlah_sdm'],
                    'volume_kerja' => $item['volume_kerja'],
                ]);
            }
            \DB::commit();
            return redirect()->route('superadmin.daftar-tupoksi.index')->with('success', 'Bulk Kamus Tupoksi berhasil ditambahkan!');
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(DaftarTupoksi $daftarTupoksi)
    {
        $rawRoles = Role::whereIn('kategori', ['tendik', 'utama'])
            ->where('name', '!=', 'Super Admin')
            ->get();

        $roles = $this->getConsolidatedRoles($rawRoles);

        return view('super-admin.daftar-tupoksi.edit', compact('daftarTupoksi', 'roles'));
    }

    public function update(Request $request, DaftarTupoksi $daftarTupoksi)
    {
        $request->validate([
            'bidang' => 'required|string',
            'tupoksi' => 'required|string',
            'jenis_aktivitas' => 'required|string',
            'kategori' => 'nullable|array',
            'periode' => 'required|string',
            'frekuensi_per_tahun' => 'required|integer|min:0',
            'durasi_menit' => 'required|integer|min:0',
            'jumlah_sdm' => 'required|integer|min:1',
            'volume_kerja' => 'required|integer|min:0',
        ]);

        $bidang = $request->bidang;
        if (str_starts_with(strtolower($bidang), 'dosen')) {
            $bidang = 'Dosen';
        }

        $daftarTupoksi->update([
            'bidang' => $bidang,
            'tupoksi' => $request->tupoksi,
            'jenis_aktivitas' => $request->jenis_aktivitas,
            'kategori' => $request->kategori ?? ['Utama'],
            'periode' => $request->periode,
            'frekuensi_per_tahun' => $request->frekuensi_per_tahun,
            'durasi_menit' => $request->durasi_menit,
            'jumlah_sdm' => $request->jumlah_sdm,
            'volume_kerja' => $request->volume_kerja,
        ]);

        return redirect()->route('superadmin.daftar-tupoksi.show-detail', $daftarTupoksi->bidang)->with('success', 'Daftar Tupoksi berhasil diperbarui!');
    }

    public function destroy(DaftarTupoksi $daftarTupoksi)
    {
        $bidang = $daftarTupoksi->bidang;
        $daftarTupoksi->delete();
        return redirect()->route('superadmin.daftar-tupoksi.show-detail', $bidang)->with('success', 'Daftar Tupoksi berhasil dihapus!');
    }

    public function getByBidang($bidang)
    {
        if ($bidang === 'Dosen') {
            $tupoksi = DaftarTupoksi::where('bidang', 'LIKE', 'Dosen%')->get();
        } else {
            $tupoksi = DaftarTupoksi::where('bidang', $bidang)->get();
        }
        return response()->json($tupoksi);
    }
}
