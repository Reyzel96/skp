<?php

namespace Tests\Feature;

use App\Models\MasterSkpTendik;
use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\SkpPerilakuIndicator;
use App\Models\SkpTendik;
use App\Models\SkpTendikDetail;
use App\Models\SkpTugasTambahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 4: memastikan alur penilai/approval selalu terikat pada header yang
 * diminta (route param / skp_header_id aktivitas), bukan header "terbaru".
 */
class MasterSkpApprovalAmbiguFixTest extends TestCase
{
    use RefreshDatabase;

    private User $penilai;

    private User $pegawai;

    private User $admin;

    private PeriodeSkp $periodeA;

    private PeriodeSkp $periodeB;

    private SkpHeader $headerA;

    private SkpHeader $headerB;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'Dekan']);
        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');

        $this->penilai = User::create([
            'name' => 'Penilai',
            'email' => 'penilai'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->penilai->assignRole('Dekan');

        $this->pegawai = User::create([
            'name' => 'Pegawai Tendik',
            'email' => 'pegawai'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);

        // Dua periode aktif bersamaan (kondisi nyata di DB lokal)
        $this->periodeA = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2026-08-01', 'selesai' => '2026-10-22', 'status' => 'aktif',
        ]);
        $this->periodeB = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'mulai' => '2026-07-01', 'selesai' => '2026-09-30', 'status' => 'aktif',
        ]);

        $this->headerA = SkpHeader::create([
            'periode_skp_id' => $this->periodeA->id,
            'user_id' => $this->pegawai->id,
            'status' => 'Draft',
        ]);
        $this->headerB = SkpHeader::create([
            'periode_skp_id' => $this->periodeB->id,
            'user_id' => $this->pegawai->id,
            'status' => 'Draft',
        ]);
    }

    private function newActivityPayload(): array
    {
        return [
            'new_activities' => [[
                'role_name' => 'Staf',
                'tupoksi' => 'Tupoksi Baru',
                'jenis_aktivitas' => 'Kegiatan Baru '.uniqid(),
                'penilai_role' => 'Dekan',
                'periode' => 'bulanan',
                'frekuensi_per_tahun' => 2,
                'durasi_menit' => 30,
                'jumlah_sdm' => 1,
                'target_ak' => 1.5,
                'target_kuantitas' => 10,
                'target_kualitas' => 90,
                'target_waktu' => 8,
                'target_biaya' => 0,
            ]],
        ];
    }

    // =========================================================================
    // R1: updateAll terikat header dari route param
    // =========================================================================

    public function test_r1_new_activities_terikat_ke_header_dari_route_bukan_terbaru(): void
    {
        // Header A lebih tua; header B terbaru. Simpan lewat route header A.
        $response = $this->actingAs($this->penilai)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), $this->newActivityPayload());

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $master = MasterSkpTendik::where('jenis_aktivitas', 'like', 'Kegiatan Baru %')->sole();
        $this->assertSame($this->headerA->id, (int) $master->skp_header_id, 'Master harus terikat header A (route param), bukan header terbaru.');
        $this->assertSame($this->pegawai->id, (int) $master->user_id);

        $detail = SkpTendikDetail::where('jenis_aktivitas', $master->jenis_aktivitas)->sole();
        $this->assertSame(
            $this->periodeA->id,
            (int) $detail->skpTendik->periode_skp_id,
            'Detail harus masuk periode A, bukan periode header terbaru.'
        );
    }

    public function test_r1_new_tugas_terikat_ke_periode_header_route(): void
    {
        $response = $this->actingAs($this->penilai)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), [
                'new_tugas' => [[
                    'jenis' => 'Penunjang',
                    'kegiatan' => 'Tugas Tambahan Uji',
                    'ak' => 1,
                    'ak_realisasi' => 1,
                ]],
            ]);

        $response->assertRedirect();

        $tugas = SkpTugasTambahan::where('kegiatan', 'Tugas Tambahan Uji')->sole();
        $this->assertSame($this->periodeA->id, (int) $tugas->periode_skp_id);
        $this->assertSame($this->pegawai->id, (int) $tugas->user_id);
    }

    public function test_r1_perilaku_ditulis_ke_header_route_bukan_header_terbaru(): void
    {
        // Buat indikator lama di header B (terbaru) untuk memastikan tidak tersentuh
        SkpPerilakuIndicator::create([
            'skp_header_id' => $this->headerB->id,
            'aspek' => 'Integritas',
            'indikator' => 'Indikator lama header B',
            'bobot_persen' => 100,
        ]);

        $response = $this->actingAs($this->penilai)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), [
                'perilaku' => [
                    'Integritas' => [[
                        'indikator' => 'Indikator baru header A',
                        'nilai' => 80,
                        'bobot_persen' => 100,
                    ]],
                ],
            ]);

        $response->assertRedirect();

        // Header A: berisi indikator baru
        $this->assertSame(1, SkpPerilakuIndicator::where('skp_header_id', $this->headerA->id)->count());
        $this->assertSame(
            'Indikator baru header A',
            SkpPerilakuIndicator::where('skp_header_id', $this->headerA->id)->value('indikator')
        );
        // Header B: tidak tersentuh
        $this->assertSame(
            'Indikator lama header B',
            SkpPerilakuIndicator::where('skp_header_id', $this->headerB->id)->value('indikator'),
            'Perilaku header lain (terbaru) tidak boleh ikut terhapus/tertimpa.'
        );
    }

    public function test_r1_tidak_ada_lagi_error_header_tidak_ditemukan_pada_alur_normal(): void
    {
        $response = $this->actingAs($this->penilai)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), [
                'new_activities' => [[
                    'role_name' => 'Staf',
                    'tupoksi' => 'Tupoksi',
                    'jenis_aktivitas' => 'Aktivitas Normal '.uniqid(),
                    'penilai_role' => 'Dekan',
                    'periode' => 'bulanan',
                    'frekuensi_per_tahun' => 1,
                    'durasi_menit' => 60,
                    'jumlah_sdm' => 1,
                ]],
                'new_tugas' => [[
                    'jenis' => 'Penunjang',
                    'kegiatan' => 'Kegiatan Normal',
                ]],
                'perilaku' => [
                    'Disiplin' => [['indikator' => 'Tepat waktu', 'nilai' => 70]],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');
    }

    public function test_r1_otorisasi_user_bukan_penilai_atau_atasan_ditolak(): void
    {
        $orangLain = User::create([
            'name' => 'Reviewer Lain',
            'email' => 'lain'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        // Reviewer di mata middleware controller, tapi bukan pejabat penilai/atasan header
        Role::firstOrCreate(['name' => 'Kaprodi']);
        $orangLain->assignRole('Kaprodi');

        $response = $this->actingAs($orangLain)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), $this->newActivityPayload());

        $response->assertStatus(403);
        $this->assertSame(0, MasterSkpTendik::count());
    }

    public function test_r1_bypass_role_dekan_tetap_lolos(): void
    {
        $response = $this->actingAs($this->penilai) // penilai = Dekan (bypass)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerB->id), $this->newActivityPayload());

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $master = MasterSkpTendik::sole();
        $this->assertSame($this->headerB->id, (int) $master->skp_header_id);
    }

    public function test_r1_blok_items_tetap_berjalan(): void
    {
        $activity = MasterSkpTendik::create([
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $this->headerA->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi Lama',
            'jenis_aktivitas' => 'Aktivitas Lama',
            'penilai_role' => 'Dekan',
            'kategori' => ['Utama'],
            'periode' => 'bulanan',
            'frekuensi_per_tahun' => 1,
            'durasi_menit' => 60,
            'jumlah_sdm' => 1,
            'status_approval' => 'approved',
        ]);

        $skpTendik = SkpTendik::create([
            'periode_skp_id' => $this->periodeA->id,
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $this->headerA->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi Lama',
        ]);
        SkpTendikDetail::create([
            'skp_tendik_id' => $skpTendik->id,
            'jenis_aktivitas' => 'Aktivitas Lama',
        ]);

        $response = $this->actingAs($this->penilai)
            ->post(route('penilai.approval-skp-tendik.bulk-update', $this->headerA->id), [
                'items' => [
                    $activity->id => [
                        'role_name' => 'Kasubag',
                        'tupoksi' => 'Tupoksi Baru',
                        'jenis_aktivitas' => 'Aktivitas Lama',
                        'frekuensi_per_tahun' => 4,
                        'durasi_menit' => 90,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $activity->refresh();
        $this->assertSame('Kasubag', $activity->role_name);
        $this->assertSame(4, (int) $activity->frekuensi_per_tahun);
    }

    // =========================================================================
    // R2: destroyActivity terikat skp_header_id aktivitas
    // =========================================================================

    private function buatAktivitasDenganDetail(SkpHeader $header, string $jenis): MasterSkpTendik
    {
        $activity = MasterSkpTendik::create([
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $header->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi '.$jenis,
            'jenis_aktivitas' => $jenis,
            'penilai_role' => 'Dekan',
            'kategori' => ['Utama'],
            'periode' => 'bulanan',
            'frekuensi_per_tahun' => 1,
            'durasi_menit' => 60,
            'jumlah_sdm' => 1,
            'status_approval' => 'approved',
        ]);

        $skpTendik = SkpTendik::create([
            'periode_skp_id' => $header->periode_skp_id,
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $header->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi '.$jenis,
        ]);
        SkpTendikDetail::create([
            'skp_tendik_id' => $skpTendik->id,
            'jenis_aktivitas' => $jenis,
        ]);

        return $activity;
    }

    public function test_r2_hapus_aktivitas_header_a_tidak_menyentuh_header_b(): void
    {
        $aktifA = $this->buatAktivitasDenganDetail($this->headerA, 'Aktivitas A');
        $aktifB = $this->buatAktivitasDenganDetail($this->headerB, 'Aktivitas B');

        // Header B adalah header terbaru milik pegawai; hapus aktivitas milik header A.
        $response = $this->actingAs($this->pegawai)
            ->delete(route('pegawai.skp.destroy-activity', $aktifA->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('master_skp_tendiks', ['id' => $aktifA->id]);
        $this->assertDatabaseHas('master_skp_tendiks', ['id' => $aktifB->id]);
        // Detail & SkpTendik periode B tetap utuh
        $this->assertDatabaseHas('skp_tendik_detail', ['jenis_aktivitas' => 'Aktivitas B']);
        $this->assertDatabaseMissing('skp_tendik_detail', ['jenis_aktivitas' => 'Aktivitas A']);
    }

    public function test_r2_aktivitas_milik_user_lain_ditolak_403(): void
    {
        $aktifA = $this->buatAktivitasDenganDetail($this->headerA, 'Aktivitas A');

        $orangLain = User::create([
            'name' => 'Bukan Pemilik',
            'email' => 'bukan'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($orangLain)
            ->delete(route('pegawai.skp.destroy-activity', $aktifA->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('master_skp_tendiks', ['id' => $aktifA->id]);
    }

    public function test_r2_hapus_diblokir_jika_target_sudah_disetujui(): void
    {
        $aktifA = $this->buatAktivitasDenganDetail($this->headerA, 'Aktivitas A');

        $this->headerA->update([
            'is_target_approved_penilai' => true,
            'is_target_approved_atasan' => true,
        ]);

        $response = $this->actingAs($this->pegawai)
            ->delete(route('pegawai.skp.destroy-activity', $aktifA->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('master_skp_tendiks', ['id' => $aktifA->id]);
    }

    // =========================================================================
    // R5: default periode stabil pada skpDosenIndex
    // =========================================================================

    public function test_r5_default_periode_stabil_dengan_dua_aktif_setahun_sama(): void
    {
        // Dua periode aktif tahun sama (periodeA & periodeB). mulai terbaru = periodeA (2026-08-01).
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->admin)
                ->get(route('superadmin.skp-dosen.index'));

            $response->assertOk();
            $response->assertViewHas('periodeId', $this->periodeA->id);
        }
    }

    public function test_r5_request_periode_id_mengalahkan_default(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('superadmin.skp-dosen.index', ['periode_id' => $this->periodeB->id]));

        $response->assertOk();
        $response->assertViewHas('periodeId', $this->periodeB->id);
    }
}
