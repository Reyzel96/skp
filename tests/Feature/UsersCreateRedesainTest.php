<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\JenjangJabatan;
use App\Models\PangkatGolongan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use DOMDocument;
use DOMXPath;

/**
 * Tugas 25: halaman users/create & users/{id}/edit.
 * - Struktur HTML via parser DOMDocument (bukan regex): tanpa form bersarang,
 *   tanpa <button> tanpa type di dalam form (kecuali menunjuk form lain),
 *   semua label for terhubung ke id input, tanpa id duplikat.
 * - Ringkasan murni tampilan: tidak punya atribut name (tidak terkirim).
 * - Store sukses: user + role + profil tersimpan.
 * - Old() pangkat pulih setelah validasi gagal.
 * - Keamanan: 'Super Admin' di roles_tendik[] / role_utama ditolak;
 *   role tendik/utama valid tetap lolos.
 * - N+1: jumlah query halaman create tidak bertambah seiring jumlah role.
 * - Non-superadmin ditolak.
 */
class UsersCreateRedesainTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['akses-dashboard-pimpinan', 'akses-dashboard-prodi', 'akses-manajemen-anggaran'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        // Mirror produksi (RoleAndAdminSeeder.php:39-63): Super Admin = kategori 'utama'.
        // Tanpa ini, findOrCreate membuat role dgn default enum 'tendik'
        // (migration permission_tables:43) dan rule kategori tidak bisa diuji.
        $superadmin = Role::findOrCreate('Super Admin', 'web');
        $superadmin->kategori = 'utama';
        $superadmin->dashboard_route = 'superadmin.dashboard';
        $superadmin->save();
        $superadmin->givePermissionTo('akses-manajemen-anggaran');

        $this->admin = $this->buatUser('admin@gmail.com');
        $this->admin->assignRole('Super Admin');
    }

    private function buatUser(string $email): User
    {
        $u = User::create(['email' => $email, 'password' => bcrypt('password')]);
        UserProfile::create([
            'user_id' => $u->id,
            'nama_lengkap' => 'User Uji ' . $u->id,
            'nip' => '19' . str_pad((string) $u->id, 16, '0'),
            'pangkat_golongan' => 'III/a',
            'jabatan_pekerjaan' => 'Staf',
            'jabatan_fungsional' => '-',
            'unit_kerja' => 'Unit Uji',
        ]);
        return $u;
    }

    private function seedRoles(): void
    {
        Role::findOrCreate('Dosen', 'web')->update(['kategori' => 'utama']);
        Role::findOrCreate('Kaprodi Teknik Informatika', 'web')->update(['kategori' => 'utama']);
        Role::findOrCreate('Staf TU', 'web')->update(['kategori' => 'tendik']);
        Role::findOrCreate('Kepala Lab', 'web')->update(['kategori' => 'tendik']);
    }

    private function renderDom(string $halaman): DOMDocument
    {
        $response = $this->actingAs($this->admin)->get($halaman);
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        return $dom;
    }

    private function auditDom(DOMDocument $dom): array
    {
        $pelanggaran = [];
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//form[descendant::form]') as $form) {
            $pelanggaran[] = 'nested <form> di dalam <form id="' . $form->getAttribute('id') . '">';
        }

        $ids = [];
        foreach ($xpath->query('//*[@id]') as $el) {
            $id = $el->getAttribute('id');
            $ids[$id] = ($ids[$id] ?? 0) + 1;
        }
        foreach ($ids as $id => $n) {
            if ($n > 1) $pelanggaran[] = "id duplikat: {$id} ({$n}x)";
        }

        foreach ($xpath->query('//form//button[not(@type)]') as $btn) {
            $pelanggaran[] = '<button> tanpa type= di dalam form, konten="' . trim($btn->textContent) . '">';
        }

        // Setiap label for= harus menunjuk id yang ada, dan id itu milik input/select/textarea
        foreach ($xpath->query('//label[@for]') as $label) {
            $target = $label->getAttribute('for');
            $cocok = $xpath->query("//*[@id='" . $target . "']");
            if ($cocok->length === 0) {
                $pelanggaran[] = "label for='{$target}' menunjuk id yang tidak ada";
                continue;
            }
            $tag = strtolower($cocok->item(0)->nodeName);
            if (!in_array($tag, ['input', 'select', 'textarea'])) {
                $pelanggaran[] = "label for='{$target}' menunjuk <{$tag}> (bukan field form)";
            }
        }

        return $pelanggaran;
    }

    // ── Struktur HTML ────────────────────────────────────────────────────────

    public function test_create_struktur_html_sehat(): void
    {
        $this->seedRoles();
        $pelanggaran = $this->auditDom($this->renderDom(route('superadmin.users.create')));
        $this->assertSame([], $pelanggaran, "Struktur HTML users/create rusak:\n- " . implode("\n- ", $pelanggaran));
    }

    public function test_edit_struktur_html_sehat(): void
    {
        $this->seedRoles();
        $pelanggaran = $this->auditDom($this->renderDom(route('superadmin.users.edit', $this->admin->id)));
        $this->assertSame([], $pelanggaran, "Struktur HTML users/edit rusak:\n- " . implode("\n- ", $pelanggaran));
    }

    public function test_ringkasan_tidak_punya_name_tidak_terkirim(): void
    {
        $this->seedRoles();
        $dom = $this->renderDom(route('superadmin.users.create'));
        $xpath = new DOMXPath($dom);

        // Semua elemen form-control [name] harus berada di dalam <form>
        // (elemen non-form seperti <meta name> dikecualikan).
        foreach ($xpath->query('//input[@name] | //select[@name] | //textarea[@name] | //button[@name]') as $el) {
            $this->assertGreaterThan(
                0,
                $xpath->query('ancestor::form', $el)->length,
                "Elemen <{$el->nodeName} name=\"{$el->getAttribute('name')}\"> di luar form (akan tidak terkirim / yatim)"
            );
        }

        // Elemen ringkasan tidak boleh punya atribut name
        foreach (['ringkasan-nama', 'ringkasan-jabatan', 'ringkasan-tendik', 'ringkasan-unit'] as $id) {
            $el = $xpath->query("//*[@id='{$id}']")->item(0);
            $this->assertNotNull($el, "elemen ringkasan #{$id} tidak ada");
            $this->assertSame('', $el->getAttribute('name'), "elemen ringkasan #{$id} punya atribut name (terkirim ke server!)");
        }
    }

    // ── Store & validasi keamanan ────────────────────────────────────────────

    private function payloadValid(array $overrides = []): array
    {
        return array_merge([
            'email' => 'budi@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_utama' => 'Dosen',
            'roles_tendik' => ['Staf TU'],
            'nama' => 'Budi Santoso',
            'nip' => '198765432100000001',
            'jenjang_jabatan_id' => '',
            'jabatan_fungsional' => '',
            'unit_kerja' => 'Fakultas Teknik',
        ], $overrides);
    }

    public function test_store_sukses_user_role_profil_tersimpan(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid());

        $response->assertRedirect(route('superadmin.users.index'));

        $user = User::where('email', 'budi@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password), 'password tidak di-hash');
        // Alur simpan eksisting: bila role_utama terisi, HANYA role_utama yang di-sync
        // (roles_tendik diabaikan) — SuperAdminController.php:433-437. Kontrak tidak diubah.
        $this->assertTrue($user->hasRole('Dosen'));
        $this->assertFalse($user->hasRole('Staf TU'), 'role_utama terisi → roles_tendik tidak boleh ikut tersimpan');

        $profil = UserProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profil);
        $this->assertSame('Budi Santoso', $profil->nama_lengkap);
        $this->assertSame('198765432100000001', $profil->nip);
    }

    public function test_super_admin_sebagai_role_utama_ditolak(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid(['role_utama' => 'Super Admin', 'roles_tendik' => null]));

        $response->assertSessionHasErrors('role_utama');
        $this->assertNull(User::where('email', 'budi@gmail.com')->first());
    }

    public function test_super_admin_di_roles_tendik_ditolak(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid([
                'role_utama' => null,
                'roles_tendik' => ['Staf TU', 'Super Admin'],
            ]));

        $response->assertSessionHasErrors();
        $errors = $response->getSession()->get('errors');
        $this->assertNotNull($errors, 'session errors kosong — validasi tidak gagal?');
        $this->assertTrue(
            collect($errors->all())->contains(fn($m) => str_contains($m, 'Super Admin')),
            'Pesan error tidak menyebut Super Admin: ' . implode(' | ', $errors->all())
        );
        $this->assertNull(User::where('email', 'budi@gmail.com')->first());
    }

    public function test_role_utama_non_utama_kategori_ditolak(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid(['role_utama' => 'Staf TU']));

        $response->assertSessionHasErrors('role_utama');
        $this->assertNull(User::where('email', 'budi@gmail.com')->first());
    }

    public function test_role_tendik_di_role_utama_ditolak(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid([
                'role_utama' => 'Kepala Lab',
                'roles_tendik' => null,
            ]));

        $response->assertSessionHasErrors('role_utama');
    }

    public function test_role_valid_tetap_lolos(): void
    {
        $this->seedRoles();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid([
                'role_utama' => null,
                'roles_tendik' => ['Staf TU', 'Kepala Lab'],
            ]));

        $response->assertRedirect(route('superadmin.users.index'));
        $user = User::where('email', 'budi@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Staf TU'));
        $this->assertTrue($user->hasRole('Kepala Lab'));
    }

    public function test_update_super_admin_ditolak_juga(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('target@gmail.com');
        $target->assignRole('Staf TU');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'target@gmail.com',
                'role_utama' => 'Super Admin',
                'roles_tendik' => null,
                'nama' => 'Target Uji',
                'nip' => '198765432100000009',
            ]);

        $response->assertSessionHasErrors('role_utama');
        $this->assertFalse($target->fresh()->hasRole('Super Admin'));
    }

    public function test_validasi_gagal_old_terpulihkan(): void
    {
        $this->seedRoles();

        $jenjang = JenjangJabatan::create(['nama' => 'Jenjang Uji', 'urutan' => 1]);
        PangkatGolongan::create([
            'jenjang_jabatan_id' => $jenjang->id,
            'kode_pangkat' => 'III/a',
            'nama_pangkat' => 'Lektor Muda',
            'golongan' => 'III',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.users.store'), $this->payloadValid([
                'email' => 'email-tidak-valid',
                'nip' => 'dua', // gagal: bukan angka panjang
                'jenjang_jabatan_id' => (string) $jenjang->id,
                'pangkat_golongan_id' => 'manual',
                'pangkat_golongan' => 'III/d khusus',
            ]));

        $response->assertSessionHasErrors(['email', 'pangkat_golongan_id']);
        $response->assertSessionHasInput('nama', 'Budi Santoso');
        $response->assertSessionHasInput('jenjang_jabatan_id', (string) $jenjang->id);
        $response->assertSessionHasInput('pangkat_golongan_id', 'manual');
        $response->assertSessionHasInput('pangkat_golongan', 'III/d khusus');
    }

    public function test_non_superadmin_ditolak(): void
    {
        $this->seedRoles();
        $staf = $this->buatUser('staf@gmail.com');
        $staf->assignRole('Staf TU');

        // Middleware role me-redirect (302) bila akses ditolak — bukan 403.
        $this->actingAs($staf)->get(route('superadmin.users.create'))
            ->assertStatus(302);
        $this->actingAs($staf)->post(route('superadmin.users.store'), $this->payloadValid())
            ->assertStatus(302);
        $this->assertNull(User::where('email', 'budi@gmail.com')->first());
    }

    // ── N+1: jumlah query tidak bertambah seiring jumlah role ───────────────

    public function test_jumlah_query_create_tidak_bertambah_seiring_role(): void
    {
        $this->seedRoles();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get(route('superadmin.users.create'));
        $nSedikit = count(DB::getQueryLog());

        // Tambah 25 role utama + 25 tendik baru
        for ($i = 1; $i <= 25; $i++) {
            Role::findOrCreate("Kaprodi Prodi Uji {$i}", 'web')->update(['kategori' => 'utama']);
            Role::findOrCreate("Staf Uji {$i}", 'web')->update(['kategori' => 'tendik']);
        }
        DB::flushQueryLog();

        $this->actingAs($this->admin)->get(route('superadmin.users.create'));
        $nBanyak = count(DB::getQueryLog());

        // N+1 yang dilarang: 1 query tambahan PER ROLE. Izinkan selisih kecil
        // (perkiraan batas: max +2 query total), larang pertumbuhan linear 50 query.
        $this->assertLessThanOrEqual(
            $nSedikit + 2,
            $nBanyak,
            "Jumlah query melonjak dari {$nSedikit} ke {$nBanyak} setelah 50 role ditambah (pola N+1)"
        );
    }
}
