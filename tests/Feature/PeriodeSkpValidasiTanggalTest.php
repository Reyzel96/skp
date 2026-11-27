<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodeSkpValidasiTanggalTest extends TestCase
{
    use RefreshDatabase;

    private $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Role middleware butuh tabel role/permission (RefreshDatabase migrasi ulang).
        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->superAdmin = User::create([
            'email' => 'sa-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('Super Admin');
    }

    /** Payload dasar; key yang sama di $overrides menimpa. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'status' => 'draft',
        ], $overrides);
    }

    /** Kasus pokok: pengisian & penilaian SETELAH tanggal selesai (04-01 & 04-05) -> lolos. */
    public function test_batas_setelah_selesai_hingga_7_hari_diterima(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_pengisian' => '2026-04-01',
                'batas_penilaian' => '2026-04-05',
            ]));

        $response->assertRedirect(route('superadmin.periode-skp.index'));
        $response->assertSessionHas('success');

        $periode = PeriodeSkp::where('tahun', 2026)->first();
        $this->assertNotNull($periode);
        $this->assertSame('2026-04-01', $periode->batas_pengisian->toDateString());
        $this->assertSame('2026-04-05', $periode->batas_penilaian->toDateString());
    }

    /** Batas tepat selesai+7 (04-07) -> lolos; selesai+8 (04-08) -> ditolak. Dites terpisah utk pengisian & penilaian. */
    public function test_batas_tepat_7_hari_lolos_dan_8_hari_ditolak(): void
    {
        // Pengisian 04-07 (tepat) -> lolos (rentang beda agar lolos rule unique tahun+mulai+selesai)
        $r = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'mulai' => '2026-02-01',
                'selesai' => '2026-04-30',
                'batas_pengisian' => '2026-05-07',
            ]));
        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');

        // Pengisian 04-08 -> ditolak
        $r = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_pengisian' => '2026-04-08',
            ]));
        $r->assertSessionHasErrors(['batas_pengisian']);
        $this->assertStringContainsString(
            'maksimal 7 hari setelah tanggal selesai',
            session('errors')->get('batas_pengisian')[0]
        );

        // Penilaian 04-07 (tepat) -> lolos
        $r = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'mulai' => '2026-05-01',
                'selesai' => '2026-06-30',
                'batas_penilaian' => '2026-07-07',
            ]));
        $r->assertSessionHas('success');

        // Penilaian 04-08 -> ditolak
        $r = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_penilaian' => '2026-04-08',
            ]));
        $r->assertSessionHasErrors(['batas_penilaian']);
        $this->assertStringContainsString(
            'maksimal 7 hari setelah tanggal selesai',
            session('errors')->get('batas_penilaian')[0]
        );
    }

    /** Ditolak: pengisian < mulai. */
    public function test_pengisian_sebelum_mulai_ditolak(): void
    {
        $r = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_pengisian' => '2025-12-31',
            ]));

        $r->assertSessionHasErrors(['batas_pengisian']);
        $this->assertStringContainsString(
            'tidak boleh sebelum tanggal mulai',
            session('errors')->get('batas_pengisian')[0]
        );
    }

    /** Ditolak: penilaian < pengisian. */
    public function test_penilaian_sebelum_pengisian_ditolak(): void
    {
        $r = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_pengisian' => '2026-04-03',
                'batas_penilaian' => '2026-04-02',
            ]));

        $r->assertSessionHasErrors(['batas_penilaian']);
        $this->assertStringContainsString(
            'tidak boleh sebelum batas akhir pengisian',
            session('errors')->get('batas_penilaian')[0]
        );
    }

    /** Ditolak: selesai <= mulai (rule after). */
    public function test_selesai_tidak_boleh_lewat_mulai(): void
    {
        // selesai == mulai
        $r = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'mulai' => '2026-03-31',
                'selesai' => '2026-03-31',
            ]));
        $r->assertSessionHasErrors(['selesai']);

        // selesai < mulai
        $r = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'mulai' => '2026-03-31',
                'selesai' => '2026-03-01',
            ]));
        $r->assertSessionHasErrors(['selesai']);
    }

    /** Batas NULL: aturan yang menyangkut batas itu dilewati. */
    public function test_batas_null_dilewati(): void
    {
        $r = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload());

        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');
        $periode = PeriodeSkp::where('tahun', 2026)->first();
        $this->assertNotNull($periode);
        $this->assertNull($periode->batas_pengisian);
        $this->assertNull($periode->batas_penilaian);
    }

    /** Edit periode ID 2 (batas di dalam rentang) tetap bisa disimpan. */
    public function test_edit_periode_dengan_batas_dalam_rentang_tetap_lolos(): void
    {
        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-08-01',
            'selesai' => '2026-10-22',
            'batas_pengisian' => '2026-10-20',
            'batas_penilaian' => '2026-10-21',
            'status' => 'aktif',
        ]);

        $r = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload([
                'mulai' => '2026-08-01',
                'selesai' => '2026-10-22',
                'batas_pengisian' => '2026-10-20',
                'batas_penilaian' => '2026-10-21',
            ]));

        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');
    }

    /** Konfigurasi 7 -> 10: batas 04-09 kini lolos (membuktikan tidak ada 7 hardcode di validasi). */
    public function test_config_diubah_ke_10_validasi_ikut(): void
    {
        config(['periode.maks_hari_setelah_selesai' => 10]);

        // 04-08 sebelumnya ditolak, dengan config 10 lolos.
        $r = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'batas_pengisian' => '2026-04-08',
                'batas_penilaian' => '2026-04-09',
            ]));

        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');
    }

    /** Konfigurasi 7 -> 10: atribut max di view create & edit ikut berubah (membuktikan Blade tidak hardcode 7). */
    public function test_config_diubah_ke_10_atribut_max_view_ikut(): void
    {
        config(['periode.maks_hari_setelah_selesai' => 10]);

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-08-01',
            'selesai' => '2026-10-22',
            'status' => 'draft',
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.create'))
            ->assertOk()
            ->assertSee('MAKS_HARI_SETELAH_SELESAI = 10', false);

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id))
            ->assertOk()
            ->assertSee('MAKS_HARI_SETELAH_SELESAI = 10', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-close: periode:auto-close
    |--------------------------------------------------------------------------
    */

    /** Selesai lewat tapi penilaian belum -> TIDAK ditutup. */
    public function test_autoclose_tidak_menutup_jika_penilaian_belum_lewat(): void
    {
        $this->travelTo('2026-04-02');

        PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'batas_pengisian' => '2026-04-01',
            'batas_penilaian' => '2026-04-05',
            'status' => 'aktif',
        ]);

        Artisan::call('periode:auto-close');

        $this->assertDatabaseHas('periode_skp', ['status' => 'aktif']);
    }

    /** Semua tanggal lewat -> ditutup. */
    public function test_autoclose_menutup_jika_semua_tanggal_lewat(): void
    {
        $this->travelTo('2026-04-10');

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'batas_pengisian' => '2026-04-01',
            'batas_penilaian' => '2026-04-05',
            'status' => 'aktif',
        ]);

        Artisan::call('periode:auto-close');

        $this->assertDatabaseHas('periode_skp', [
            'id' => $periode->id,
            'status' => 'selesai',
        ]);
    }

    /** Batas NULL -> jatuh ke tanggal_selesai. */
    public function test_autoclose_batas_null_jatuh_ke_tanggal_selesai(): void
    {
        $this->travelTo('2026-04-02');

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'status' => 'aktif',
        ]);

        Artisan::call('periode:auto-close');

        $this->assertDatabaseHas('periode_skp', [
            'id' => $periode->id,
            'status' => 'selesai',
        ]);
    }

    /** Tepat di hari batas tutup terakhir: belum ditutup (inklusif). */
    public function test_autoclose_tepat_hari_batas_belum_ditutup(): void
    {
        $this->travelTo('2026-04-05');

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'batas_pengisian' => '2026-04-01',
            'batas_penilaian' => '2026-04-05',
            'status' => 'aktif',
        ]);

        Artisan::call('periode:auto-close');

        $this->assertDatabaseHas('periode_skp', [
            'id' => $periode->id,
            'status' => 'aktif',
        ]);
    }
}
