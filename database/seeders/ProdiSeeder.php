<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prodi;
use Spatie\Permission\Models\Role;

class ProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodis = [
            'Arsitektur',
            'Teknik Elektro',
            'Teknik Mesin',
            'Teknik Perkapalan',
            'Teknik Sipil',
            'Teknik Industri',
            'Informatika',
        ];

        // Opsional: Hapus prodi yang tidak ada dalam daftar default (untuk mencegah double data/typo lama)
        Prodi::whereNotIn('nama', $prodis)->delete();

        foreach ($prodis as $nama) {
            $prodi = Prodi::updateOrCreate(['nama' => $nama]);
            
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
                    ['kategori' => 'utama']
                );

                if (!$role->kategori) {
                    $role->update(['kategori' => 'utama']);
                }

                $role->syncPermissions($permissions);
            }
        }
    }
}
