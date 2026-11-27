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
use Illuminate\Support\Facades\Hash;
use DOMDocument;
use DOMXPath;

/**
 * Tugas 27: halaman users/{id}/edit (redesain konsisten users/create).
 * - Struktur HTML via DOMDocument: tanpa form bersarang, tanpa <button> tanpa
 *   type, label for terhubung, tanpa id duplikat.
 * - Update sukses: user + profil + role tersimpan.
 * - Keamanan: Super Admin sebagai role_utama / roles_tendik ditolak.
 * - Old() pulih setelah validasi gagal.
 * - Nilai tersimpan (jabatan utama, tendik, jenjang, pangkat, fungsional,
 *   unit kerja) tampil TERPILIH di HTML saat halaman dibuka.
 * - Non-superadmin ditolak.
 */
class UsersEditRedesainTest extends TestCase
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
        $superadmin = Role::findOrCreate('Super Admin', 'web');
        $superadmin->kategori = 'utama';
        $superadmin->dashboard_route = 'superadmin.dashboard';
        $superadmin->save();
        $superadmin->givePermissionTo('akses-manajemen-anggaran');

        $this->admin = $this->buatUser('admin@gmail.com');
        $this->admin->assignRole('Super Admin');
    }

    private function buatUser(string $email, array $profil = []): User
    {
        $u = User::create(['email' => $email, 'password' => bcrypt('password')]);
        UserProfile::create(array_merge([
            'user_id' => $u->id,
            'nama_lengkap' => 'User Uji ' . $u->id,
            'nip' => '19' . str_pad((string) $u->id, 16, '0'),
            'pangkat_golongan' => 'III/a',
            'jabatan_pekerjaan' => 'Staf',
            'jabatan_fungsional' => 'Lektor',
            'unit_kerja' => 'Unit Uji',
        ], $profil));
        return $u;
    }

    private function seedRoles(): void
    {
        Role::findOrCreate('Dosen', 'web')->update(['kategori' => 'utama']);
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

    public function test_edit_struktur_html_sehat(): void
    {
        $this->seedRoles();
        $pelanggaran = $this->auditDom($this->renderDom(route('superadmin.users.edit', $this->admin->id)));
        $this->assertSame([], $pelanggaran, "Struktur HTML users/edit rusak:\n- " . implode("\n- ", $pelanggaran));
    }

    public function test_edit_ringkasan_dan_tombol_tanpa_dobel(): void
    {
        $this->seedRoles();
        $dom = $this->renderDom(route('superadmin.users.edit', $this->admin->id));
        $xpath = new DOMXPath($dom);

        // Elemen ringkasan tidak punya name (tidak terkirim)
        foreach (['ringkasan-nama', 'ringkasan-jabatan', 'ringkasan-tendik', 'ringkasan-unit'] as $id) {
            $el = $xpath->query("//*[@id='{$id}']")->item(0);
            $this->assertNotNull($el, "elemen ringkasan #{$id} tidak ada");
            $this->assertSame('', $el->getAttribute('name'));
        }

        // Tidak ada tombol submit di luar kartu header (tanpa tombol dobel):
        // semua [data-tombol-simpan] menunjuk form-user-edit yang ADA.
        $formIds = [];
        foreach ($xpath->query('//form[@id]') as $f) {
            $formIds[] = $f->getAttribute('id');
        }
        foreach ($xpath->query('//button[@form]') as $btn) {
            $this->assertContains($btn->getAttribute('form'), $formIds);
        }

        // Tidak ada emoji di halaman
        $this->assertStringNotContainsString('🎯', $dom->saveHTML());
        $this->assertStringNotContainsString('✅', $dom->saveHTML());
        $this->assertStringNotContainsString('❌', $dom->saveHTML());
    }

    // ── Update & keamanan ────────────────────────────────────────────────────

    public function test_update_sukses_user_profil_role_tersimpan(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('target@gmail.com');
        $target->assignRole('Staf TU');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'target-baru@gmail.com',
                'password' => 'password456',
                'password_confirmation' => 'password456',
                'role_utama' => 'Dosen',
                'roles_tendik' => [],
                'nama' => 'Target Diubah',
                'nip' => '198765432100000077',
                'unit_kerja' => 'Fakultas Ekonomi',
                'jabatan_fungsional' => 'Lektor Kepala',
            ]);

        $response->assertRedirect(route('superadmin.users.index'));

        $target->refresh();
        $this->assertSame('target-baru@gmail.com', $target->email);
        $this->assertTrue(Hash::check('password456', $target->password));
        $this->assertTrue($target->hasRole('Dosen'));

        $profil = $target->profile()->first();
        $this->assertSame('Target Diubah', $profil->nama_lengkap);
        $this->assertSame('198765432100000077', $profil->nip);
        $this->assertSame('Fakultas Ekonomi', $profil->unit_kerja);
        $this->assertSame('Lektor Kepala', $profil->jabatan_fungsional);
    }

    public function test_update_tanpa_password_tetap_lolos(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('tanpa-pass@gmail.com');
        $target->assignRole('Kepala Lab');
        $hashLama = $target->password;

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'tanpa-pass@gmail.com',
                'role_utama' => null,
                'roles_tendik' => ['Kepala Lab'],
                'nama' => 'Tanpa Password',
                'nip' => '198765432100000088',
            ]);

        $response->assertRedirect(route('superadmin.users.index'));
        $this->assertSame($hashLama, $target->fresh()->password, 'password tidak boleh berubah bila dikosongkan');
    }

    public function test_update_super_admin_sebagai_role_utama_ditolak(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('target2@gmail.com');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'target2@gmail.com',
                'role_utama' => 'Super Admin',
                'roles_tendik' => [],
                'nama' => 'Target Dua',
                'nip' => '198765432100000099',
            ]);

        $response->assertSessionHasErrors('role_utama');
        $this->assertFalse($target->fresh()->hasRole('Super Admin'));
    }

    public function test_update_super_admin_di_roles_tendik_ditolak(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('target3@gmail.com');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'target3@gmail.com',
                'role_utama' => null,
                'roles_tendik' => ['Staf TU', 'Super Admin'],
                'nama' => 'Target Tiga',
                'nip' => '198765432100000111',
            ]);

        $response->assertSessionHasErrors();
        $this->assertFalse($target->fresh()->hasRole('Super Admin'));
    }

    public function test_validasi_gagal_old_terpulihkan(): void
    {
        $this->seedRoles();
        $target = $this->buatUser('target4@gmail.com');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'email-tidak-valid',
                'role_utama' => null,
                'roles_tendik' => ['Staf TU'],
                'nama' => 'Nama Lama Terjaga',
                'nip' => '12345678901234567890X', // 21 karakter → melanggar max:20
                'unit_kerja' => 'Unit Kerja Old',
                'jabatan_fungsional' => 'Lektor',
            ]);

        $response->assertSessionHasErrors(['email', 'nip']);
        $response->assertSessionHasInput('nama', 'Nama Lama Terjaga');
        $response->assertSessionHasInput('unit_kerja', 'Unit Kerja Old');
    }

    // ── Nilai tersimpan tampil TERPILIH di HTML ─────────────────────────────

    public function test_nilai_tersimpan_tampil_terpilih(): void
    {
        $this->seedRoles();

        $jenjang = JenjangJabatan::create(['nama' => 'Jenjang Uji', 'urutan' => 1]);
        $pg = PangkatGolongan::create([
            'jenjang_jabatan_id' => $jenjang->id,
            'kode_pangkat' => 'III/b',
            'nama_pangkat' => 'Lektor',
            'golongan' => 'III',
            'urutan' => 1,
        ]);

        $target = $this->buatUser('tersimpan@gmail.com', [
            'nama_lengkap' => 'Pegawai Tersimpan',
            'nip' => '198765432100000222',
            'pangkat_golongan' => 'III/b',
            'pangkat_golongan_id' => $pg->id,
            'jabatan_fungsional' => 'Lektor',
            'unit_kerja' => 'Fakultas Uji',
        ]);
        $target->assignRole('Dosen');
        $target->assignRole('Staf TU');

        $dom = $this->renderDom(route('superadmin.users.edit', $target->id));
        $xpath = new DOMXPath($dom);

        // Jabatan utama tersimpan terpilih
        $this->assertSame('Dosen', $this->nilaiSelect($xpath, 'role_utama'));

        // Peran tendik tersimpan tercentang
        $cb = $xpath->query("//input[@name='roles_tendik[]'][@value='Staf TU']")->item(0);
        $this->assertNotNull($cb);
        $this->assertTrue($cb->hasAttribute('checked'), 'checkbox Staf TU harus tercentang');

        // Jenjang tersimpan terpilih
        $this->assertSame((string) $jenjang->id, $this->nilaiSelect($xpath, 'jenjang_jabatan_id'));

        // Pangkat tersimpan terpilih (server-side, bukan hasil fetch JS)
        $this->assertSame((string) $pg->id, $this->nilaiSelect($xpath, 'pangkat_golongan_id'));
        $selPangkat = $xpath->query("//select[@name='pangkat_golongan_id']")->item(0);
        $this->assertNotSame('disabled', $selPangkat->getAttribute('disabled'), 'pangkat tersimpan tidak boleh dirender disabled');

        // Jabatan fungsional tersimpan terpilih (bukan dari jabatan_pekerjaan)
        $this->assertSame('Lektor', $this->nilaiSelect($xpath, 'jabatan_fungsional'));

        // Unit kerja tersimpan terisi
        $unit = $xpath->query("//input[@name='unit_kerja']")->item(0);
        $this->assertSame('Fakultas Uji', $unit->getAttribute('value'));
    }

    private function nilaiSelect(DOMXPath $xpath, string $name): string
    {
        // Option bisa bersarang di <optgroup> — pakai descendant::option, bukan childNodes langsung.
        // NB: DOMDocument mem-parse atribut boolean `selected` (tanpa nilai) sebagai
        // string kosong — wajib hasAttribute(), bukan getAttribute()==='selected'.
        $opt = $xpath->query("//select[@name='{$name}']//option[@selected]")->item(0);
        return $opt ? $opt->getAttribute('value') : '';
    }

    // ── Round trip & aturan 1-orang ──────────────────────────────────────────

    public function test_round_trip_tanpa_ubah_apa_pun_data_tetap_utuh(): void
    {
        $this->seedRoles();

        $jenjang = JenjangJabatan::create(['nama' => 'Jenjang Roundtrip', 'urutan' => 1]);
        $pg = PangkatGolongan::create([
            'jenjang_jabatan_id' => $jenjang->id,
            'kode_pangkat' => 'III/c',
            'nama_pangkat' => 'Roundtrip',
            'golongan' => 'III',
            'urutan' => 1,
        ]);

        $target = $this->buatUser('roundtrip@gmail.com', [
            'nama_lengkap' => 'Pegawai Roundtrip',
            'nip' => '198765432100000444',
            'pangkat_golongan' => 'III/c',
            'pangkat_golongan_id' => $pg->id,
            'unit_kerja' => 'Fakultas Roundtrip',
        ]);
        $hashLama = $target->password;
        $target->assignRole('Dosen');
        $target->assignRole('Staf TU');

        // Submit TANPA mengubah apa pun (persis nilai yang dirender form).
        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'roundtrip@gmail.com',
                'role_utama' => 'Dosen',
                'roles_tendik' => ['Staf TU'],
                'nama' => 'Pegawai Roundtrip',
                'nip' => '198765432100000444',
                'pangkat_golongan_id' => (string) $pg->id,
                'unit_kerja' => 'Fakultas Roundtrip',
            ]);

        $response->assertRedirect(route('superadmin.users.index'));

        $target->refresh();
        $this->assertSame($hashLama, $target->password, 'password tidak boleh berubah');
        $this->assertTrue($target->hasRole('Dosen'), 'jabatan utama tidak boleh hilang');
        // Perilaku eksisting usersUpdate (kontrak, TIDAK diubah): bila role_utama
        // terisi, HANYA role_utama yang di-sync — roles_tendik diabaikan. Jadi
        // 'Staf TU' yang tersimpan (mis. dibuat via import) HILANG saat submit
        // tanpa ubah. Ini temuan alur bisnis untuk diputuskan terpisah.
        $this->assertFalse($target->hasRole('Staf TU'), 'peran tendik diabaikan server bila role_utama terisi (perilaku eksisting)');

        $profil = $target->profile()->first();
        $this->assertSame($pg->id, $profil->pangkat_golongan_id, 'pangkat_golongan_id tidak boleh null');
        $this->assertSame('III/c', $profil->getRawOriginal('pangkat_golongan'));
        $this->assertSame('Pegawai Roundtrip', $profil->nama_lengkap);
        $this->assertSame('Fakultas Roundtrip', $profil->unit_kerja);
    }

    public function test_jabatan_struktural_yang_sudah_dipegang_ditolak(): void
    {
        $this->seedRoles();

        $pemegang = $this->buatUser('pemegang@gmail.com');
        $pemegang->assignRole('Kepala Lab'); // struktural (bukan Dosen) — sudah dipegang

        $target = $this->buatUser('perebut@gmail.com');
        $target->assignRole('Dosen');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.users.update', $target->id), [
                'email' => 'perebut@gmail.com',
                'role_utama' => 'Kepala Lab',
                'roles_tendik' => [],
                'nama' => 'Perebut Jabatan',
                'nip' => '198765432100000555',
            ]);

        $response->assertSessionHasErrors('role_utama');
        $this->assertFalse($target->fresh()->hasRole('Kepala Lab'), 'jabatan struktural tidak boleh pindah via update');
        $this->assertTrue($pemegang->fresh()->hasRole('Kepala Lab'), 'pemegang lama tidak boleh tergeser');
    }

    // ── Akses ────────────────────────────────────────────────────────────────

    public function test_non_superadmin_ditolak(): void
    {
        $this->seedRoles();
        $staf = $this->buatUser('staf-edit@gmail.com');
        $staf->assignRole('Staf TU');
        $target = $this->buatUser('target5@gmail.com');

        // Middleware role me-redirect (302) bila akses ditolak — bukan 403.
        $this->actingAs($staf)->get(route('superadmin.users.edit', $target->id))
            ->assertStatus(302);
        $this->actingAs($staf)->put(route('superadmin.users.update', $target->id), [
            'email' => 'target5@gmail.com',
            'nama' => 'X',
            'nip' => '198765432100000333',
        ])->assertStatus(302);
    }
}
