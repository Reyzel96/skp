<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 6: periode bulanan bergantian tidak lagi menutup paksa periode lama
 * yang jendela penilaiannya masih terbuka; penutupan paksa (bila jendela sudah
 * lewat) dilakukan per model sehingga DistribusiRiil::freeze terpicu.
 */
class PeriodeBulananBergantianTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');

        Carbon::setTestNow('2026-04-02 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function buatTriwulanSatu(): PeriodeSkp
    {
        return PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'bulanan',
            'bulan' => 1,
            'bulan_akhir' => 3,
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'batas_pengisian' => '2026-03-31',
            'batas_penilaian' => '2026-04-07',
            'status' => 'aktif',
        ]);
    }

    private function payloadTriwulanDua(array $overrides = []): array
    {
        return array_merge([
            'tahun' => '2026',
            'tipe' => 'bulanan',
            'bulan' => '4',
            'bulan_akhir' => '6',
            'mulai' => '2026-04-01',
            'selesai' => '2026-06-30',
            'batas_pengisian' => '2026-06-30',
            'batas_penilaian' => '2026-07-07',
            'status' => 'aktif',
        ], $overrides);
    }

    // =========================================================================
    // Jendela penilaian periode lama masih terbuka → dibiarkan aktif
    // =========================================================================

    public function test_update_status_tidak_menutup_periode_lama_yang_jendela_masih_terbuka(): void
    {
        $tw1 = $this->buatTriwulanSatu();
        $tw2 = PeriodeSkp::create(array_merge($this->payloadTriwulanDua(), ['status' => 'draft']));

        $response = $this->actingAs($this->admin)
            ->patch(route('superadmin.periode-skp.update-status', $tw2), ['status' => 'aktif']);

        $response->assertRedirect();
        $this->assertSame('aktif', $tw1->fresh()->status, 'TW I harus tetap aktif: batas penilaian 04-07 belum lewat.');
        $this->assertSame('aktif', $tw2->fresh()->status);
        $this->assertNull($tw1->fresh()->distribusi_riil_dihitung_pada, 'TW I tidak boleh di-freeze.');
    }

    public function test_store_tidak_menutup_periode_lama_yang_jendela_masih_terbuka(): void
    {
        $tw1 = $this->buatTriwulanSatu();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.periode-skp.store'), $this->payloadTriwulanDua());

        $response->assertRedirect();
        $this->assertSame('aktif', $tw1->fresh()->status, 'TW I harus tetap aktif setelah TW II dibuat.');
        $this->assertNull($tw1->fresh()->distribusi_riil_dihitung_pada);
        $this->assertSame('aktif', PeriodeSkp::where('bulan', 4)->where('bulan_akhir', 6)->first()->status);
    }

    public function test_update_tidak_menutup_periode_lama_yang_jendela_masih_terbuka(): void
    {
        $tw1 = $this->buatTriwulanSatu();
        $tw2 = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => 4, 'bulan_akhir' => 6,
            'mulai' => '2026-04-01', 'selesai' => '2026-06-30', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $tw2), [
                'tahun' => '2026', 'tipe' => 'bulanan', 'bulan' => '4', 'bulan_akhir' => '6',
                'mulai' => '2026-04-01', 'selesai' => '2026-06-30', 'status' => 'aktif',
            ]);

        $response->assertRedirect();
        $this->assertSame('aktif', $tw1->fresh()->status);
        $this->assertNull($tw1->fresh()->distribusi_riil_dihitung_pada);
        $this->assertSame('aktif', $tw2->fresh()->status);
    }

    // =========================================================================
    // Jendela periode lama sudah lewat → ditutup paksa + freeze SEKALI
    // =========================================================================

    public function test_penutupan_paksa_periode_lama_memicu_freeze_sekali(): void
    {
        $pegawai = User::create([
            'name' => 'Pegawai',
            'email' => 'pgw'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $tw1 = $this->buatTriwulanSatu();
        SkpHeader::create([
            'periode_skp_id' => $tw1->id,
            'user_id' => $pegawai->id,
            'status' => 'Selesai',
            'predikat_kinerja' => 'BAIK',
        ]);
        $tw2 = PeriodeSkp::create(array_merge($this->payloadTriwulanDua(), ['status' => 'draft']));

        Carbon::setTestNow('2026-04-08 08:00:00');

        $response = $this->actingAs($this->admin)
            ->patch(route('superadmin.periode-skp.update-status', $tw2), ['status' => 'aktif']);

        $response->assertRedirect();
        $tw1Fresh = $tw1->fresh();
        $this->assertSame('selesai', $tw1Fresh->status, 'TW I (batas tutup lewat) harus ditutup paksa.');
        $this->assertNotNull($tw1Fresh->distribusi_riil_dihitung_pada, 'Koreksi 2: penutupan paksa per-model harus memicu freeze.');
        $this->assertNotNull($tw1Fresh->distribusi_riil, 'Distribusi harus terisi dari header berpredikat.');
    }

    public function test_auto_close_menutup_periode_lama_dan_memicu_freeze(): void
    {
        $pegawai = User::create([
            'name' => 'Pegawai 2',
            'email' => 'pgw2'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $tw1 = $this->buatTriwulanSatu();
        SkpHeader::create([
            'periode_skp_id' => $tw1->id,
            'user_id' => $pegawai->id,
            'status' => 'Selesai',
            'predikat_kinerja' => 'BAIK',
        ]);
        $tw2 = $this->buatTriwulanDuaAktifTanpaController();

        Carbon::setTestNow('2026-04-08 08:00:00');
        Artisan::call('periode:auto-close');

        $this->assertSame('selesai', $tw1->fresh()->status);
        $this->assertNotNull($tw1->fresh()->distribusi_riil_dihitung_pada, 'auto-close memicu freeze via model event.');
    }

    public function test_penutupan_paksa_dan_auto_close_tidak_membekukan_dua_kali(): void
    {
        $pegawai = User::create([
            'name' => 'Pegawai 3',
            'email' => 'pgw3'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $tw1 = $this->buatTriwulanSatu();
        SkpHeader::create([
            'periode_skp_id' => $tw1->id,
            'user_id' => $pegawai->id,
            'status' => 'Selesai',
            'predikat_kinerja' => 'BAIK',
        ]);
        $tw2 = PeriodeSkp::create(array_merge($this->payloadTriwulanDua(), ['status' => 'draft']));

        Carbon::setTestNow('2026-04-08 08:00:00');

        // Jalur 1: penutupan paksa saat TW II diaktifkan
        $this->actingAs($this->admin)
            ->patch(route('superadmin.periode-skp.update-status', $tw2), ['status' => 'aktif']);
        $freeze1 = $tw1->fresh()->distribusi_riil_dihitung_pada;
        $this->assertNotNull($freeze1);
        $this->assertSame('selesai', $tw1->fresh()->status);

        // Jalur 2: auto-close — periode sudah selesai, tidak boleh mengubah apa pun
        Artisan::call('periode:auto-close');

        $this->assertSame($freeze1, $tw1->fresh()->distribusi_riil_dihitung_pada, 'Freeze tidak boleh dijalankan dua kali.');
        $this->assertSame('selesai', $tw1->fresh()->status);
    }

    // =========================================================================
    // Tipe berbeda tidak saling menutup
    // =========================================================================

    public function test_tipe_berbeda_tidak_saling_menutup(): void
    {
        $tw1 = $this->buatTriwulanSatu();
        $tahunan = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'semester_genap' => 'Semester Genap 2025/2026', 'semester_gasal' => 'Semester Gasal 2026/2027',
            'mulai' => '2026-01-05', 'selesai' => '2026-02-28', 'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('superadmin.periode-skp.update-status', $tahunan), ['status' => 'aktif']);

        $this->assertSame('aktif', $tw1->fresh()->status, 'Bulanan tidak boleh ditutup oleh tahunan.');
        $this->assertSame('aktif', $tahunan->fresh()->status);
    }

    // =========================================================================
    // isPenilaianTerbuka kini cek status
    // =========================================================================

    public function test_is_penilaian_terbuka_false_untuk_draft_dan_selesai(): void
    {
        $draft = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => 7, 'bulan_akhir' => 9,
            'mulai' => '2026-07-01', 'selesai' => '2026-09-30',
            'batas_penilaian' => '2027-12-31', 'status' => 'draft',
        ]);
        $selesai = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => 10, 'bulan_akhir' => 12,
            'mulai' => '2026-10-01', 'selesai' => '2026-12-31',
            'batas_penilaian' => '2027-12-31', 'status' => 'selesai',
        ]);

        $this->assertFalse($draft->isPenilaianTerbuka(), 'Draft tertutup meski batas masa depan.');
        $this->assertFalse($selesai->isPenilaianTerbuka(), 'Selesai tertutup meski batas masa depan.');
    }

    public function test_is_penilaian_terbuka_true_untuk_aktif_batas_masa_depan(): void
    {
        $tw1 = $this->buatTriwulanSatu();
        $this->assertTrue($tw1->isPenilaianTerbuka(), 'Aktif + batas 04-07 > now 04-02 → terbuka.');
    }

    // =========================================================================
    // Default periode skpTendikIndex / skpTendikCreate deterministik
    // =========================================================================

    public function test_default_periode_skp_tendik_memilih_mulai_terbaru(): void
    {
        $tw1 = $this->buatTriwulanSatu();
        $tw2 = $this->buatTriwulanDuaAktifTanpaController();

        // Route superadmin.skp-tendik.index kini redirect ke master-skp-tendik/create
        // (routing berubah sejak investigasi awal — temuan baru).
        $responseIndex = $this->actingAs($this->admin)
            ->get(route('superadmin.skp-tendik.index'));
        $responseIndex->assertRedirect();
        $this->assertStringContainsString(
            'master-skp-tendik/create',
            $responseIndex->headers->get('Location')
        );

        // Controller skpTendikCreate (route superadmin.skp-tendik.create, view
        // create_form.blade.php) tetap ada dan memakai default periode aktif.
        // Perilakunya dites langsung di level method: 2 aktif → default = mulai terbaru.
        $controller = new \App\Http\Controllers\SuperAdmin\SuperAdminController();
        $response = $controller->skpTendikCreate(new \Illuminate\Http\Request());

        $viewData = $response->getData();
        $this->assertSame($tw2->id, $viewData['periodeId'], 'Default periode create SKP tendik harus periode aktif mulai terbaru (TW II).');
        // TW I tetap ada di daftar dropdown, urut setelah TW II
        $this->assertEquals(
            [$tw2->id, $tw1->id],
            $viewData['periode']->pluck('id')->all()
        );
    }

    // =========================================================================
    // periodeBentrok (dikunci, tidak berubah): 04-01 vs 04-01 = bentrok
    // =========================================================================

    public function test_periode_bentrok_menolak_tumpang_tindih_tanggal(): void
    {
        // TW I selesai 04-01, TW II mulai 04-01 → overlap → ditolak.
        // periodeBentrok memakai flash 'error' (bukan validation error bag).
        PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => 1, 'bulan_akhir' => 4,
            'mulai' => '2026-01-01', 'selesai' => '2026-04-01', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payloadTriwulanDua());

        $response->assertSessionHas('error');
        $this->assertSame(1, PeriodeSkp::where('tipe', 'bulanan')->count(), 'TW II tidak boleh tersimpan.');
    }

    public function test_periode_berturutan_tanpa_overlap_diizinkan(): void
    {
        // TW I selesai 03-31, TW II mulai 04-01 → tidak overlap → diizinkan
        $this->buatTriwulanSatu();

        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.periode-skp.store'), $this->payloadTriwulanDua());

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(2, PeriodeSkp::where('tipe', 'bulanan')->count());
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function buatTriwulanDuaAktifTanpaController(): PeriodeSkp
    {
        // Dibuat langsung lewat model (bukan controller) untuk simulasi dua-aktif:
        // sesuai perilaku baru, controller tidak pernah menghasilkan dua aktif
        // ketika jendela masih terbuka.
        return PeriodeSkp::create(array_merge($this->payloadTriwulanDua(), ['status' => 'aktif']));
    }
}
