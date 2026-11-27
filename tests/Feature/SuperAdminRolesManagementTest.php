<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminRolesManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Permission bawaan (sesuai RoleAndAdminSeeder)
        foreach ([
            'akses-manajemen-anggaran',
            'kelola-master-anggaran',
            'approve-pagu-anggaran',
            'akses-dashboard-pimpinan',
            'akses-dashboard-prodi',
            'akses-dashboard-lab',
            'akses-dashboard-dosen',
            'akses-dashboard-admin-keuangan',
            'akses-dashboard-upf',
            'akses-dashboard-rektorat',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], [
            'kategori' => 'utama',
            'dashboard_route' => 'superadmin.dashboard',
        ]);

        $this->admin = $this->buatUserDenganProfil('admin@test.local', 'Admin Test');
        $this->admin->assignRole('Super Admin');
    }

    // View dashboard/sidebar membaca UserProfile (unit_kerja, dll) — semua user
    // test harus punya profil lengkap agar pengujian murni soal role.
    private function buatUserDenganProfil(string $email, string $namaLengkap): User
    {
        $user = User::create([
            'name' => $namaLengkap,
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
        \App\Models\UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap' => $namaLengkap,
                'nip' => 'TEST-' . $user->id,
                'pangkat_golongan' => '-',
                'jabatan_pekerjaan' => 'Penguji',
                'jabatan_fungsional' => '-',
                'unit_kerja' => 'Unit Uji',
            ]
        );
        return $user;
    }

    private function buatRole(string $name, string $route = 'pegawai.dashboard', array $perms = []): Role
    {
        $role = Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ], [
            'kategori' => 'utama',
            'dashboard_route' => $route,
        ]);
        if (!empty($perms)) {
            $role->syncPermissions($perms);
        }
        return $role;
    }

    // =========================================================================
    // AKSES HALAMAN
    // =========================================================================

    public function test_superadmin_dapat_membuka_halaman_roles(): void
    {
        $this->actingAs($this->admin)
            ->get(route('superadmin.roles.index'))
            ->assertOk()
            ->assertSee('Kelola Jabatan');
    }

    // =========================================================================
    // PRATINJAU SELISIH PERMISSION DI MODAL EDIT (Tugas 21A)
    // =========================================================================

    public function test_peta_permission_dashboard_dikirim_ke_view(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('superadmin.roles.index'))
            ->assertOk();

        // Peta harus tersedia di rendered HTML sebagai JSON (window.__dashboardPermissions),
        // bukan hardcode di JS. Cek beberapa pasangan kunci dari DASHBOARD_PERMISSIONS.
        $response->assertSee('window.__dashboardPermissions', false);
        $response->assertSee('dekan.dashboard', false);
        $response->assertSee('akses-dashboard-pimpinan', false);
        $response->assertSee('pegawai.dashboard', false);
    }

    public function test_modal_edit_membawa_permission_role_untuk_pratinjau(): void
    {
        $role = $this->buatRole('Dekan Uji', 'dekan.dashboard', ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran']);
        $this->actingAs($this->admin)->get(route('superadmin.roles.index'))->assertOk();

        // Role dikirim ke view dengan permissions ter-eager-load (dipakai openEdit
        // untuk menghitung permission non-peta yang dipertahankan).
        $role->load('permissions:id,name');
        $this->assertTrue($role->relationLoaded('permissions'));
        $this->assertEqualsCanonicalizing(
            ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_user_tanpa_role_super_admin_ditolak(): void
    {
        $user = $this->buatUserDenganProfil('pegawai@test.local', 'Pegawai');

        $this->actingAs($user)
            ->get(route('superadmin.roles.index'))
            ->assertRedirect();
    }

    // =========================================================================
    // PROTEKSI ROLE BAWAAN: RENAME & HAPUS
    // =========================================================================

    public function test_tidak_bisa_rename_role_bawaan(): void
    {
        $dekan = $this->buatRole('Dekan', 'dekan.dashboard', ['akses-dashboard-pimpinan']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $dekan->id), [
                'name' => 'Penguasa Fakultas',
                'kategori' => 'utama',
                'dashboard_route' => 'dekan.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $dekan->id, 'name' => 'Dekan']);
    }

    public function test_role_bawaan_tetap_bisa_diubah_kategori_dashboardnya(): void
    {
        $dekan = $this->buatRole('Dekan', 'dekan.dashboard', ['akses-dashboard-pimpinan']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $dekan->id), [
                'name' => 'Dekan',
                'kategori' => 'utama',
                'dashboard_route' => 'dekan.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['id' => $dekan->id, 'name' => 'Dekan']);
    }

    public function test_role_super_admin_dikunci_penuh(): void
    {
        $superAdmin = Role::where('name', 'Super Admin')->first();

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $superAdmin->id), [
                'name' => 'Super Admin',
                'kategori' => 'utama',
                'dashboard_route' => 'pegawai.dashboard',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $superAdmin->id, 'dashboard_route' => $superAdmin->dashboard_route]);
    }

    public function test_tidak_bisa_hapus_role_bawaan_meski_belum_dipakai(): void
    {
        $rektorat = $this->buatRole('Rektorat', 'rektorat.dashboard', ['akses-dashboard-pimpinan']);

        $this->actingAs($this->admin)
            ->delete(route('superadmin.roles.destroy', $rektorat->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $rektorat->id]);
    }

    public function test_bulk_destroy_menolak_jika_ada_role_bawaan(): void
    {
        $builtIn = $this->buatRole('Wadek 1', 'wakildekan.dashboard');
        $custom = $this->buatRole('Staf Uji Custom', 'pegawai.dashboard');

        $this->actingAs($this->admin)
            ->delete(route('superadmin.roles.bulk-destroy'), [
                'ids' => [$builtIn->id, $custom->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $builtIn->id]);
        $this->assertDatabaseHas('roles', ['id' => $custom->id]);
    }

    public function test_bulk_destroy_berhasil_untuk_role_custom_tak_terpakai(): void
    {
        $custom1 = $this->buatRole('Staf Uji Custom 1');
        $custom2 = $this->buatRole('Staf Uji Custom 2');

        $this->actingAs($this->admin)
            ->delete(route('superadmin.roles.bulk-destroy'), [
                'ids' => [$custom1->id, $custom2->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $custom1->id]);
        $this->assertDatabaseMissing('roles', ['id' => $custom2->id]);
    }

    // =========================================================================
    // PROTEKSI ROLE DIPAKAI USER
    // =========================================================================

    public function test_tidak_bisa_hapus_role_yang_masih_dipakai_user(): void
    {
        $role = $this->buatRole('Dosen Uji Custom', 'dosen.dashboard');
        $user = $this->buatUserDenganProfil('dosen@test.local', 'Dosen');
        $user->assignRole($role);

        $this->actingAs($this->admin)
            ->delete(route('superadmin.roles.destroy', $role->id))
            ->assertRedirect()
            ->assertSessionHas('error', function ($error) use ($user) {
                return str_contains($error, '1 pegawai');
            });

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    // =========================================================================
    // PERMISSON: TIDAK DIKOSONGKAN & TIDAK MERUSAK SAAT EDIT TANPA PERUBAHAN
    // =========================================================================

    public function test_pindah_ke_pegawai_dashboard_mencabut_permission_dashboard_lama_saja(): void
    {
        // Sync berbasis selisih: peta lama dekan=[pimpinan], peta pegawai=[]
        // → pimpinan DICABUT; permission di luar peta (anggaran) TETAP.
        $dekan = $this->buatRole('Dekan', 'dekan.dashboard', ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $dekan->id), [
                'name' => 'Dekan',
                'kategori' => 'utama',
                'dashboard_route' => 'pegawai.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $dekan->refresh();
        $this->assertSame(
            ['akses-manajemen-anggaran', 'approve-pagu-anggaran'],
            $dekan->permissions->pluck('name')->sort()->values()->all()
        );
        $this->assertFalse($dekan->hasPermissionTo('akses-dashboard-pimpinan'));
    }

    public function test_edit_tanpa_perubahan_tidak_mengubah_permission(): void
    {
        // Skenario bug asli: edit role Dekan (dashboard tetap dekan.dashboard)
        // lama meng-sync hanya akses-dashboard-pimpinan → mencabut akses
        // manajemen anggaran milik Dekan.
        $dekan = $this->buatRole('Dekan', 'dekan.dashboard', ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $dekan->id), [
                'name' => 'Dekan',
                'kategori' => 'utama',
                'dashboard_route' => 'dekan.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $dekan->refresh();
        $this->assertSame(
            ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran'],
            $dekan->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_ubah_dashboard_menerapkan_selisih_peta(): void
    {
        // Selisih: peta lama pegawai=[], peta baru kaprodi=[prodi] → tambah prodi;
        // permission lama di luar peta (dosen) TETAP.
        $role = $this->buatRole('Analis Custom', 'pegawai.dashboard', ['akses-dashboard-dosen']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $role->id), [
                'name' => 'Analis Custom',
                'kategori' => 'utama',
                'dashboard_route' => 'kaprodi.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $role->refresh();
        $this->assertSame(
            ['akses-dashboard-dosen', 'akses-dashboard-prodi'],
            $role->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_dekan_ke_kaprodi_mencabut_pimpinan_mempertahankan_anggaran(): void
    {
        $dekan = $this->buatRole('Dekan', 'dekan.dashboard', ['akses-dashboard-pimpinan', 'akses-manajemen-anggaran', 'approve-pagu-anggaran']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $dekan->id), [
                'name' => 'Dekan',
                'kategori' => 'utama',
                'dashboard_route' => 'kaprodi.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $dekan->refresh();
        $this->assertFalse($dekan->hasPermissionTo('akses-dashboard-pimpinan'));
        $this->assertTrue($dekan->hasPermissionTo('akses-dashboard-prodi'));
        $this->assertTrue($dekan->hasPermissionTo('akses-manajemen-anggaran'));
        $this->assertTrue($dekan->hasPermissionTo('approve-pagu-anggaran'));
        $this->assertSame(
            ['akses-dashboard-prodi', 'akses-manajemen-anggaran', 'approve-pagu-anggaran'],
            $dekan->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_rektorat_ke_dekan_mencabut_rektorat_mempertahankan_pimpinan(): void
    {
        $rektorat = $this->buatRole('Rektorat', 'rektorat.dashboard', ['akses-dashboard-pimpinan', 'akses-dashboard-rektorat']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $rektorat->id), [
                'name' => 'Rektorat',
                'kategori' => 'utama',
                'dashboard_route' => 'dekan.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $rektorat->refresh();
        $this->assertFalse($rektorat->hasPermissionTo('akses-dashboard-rektorat'));
        $this->assertTrue($rektorat->hasPermissionTo('akses-dashboard-pimpinan'));
        $this->assertSame(['akses-dashboard-pimpinan'], $rektorat->permissions->pluck('name')->all());
    }

    public function test_pegawai_ke_rektorat_menambah_pimpinan_dan_rektorat_utuh_permission_lain(): void
    {
        $pegawai = $this->buatRole('Staf Khusus', 'pegawai.dashboard', ['akses-manajemen-anggaran']);

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $pegawai->id), [
                'name' => 'Staf Khusus',
                'kategori' => 'utama',
                'dashboard_route' => 'rektorat.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $pegawai->refresh();
        $this->assertTrue($pegawai->hasPermissionTo('akses-dashboard-pimpinan'));
        $this->assertTrue($pegawai->hasPermissionTo('akses-dashboard-rektorat'));
        $this->assertTrue($pegawai->hasPermissionTo('akses-manajemen-anggaran'));
        $this->assertSame(
            ['akses-dashboard-pimpinan', 'akses-dashboard-rektorat', 'akses-manajemen-anggaran'],
            $pegawai->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_store_menerapkan_permission_template(): void
    {
        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => 'Plt. Kaprodi Uji',
                'kategori' => 'utama',
                'dashboard_route' => 'kaprodi.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $role = Role::where('name', 'Plt. Kaprodi Uji')->first();
        $this->assertNotNull($role);
        $this->assertSame(['akses-dashboard-prodi'], $role->permissions->pluck('name')->all());
        $this->assertSame('web', $role->guard_name);
    }

    public function test_store_dengan_template_pegawai_tanpa_permission(): void
    {
        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => 'Tendik Uji Custom',
                'kategori' => 'tendik',
                'dashboard_route' => 'pegawai.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $role = Role::where('name', 'Tendik Uji Custom')->first();
        $this->assertNotNull($role);
        $this->assertSame(0, $role->permissions->count());
    }

    // =========================================================================
    // VALIDASI KETAT
    // =========================================================================

    public function test_store_menolak_dashboard_route_arbitrary(): void
    {
        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => 'Role Nakal',
                'kategori' => 'utama',
                'dashboard_route' => 'dashboard-tidak-ada',
            ])
            ->assertSessionHasErrors('dashboard_route');

        $this->assertDatabaseMissing('roles', ['name' => 'Role Nakal']);
    }

    public function test_update_menolak_dashboard_route_arbitrary(): void
    {
        $role = $this->buatRole('Role Uji Validasi', 'pegawai.dashboard');

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $role->id), [
                'name' => 'Role Uji Validasi',
                'kategori' => 'utama',
                'dashboard_route' => 'dashboard-tidak-ada',
            ])
            ->assertSessionHasErrors('dashboard_route');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'dashboard_route' => 'pegawai.dashboard']);
    }

    public function test_update_menerima_dashboard_route_custom_existing(): void
    {
        // Role custom bisa saja punya dashboard_route lama di luar template
        // (mis. dibuat sebelum validasi ketat) — update harus tetap bisa
        // menyimpan tanpa memaksa perubahan dashboard.
        $role = $this->buatRole('Role Legacy', 'legacy.dashboard');

        $this->actingAs($this->admin)
            ->put(route('superadmin.roles.update', $role->id), [
                'name' => 'Role Legacy',
                'kategori' => 'tendik',
                'dashboard_route' => 'legacy.dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'dashboard_route' => 'legacy.dashboard']);
    }

    public function test_store_menolak_nama_kosong_dan_lebih_dari_100_karakter(): void
    {
        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => '',
                'kategori' => 'utama',
                'dashboard_route' => 'dosen.dashboard',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => str_repeat('A', 101),
                'kategori' => 'utama',
                'dashboard_route' => 'dosen.dashboard',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_menolak_nama_duplikat(): void
    {
        $this->buatRole('Dosen Uji Duplikat');

        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => 'Dosen Uji Duplikat',
                'kategori' => 'utama',
                'dashboard_route' => 'dosen.dashboard',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_mengabaikan_field_mass_assignment(): void
    {
        $this->actingAs($this->admin)
            ->post(route('superadmin.roles.store'), [
                'name' => 'Role Mass Assignment',
                'kategori' => 'utama',
                'dashboard_route' => 'pegawai.dashboard',
                'guard_name' => 'api',
                'id' => 9999,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $role = Role::where('name', 'Role Mass Assignment')->first();
        $this->assertNotNull($role);
        $this->assertNotSame(9999, $role->id);
        $this->assertSame('web', $role->guard_name);
    }

    // =========================================================================
    // REGRESI: TIAP PERAN MASIH BISA MEMBUKA HALAMAN UTAMANYA
    // =========================================================================

    public static function penyediaDashboardPeran(): array
    {
        return [
            'dekan' => ['dekan.dashboard', 'dekan.dashboard', ['akses-dashboard-pimpinan']],
            'wakildekan' => ['wakildekan.dashboard', 'wakildekan.dashboard', ['akses-dashboard-pimpinan']],
            'rektorat' => ['rektorat.dashboard', 'rektorat.dashboard', ['akses-dashboard-pimpinan', 'akses-dashboard-rektorat']],
            'kaprodi' => ['kaprodi.dashboard', 'kaprodi.dashboard', ['akses-dashboard-prodi']],
            'sekprodi' => ['sekprodi.dashboard', 'sekprodi.dashboard', ['akses-dashboard-prodi']],
            'kepalalab' => ['kepalalab.dashboard', 'kepalalab.dashboard', ['akses-dashboard-lab']],
            'dosen' => ['dosen.dashboard', 'dosen.dashboard', ['akses-dashboard-dosen']],
            'adminkeuanganfakultas' => ['adminkeuanganfakultas.dashboard', 'adminkeuanganfakultas.dashboard', ['akses-dashboard-admin-keuangan']],
            'unitpenunjang' => ['unitpenunjang.dashboard', 'unitpenunjang.dashboard', ['akses-dashboard-upf']],
            'pegawai' => ['pegawai.dashboard', 'pegawai.dashboard', []],
        ];
    }

    /**
     * @dataProvider penyediaDashboardPeran
     */
    public function test_setiap_peran_bisa_membuka_dashboard_utamanya(string $dashboardRoute, string $targetRoute, array $permissions): void
    {
        $roleName = 'Penguji ' . $dashboardRoute;
        $role = $this->buatRole($roleName, $dashboardRoute, $permissions);

        $user = $this->buatUserDenganProfil(
            'penguji-' . md5($dashboardRoute) . '@test.local',
            'Penguji'
        );
        $user->assignRole($role);

        // /dashboard harus me-redirect ke halaman utama sesuai role
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route($targetRoute, absolute: false));

        // Halaman utama role harus bisa dibuka (200)
        $this->actingAs($user)
            ->get(route($targetRoute))
            ->assertOk();
    }

    public function test_super_admin_bisa_membuka_dashboard_superadmin(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertRedirect(route('superadmin.dashboard', absolute: false));

        $this->actingAs($this->admin)
            ->get(route('superadmin.dashboard'))
            ->assertOk();
    }

    /**
     * Tiebreaker Tugas 22: dua role dengan kategori SAMA harus deterministik —
     * role dengan id terkecil yang dipakai, konsisten pada beberapa request.
     */
    public function test_multi_role_kategori_sama_redirect_ke_role_id_terkecil_konsisten(): void
    {
        $roleA = $this->buatRole('Uji Multi A', 'pegawai.dashboard', []);
        $roleB = $this->buatRole('Uji Multi B', 'pegawai.dashboard', []);
        // Pastikan id B > id A
        $this->assertGreaterThan($roleA->id, $roleB->id);

        $user = $this->buatUserDenganProfil('multi-uji@test.local', 'Multi Uji');
        $user->assignRole([$roleB, $roleA]); // sengaja assign terbalik

        $routes = [];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertRedirect(route('pegawai.dashboard', absolute: false));
            $routes[] = $response->headers->get('Location');
        }

        $this->assertCount(1, array_unique($routes), 'Redirect harus konsisten antar request');
    }

    /**
     * Tiebreaker Tugas 22: role utama + tendik tetap memilih utama
     * (kategori 'utama' < 'tendik' secara alfabetis), di luar urutan assign/id.
     */
    public function test_multi_role_bed_kategori_tetap_memilih_utama(): void
    {
        $tendikRole = $this->buatRole('Uji Multi Tendik', 'pegawai.dashboard', []);
        $tendikRole->forceFill(['kategori' => 'tendik'])->save();
        $utamaRole = $this->buatRole('Uji Multi Utama', 'pegawai.dashboard', []);
        $utamaRole->forceFill(['kategori' => 'utama'])->save();

        $user = $this->buatUserDenganProfil('multi-kat@test.local', 'Multi Kat');
        $user->assignRole([$tendikRole, $utamaRole]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('pegawai.dashboard', absolute: false));
    }
}
