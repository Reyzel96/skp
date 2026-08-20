<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ProdiController extends Controller
{
    public function index()
    {
        $prodis = Prodi::orderBy('nama')->get();
        return view('super-admin.prodi.index', compact('prodis'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'nama' => trim($request->nama),
            'kode' => $request->kode ? trim($request->kode) : null,
        ]);

        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:prodis,nama',
            'kode' => 'nullable|string|max:50|unique:prodis,kode',
        ]);

        $prodi = Prodi::create($validated);

        // Otomatis Buat Role Terkait & Beri Permission Dasar
        $roleConfig = [
            'Kaprodi' => ['akses-dashboard-prodi', 'akses-manajemen-anggaran'],
            'Sekprodi' => ['akses-dashboard-prodi', 'akses-manajemen-anggaran'],
            'Kepala Lab' => ['akses-dashboard-lab'],
            'Dosen' => ['akses-dashboard-dosen'],
        ];

        foreach ($roleConfig as $prefix => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => "{$prefix} {$prodi->nama}"],
                ['kategori' => 'utama'] // Tambahkan kategori utama
            );
            
            // Pastikan kategori terupdate jika role sudah ada tapi kategorinya kosong
            if (!$role->kategori) {
                $role->update(['kategori' => 'utama']);
            }

            $role->syncPermissions($permissions);
        }

        return redirect()->back()->with('success', 'Program Studi dan Role terkait berhasil ditambahkan.');
    }

    public function update(Request $request, Prodi $prodi)
    {
        $request->merge([
            'nama' => trim($request->nama),
            'kode' => $request->kode ? trim($request->kode) : null,
        ]);

        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:prodis,nama,' . $prodi->id,
            'kode' => 'nullable|string|max:50|unique:prodis,kode,' . $prodi->id,
        ]);

        $oldName = $prodi->nama;
        $prodi->update($validated);

        // Jika nama berubah, update juga nama role-nya
        if ($oldName !== $prodi->nama) {
            $rolePrefixes = ['Kaprodi', 'Sekprodi', 'Kepala Lab', 'Dosen'];
            foreach ($rolePrefixes as $prefix) {
                $oldRoleName = "{$prefix} {$oldName}";
                $newRoleName = "{$prefix} {$prodi->nama}";
                
                $role = Role::where('name', $oldRoleName)->first();
                if ($role) {
                    $role->update(['name' => $newRoleName]);
                } else {
                    // Jika role lama tidak ketemu (mungkin dihapus manual), buat yang baru
                    Role::firstOrCreate(['name' => $newRoleName]);
                }
            }
        }

        return redirect()->back()->with('success', 'Program Studi dan Role terkait berhasil diperbarui.');
    }

    public function destroy(Prodi $prodi)
    {
        $prodi->delete();
        return redirect()->back()->with('success', 'Program Studi berhasil dihapus.');
    }
}
