<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DOMDocument;
use DOMXPath;

/**
 * Struktur HTML form/button di halaman-halaman daftar tervalidasi dengan
 * DOMDocument (parser nyata, bukan regex — regex terbukti keliru di Tugas 24):
 * (a) tidak ada <form> yang punya <form> keturunan (nested form invalid);
 * (b) tidak ada <button> TANPA type= di dalam <form> (default submit tersembunyi);
 * (c) setiap input name="ids[]" berada di dalam <form> (parser HTML menutup
 *     form lebih awal bila nested, sehingga input bisa "yatim");
 * (d) tidak ada id form duplikat.
 * NB: validasi (b) di sini HANYA untuk halaman daftar yang diuji, bukan seluruh views.
 */
class FormStructureHtmlTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['akses-dashboard-pimpinan', 'akses-dashboard-prodi', 'akses-manajemen-anggaran'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $superadmin = Role::findOrCreate('Super Admin', 'web');
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

    /** Render halaman lalu kembalikan DOMDocument hasil parse nyata. */
    private function renderDom(string $halaman, array $data = []): DOMDocument
    {
        $response = $this->actingAs($this->admin)->get($halaman, $data);
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        return $dom;
    }

    /** Kumpulkan semua pelanggaran struktur form/button dari sebuah DOM. */
    private function auditDom(DOMDocument $dom): array
    {
        $pelanggaran = [];
        $xpath = new DOMXPath($dom);

        // (a) nested form: form yang punya form keturunan
        foreach ($xpath->query('//form[descendant::form]') as $form) {
            $pelanggaran[] = 'nested <form> di dalam <form id="' . $form->getAttribute('id') . '">';
        }

        // (d) id form duplikat
        $ids = [];
        foreach ($xpath->query('//form[@id]') as $form) {
            $ids[$form->getAttribute('id')] = ($ids[$form->getAttribute('id')] ?? 0) + 1;
        }
        foreach ($ids as $id => $n) {
            if ($n > 1) $pelanggaran[] = "id form duplikat: {$id} ({$n}x)";
        }

        // (c) input ids[] yatim: tidak punya ancestor form
        foreach ($xpath->query('//input[@name="ids[]"]') as $input) {
            if (!$xpath->query('ancestor::form', $input)->length) {
                $pelanggaran[] = 'input ids[] yatim (di luar <form> apa pun)';
            }
        }

        // (b) button tanpa type= di dalam form (default = submit tersembunyi).
        // Kecuali: tombol yang MENUNJUK form lain via attribute form= (disengaja).
        foreach ($xpath->query('//form//button[not(@type)]') as $btn) {
            if ($btn->getAttribute('form') !== '') continue; // menunjuk form lain, disengaja
            $pelanggaran[] = '<button> tanpa type= di dalam <form id="' .
                $btn->getAttribute('form') . '" konten="' . trim($btn->textContent) . '">';
        }

        return $pelanggaran;
    }

    private function assertStrukturSehat(string $label, string $halaman, array $data = []): void
    {
        $dom = $this->renderDom($halaman, $data);
        $pelanggaran = $this->auditDom($dom);
        $this->assertSame([], $pelanggaran, "Struktur HTML rusak di [{$label}]:\n- " . implode("\n- ", $pelanggaran));
    }

    // ── Halaman daftar utama ────────────────────────────────────────────────

    public function test_kelola_users_struktur_form_sehat(): void
    {
        $this->buatUser('dosen1@gmail.com');
        $this->assertStrukturSehat('superadmin.users.index', route('superadmin.users.index'));
    }

    public function test_kelola_users_bulk_delete_ids_terasosiasi_ke_form_bulk(): void
    {
        $u1 = $this->buatUser('u1@gmail.com');
        $u2 = $this->buatUser('u2@gmail.com');
        $dom = $this->renderDom(route('superadmin.users.index'));

        $xpath = new DOMXPath($dom);

        // Checkbox ids[] HARUS di dalam form bulk (bulk delete via DOM mengirim ids[])
        $formBulk = $xpath->query('//form[@id="bulk-delete-form"]');
        $this->assertSame(1, $formBulk->length, 'form bulk-delete-form tidak ada / duplikat');

        $idsDalamBulk = $xpath->query('.//input[@name="ids[]"]', $formBulk->item(0));
        $this->assertGreaterThanOrEqual(2, $idsDalamBulk->length, 'checkbox ids[] TIDAK terasosiasi ke form bulk (nested form?)');

        // Tombol hapus per-baris menunjuk form hapus-user-{id} yang ADA
        $semuaFormIds = [];
        foreach ($xpath->query('//form[@id]') as $f) {
            $semuaFormIds[] = $f->getAttribute('id');
        }
        foreach ($xpath->query('//button[@form]') as $btn) {
            $target = $btn->getAttribute('form');
            $this->assertContains($target, $semuaFormIds, "Tombol Hapus menunjuk form '{$target}' yang TIDAK ada di DOM");
        }
    }

    public function test_kelola_roles_struktur_form_sehat(): void
    {
        $this->assertStrukturSehat('superadmin.roles.index', route('superadmin.roles.index'));
    }

    public function test_kelola_prodi_struktur_form_sehat(): void
    {
        $this->assertStrukturSehat('superadmin.prodi.index', route('superadmin.prodi.index'));
    }

    public function test_periode_skp_struktur_form_sehat(): void
    {
        $this->assertStrukturSehat('superadmin.periode-skp.index', route('superadmin.periode-skp.index'));
    }

    public function test_program_kegiatan_struktur_form_sehat(): void
    {
        $this->assertStrukturSehat('program-kegiatan.index', route('program-kegiatan.index'));
    }

    public function test_master_skp_tendik_struktur_form_sehat(): void
    {
        $this->assertStrukturSehat('superadmin.master-skp-tendik.index', route('superadmin.master-skp-tendik.index'));
    }

    // ── Parser juga dipakai memverifikasi turunan form di halaman roles ─────

    public function test_kelola_roles_tombol_hapus_menunjuk_form_yang_ada(): void
    {
        Role::findOrCreate('Staf Uji Parser', 'web')->update(['dashboard_route' => 'pegawai.dashboard']);
        $dom = $this->renderDom(route('superadmin.roles.index'));

        $xpath = new DOMXPath($dom);
        $semuaFormIds = [];
        foreach ($xpath->query('//form[@id]') as $f) {
            $semuaFormIds[] = $f->getAttribute('id');
        }
        foreach ($xpath->query('//button[@form]') as $btn) {
            $this->assertContains($btn->getAttribute('form'), $semuaFormIds);
        }
    }
}
