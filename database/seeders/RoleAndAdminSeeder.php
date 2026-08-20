<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\UserProfile;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'akses-manajemen-anggaran',
            'kelola-master-anggaran',
            'approve-pagu-anggaran',
            'akses-dashboard-pimpinan',
            'akses-dashboard-prodi',
            'akses-dashboard-lab',
            'akses-dashboard-dosen',
            'akses-dashboard-admin-keuangan',
            'akses-dashboard-upf',
            'akses-dashboard-rektorat'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $prodis = ['Arsitektur', 'Teknik Elektro', 'Teknik Mesin', 'Teknik Perkapalan', 'Teknik Sipil', 'Teknik Industri', 'Informatika'];

        // PERUBAHAN: Wadek 1 dan Wadek 2 sekarang diarahkan ke 'wakildekan.dashboard'
        $jabatanUtama = [
            ['name' => 'Super Admin', 'route' => 'superadmin.dashboard', 'perms' => []],
            ['name' => 'Dekan', 'route' => 'dekan.dashboard', 'perms' => ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran']],
            ['name' => 'Wadek 1', 'route' => 'wakildekan.dashboard', 'perms' => ['akses-dashboard-pimpinan']],
            ['name' => 'Wadek 2', 'route' => 'wakildekan.dashboard', 'perms' => ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran']],
            ['name' => 'Rektorat', 'route' => 'rektorat.dashboard', 'perms' => ['akses-dashboard-pimpinan', 'akses-dashboard-rektorat', 'akses-manajemen-anggaran']],
        ];

        foreach ($prodis as $prodi) {
            $jabatanUtama[] = ['name' => "Kaprodi $prodi", 'route' => 'kaprodi.dashboard', 'perms' => ['akses-dashboard-prodi', 'akses-manajemen-anggaran']];
            $jabatanUtama[] = ['name' => "Sekprodi $prodi", 'route' => 'sekprodi.dashboard', 'perms' => ['akses-dashboard-prodi']];
            $jabatanUtama[] = ['name' => "Kepala Lab $prodi", 'route' => 'kepalalab.dashboard', 'perms' => ['akses-dashboard-lab']];
            $jabatanUtama[] = ['name' => "Dosen $prodi", 'route' => 'dosen.dashboard', 'perms' => ['akses-dashboard-dosen']];
        }

        $upfs = ['Penelitian', 'Kemahasiswaan', 'Kerjasama', 'Humas', 'Laboratorium', 'Penjaminan Mutu'];
        foreach ($upfs as $upf) {
            $jabatanUtama[] = ['name' => "Kepala UPF $upf", 'route' => 'unitpenunjang.dashboard', 'perms' => ['akses-dashboard-upf']];
        }

        foreach ($jabatanUtama as $data) {
            $role = Role::firstOrCreate([
                'name' => $data['name'],
                'guard_name' => 'web'
            ], [
                'kategori' => 'utama',
                'dashboard_route' => $data['route']
            ]);
            if (!empty($data['perms'])) {
                $role->syncPermissions($data['perms']);
            }
        }

        $peranTendik = ['Tata Usaha', 'Kesekretariatan', 'Kepegawaian', 'Sumber Daya Manusia', 'Kesekretariatan (Kerumahtanggaan)', 'IT', 'Kerumahtanggaan', 'Sarana dan Prasarana', 'Humas', 'Kerjasama dan Internasional', 'Front Office', 'Laboran', 'Administrasi Prodi', 'Admin Keuangan Fakultas'];
        // Remove the loop that adds specific Administrasi Prodi for each prodi
        /*
        foreach ($prodis as $prodi) {
            $peranTendik[] = "Administrasi Prodi $prodi";
        }
        */

        foreach ($peranTendik as $namaRole) {
            $role = Role::firstOrCreate([
                'name' => $namaRole,
                'guard_name' => 'web'
            ], [
                'kategori' => 'tendik',
                'dashboard_route' => 'pegawai.dashboard'
            ]);

            // Berikan akses khusus untuk Admin Keuangan Fakultas meskipun kategori tendik
            if ($namaRole === 'Admin Keuangan Fakultas') {
                $role->update(['dashboard_route' => 'adminkeuanganfakultas.dashboard']);
                $role->syncPermissions([
                    'akses-dashboard-admin-keuangan',
                    'akses-manajemen-anggaran',
                    'kelola-master-anggaran'
                ]);
            }
        }

        $admin = User::firstOrCreate(['email' => 'admin@gmail.com'], ['password' => Hash::make('qwerty123'), 'email_verified_at' => now()]);
        UserProfile::updateOrCreate(['user_id' => $admin->id], ['nama_lengkap' => 'Administrator Utama', 'nip' => 'SA001', 'pangkat_golongan' => '-', 'jabatan_pekerjaan' => 'Super Admin', 'jabatan_fungsional' => '-', 'unit_kerja' => 'Pusat Komputer']);
        $admin->assignRole('Super Admin');
    }
}
