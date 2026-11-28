<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\SkpTendik;
use App\Models\SkpTendikDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fix #3: SuperAdmin tidak boleh menimpa target SKP yang sudah terkunci
 * (status 'Review Akhir', 'Selesai', 'Disetujui').
 *
 * Cakupan:
 * 1. MasterSkpTendikController::update (header 'Review Akhir' → ditolak).
 * 2. SuperAdminController::skpTendikUpdate (header 'Review Akhir' → ditolak;
 *    sebelumnya hanya 'Disetujui' yang diblokir).
 * 3. SuperAdminController::skpTendikUpdate (header 'Draft' → tetap boleh).
 */
class GuardStatusHeaderTerkunciTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pegawai;
    private PeriodeSkp $periode;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('password')]);
        $this->admin->assignRole('Super Admin');

        $this->pegawai = User::create(['name' => 'Pegawai', 'email' => 'pegawai@test.local', 'password' => bcrypt('password')]);

        $this->periode = PeriodeSkp::create([
            'tahun' => (int) now()->format('Y'),
            'tipe' => 'tahunan',
            'mulai' => now()->startOfYear()->toDateString(),
            'selesai' => now()->endOfYear()->toDateString(),
            'status' => 'aktif',
        ]);
    }

    private function buatHeader(string $status): SkpHeader
    {
        return SkpHeader::create([
            'periode_skp_id' => $this->periode->id,
            'user_id' => $this->pegawai->id,
            'pejabat_penilai_id' => $this->admin->id,
            'status' => $status,
            'is_target_approved_penilai' => true,
            'is_target_approved_atasan' => true,
        ]);
    }

    private function payloadMasterSkpTendik(): array
    {
        return [
            'periode_skp_id' => $this->periode->id,
            'user_id' => $this->pegawai->id,
            'pejabat_penilai_id' => $this->admin->id,
            'penilai_akhir_id' => $this->admin->id,
            'items' => [
                [
                    'tupoksi' => 'Tupoksi Uji',
                    'activities' => [
                        [
                            'jenis_aktivitas' => 'aktivitas uji',
                            'target_kuantitas' => 1,
                            'satuan' => 'kegiatan',
                            'frekuensi' => 1,
                            'waktu' => 1,
                            'durasi' => 1,
                        ],
                    ],
                ],
            ],
            'perilaku' => [
                ['aspek' => 'Berorientasi Pelayanan', 'indicators' => ['indikator uji']],
            ],
        ];
    }

    public function test_master_skp_tendik_update_ditolak_bila_status_review_akhir(): void
    {
        $header = $this->buatHeader('Review Akhir');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.master-skp-tendik.update', $header->id), $this->payloadMasterSkpTendik());

        $response->assertRedirect();

        // Data target TIDAK boleh berubah: belum ada MasterSkpTendik terhapus/buat baru.
        $this->assertSame(0, \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->count());
        $this->assertSame('Review Akhir', $header->fresh()->status);
        // Approval flags tidak direset.
        $this->assertTrue((bool) $header->fresh()->is_target_approved_penilai);
    }

    private function payloadSkpTendik(): array
    {
        return [
            'periode_skp_id' => $this->periode->id,
            'pejabat_penilai_id' => $this->admin->id,
            'items' => [
                ['tupoksi' => 'Tupoksi Baru', 'activities' => [[
                    'jenis_aktivitas' => 'x', 'ak' => 1, 'kuantitas' => 1, 'kualitas' => 100, 'waktu' => 1, 'biaya' => 0,
                ]]],
            ],
            'perilaku' => [
                'Berorientasi Pelayanan' => ['indicators' => ['indikator uji']],
            ],
        ];
    }

    public function test_skp_tendik_update_ditolak_bila_status_review_akhir(): void
    {
        $this->buatHeader('Review Akhir');
        SkpTendik::create([
            'periode_skp_id' => $this->periode->id,
            'user_id' => $this->pegawai->id,
            'tupoksi' => 'Tupoksi Lama',
            'role_name' => 'Tendik',
        ]);

        $this->actingAs($this->admin)
            ->put(route('superadmin.skp-tendik.update', ['user_id' => $this->pegawai->id]), $this->payloadSkpTendik());

        // Tidak boleh menimpa: SkpTendik lama tetap ada, baru tidak dibuat.
        $this->assertSame(1, SkpTendik::where('user_id', $this->pegawai->id)->where('tupoksi', 'Tupoksi Lama')->count());
        $this->assertSame(0, SkpTendik::where('tupoksi', 'Tupoksi Baru')->count());
    }

    public function test_skp_tendik_update_tetap_boleh_bila_status_draft(): void
    {
        $this->buatHeader('Draft');

        $response = $this->actingAs($this->admin)
            ->put(route('superadmin.skp-tendik.update', ['user_id' => $this->pegawai->id]), $this->payloadSkpTendik());

        $response->assertSessionMissing('error');
        $this->assertSame(1, SkpTendik::where('tupoksi', 'Tupoksi Baru')->count());
    }
}
