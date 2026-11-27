<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * TUGAS 9 — Kalender (date picker) batas pengisian & batas penilaian harus
 * terbatas sejak halaman dirender: max = tanggal selesai + 7 hari,
 * min = tanggal mulai (penilaian: batas pengisian bila terisi),
 * teks bantuan "Paling lambat {tanggal}" dalam format Indonesia.
 */
class PeriodeSkpBatasKalenderTest extends TestCase
{
    use RefreshDatabase;

    private $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->superAdmin = User::create([
            'email' => 'sa-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('Super Admin');
    }

    private function buatPeriode(array $overrides = []): PeriodeSkp
    {
        return PeriodeSkp::create(array_merge([
            'tahun' => 2026,
            'tipe' => 'tahunan',
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
            'batas_pengisian' => '2026-12-30',
            'batas_penilaian' => '2026-12-31',
            'status' => 'aktif',
        ], $overrides));
    }

    /** Halaman EDIT: atribut max = selesai + 7 hari & teks "Maks. 07/01/2027" (Tugas 14: format pendek). */
    public function test_edit_render_max_selesai_plus_7_dan_teks_paling_lambat(): void
    {
        // Periode terkunci (ada SKP) dengan selesai 2026-12-31.
        $periode = $this->buatPeriode();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));

        $response->assertOk();
        // max kedua input batas = 2026-12-31 + 7 hari = 2027-01-07.
        // (Pengisian: min=mulai, max=selesai+7; Penilaian: min=pengisian tersimpan, max=selesai+7.)
        $this->assertSame(1, substr_count($response->getContent(), 'min="2026-01-01"'));
        $this->assertSame(1, substr_count($response->getContent(), 'min="2026-12-30"'));
        $this->assertSame(2, substr_count($response->getContent(), 'max="2027-01-07"'));
        // Teks bantuan singkat format d/m/Y (Tugas 14: "Maks. {d/m/Y}" ganti "Paling lambat ...").
        $response->assertSee('Maks. 07/01/2027');
    }

    /** Halaman EDIT periode terkunci: batas tidak dalam hidden input (tetap editable). */
    public function test_edit_periode_terpakai_batas_tetap_bisa_diubah(): void
    {
        $periode = $this->buatPeriode();

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.periode-skp.update', $periode->id), [
                'tipe' => 'tahunan',
                'tahun' => 2026,
                'mulai' => '2026-01-01',
                'selesai' => '2026-12-31',
                'batas_pengisian' => '2027-01-05',
                'batas_penilaian' => '2027-01-07',
                'status' => 'aktif',
            ]);

        $response->assertRedirect(route('superadmin.periode-skp.index'));
        $response->assertSessionHas('success');

        $periode->refresh();
        $this->assertSame('2027-01-05', $periode->batas_pengisian->toDateString());
        $this->assertSame('2027-01-07', $periode->batas_penilaian->toDateString());
    }

    /** Halaman CREATE: atribut max dirender server-side dari old() (render awal). */
    public function test_create_render_dengan_old_input_memiliki_max(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.create'));

        $response->assertOk();
        // Tanpa old input selesai -> belum ada max server-side; JS yang mengisi.
        $this->assertStringNotContainsString('max="', $response->getContent());

        // Dengan old input (sesi gagal validasi), max dirender server-side.
        $responseLama = $this->actingAs($this->superAdmin)
            ->withSession(['_old_input' => [
                'tipe' => 'tahunan',
                'tahun' => '2026',
                'mulai' => '2026-01-01',
                'selesai' => '2026-12-31',
                'batas_pengisian' => '2026-12-30',
            ]])
            ->get(route('superadmin.periode-skp.create'));

        $responseLama->assertOk();
        $this->assertStringContainsString('max="2027-01-07"', $responseLama->getContent());
        // Tugas 14: teks bantuan singkat "Maks. {d/m/Y}".
        $responseLama->assertSee('Maks. 07/01/2027');
    }

    /** Server tetap menolak batas > selesai + 7 hari. */
    public function test_server_menolak_batas_lebih_dari_7_hari_setelah_selesai(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2026,
                'tipe' => 'tahunan',
                'mulai' => '2026-01-01',
                'selesai' => '2026-12-31',
                'batas_pengisian' => '2027-01-08', // selesai + 8 hari
                'batas_penilaian' => '2027-01-07',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('batas_pengisian');
        $this->assertSame(0, PeriodeSkp::where('tahun', 2026)->count());
    }

    /** Server tetap menolak batas penilaian sebelum batas pengisian. */
    public function test_server_menolak_batas_penilaian_sebelum_pengisian(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2026,
                'tipe' => 'tahunan',
                'mulai' => '2026-01-01',
                'selesai' => '2026-12-31',
                'batas_pengisian' => '2027-01-07',
                'batas_penilaian' => '2027-01-06',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('batas_penilaian');
    }

    /** Peringatan merah dirender bila old input batas di luar rentang. */
    public function test_create_render_peringatan_merah_batas_di_luar_rentang(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['_old_input' => [
                'tipe' => 'tahunan',
                'tahun' => '2026',
                'mulai' => '2026-01-01',
                'selesai' => '2026-12-31',
                'batas_pengisian' => '2027-02-01', // di luar selesai + 7
                'batas_penilaian' => '2027-02-05',
            ]])
            ->get(route('superadmin.periode-skp.create'));

        $response->assertOk();
        $response->assertSee('peringatan-batas-pengisian');
        $response->assertSee('peringatan-batas-penilaian');
        $response->assertSee('di luar rentang yang diizinkan');
    }
}
