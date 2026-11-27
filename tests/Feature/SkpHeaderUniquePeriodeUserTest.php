<?php

namespace Tests\Feature;

use App\Models\MasterSkpTendik;
use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 7: unique constraint (periode_skp_id, user_id) di skp_headers +
 * guard duplikat di skpDosenStore & MasterSkpTendikController@store.
 */
class SkpHeaderUniquePeriodeUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pegawai;

    private PeriodeSkp $periode;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Dekan']);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');

        $this->pegawai = User::create([
            'name' => 'Pegawai',
            'email' => 'pegawai'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);

        $this->periode = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2026-08-01', 'selesai' => '2026-10-22', 'status' => 'aktif',
        ]);
    }

    // =========================================================================
    // Constraint DB level
    // =========================================================================

    public function test_insert_duplikat_periode_user_ditolak_db(): void
    {
        SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);
    }

    public function test_user_sama_periode_beda_diperbolehkan(): void
    {
        $periodeLain = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'mulai' => '2026-01-01', 'selesai' => '2026-03-31', 'status' => 'aktif',
        ]);

        SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);
        SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $periodeLain->id, 'status' => 'Draft']);

        $this->assertSame(2, SkpHeader::where('user_id', $this->pegawai->id)->count());
    }

    public function test_periode_sama_user_beda_diperbolehkan(): void
    {
        $pegawaiLain = User::create([
            'name' => 'Pegawai Lain',
            'email' => 'lain'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);

        SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);
        SkpHeader::create(['user_id' => $pegawaiLain->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);

        $this->assertSame(2, SkpHeader::where('periode_skp_id', $this->periode->id)->count());
    }

    // =========================================================================
    // Guard aplikasi: MasterSkpTendikController@store
    // =========================================================================

    private function payloadMasterTendik(): array
    {
        $penilai = User::create([
            'name' => 'Dekan Penilai',
            'email' => 'dekan'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $penilai->assignRole('Dekan');

        return [
            'user_id' => $this->pegawai->id,
            'periode_skp_id' => $this->periode->id,
            'pejabat_penilai_id' => $penilai->id,
            'atasan_pejabat_penilai_id' => $penilai->id,
            'items' => [[
                'role_name' => 'Staf',
                'tupoksi' => 'Tupoksi Uji',
                'jenis_aktivitas' => 'Kegiatan Uji',
                'penilai_role' => 'Dekan',
                'periode' => 'bulanan',
                'frekuensi_per_tahun' => 1,
                'durasi_menit' => 60,
            ]],
        ];
    }

    public function test_master_tendik_store_header_sudah_ada_ditolak_dengan_pesan(): void
    {
        $headerLama = SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Pending']);
        MasterSkpTendik::create([
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $headerLama->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi Lama',
            'jenis_aktivitas' => 'Kegiatan Lama',
            'periode' => 'bulanan',
            'frekuensi_per_tahun' => 1,
            'durasi_menit' => 60,
            'status_approval' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('superadmin.master-skp-tendik.create'))
            ->post(route('superadmin.master-skp-tendik.store'), $this->payloadMasterTendik());

        $response->assertRedirect();
        $response->assertSessionHas('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
        $this->assertSame(1, SkpHeader::count(), 'Header tidak boleh bertambah.');
        // Aktivitas header lama tidak berubah
        $this->assertSame('Kegiatan Lama', MasterSkpTendik::where('skp_header_id', $headerLama->id)->value('jenis_aktivitas'));
        $this->assertSame(1, MasterSkpTendik::count());
    }

    public function test_master_tendik_store_header_belum_ada_berhasil(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.master-skp-tendik.store'), $this->payloadMasterTendik());

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(1, SkpHeader::count());
        $this->assertSame(1, MasterSkpTendik::count());
    }

    // =========================================================================
    // Guard aplikasi: SuperAdminController@skpDosenStore
    // =========================================================================

    private function payloadSkpDosen(): array
    {
        $penilai = User::create([
            'name' => 'Dekan Dosen',
            'email' => 'dekandosen'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $penilai->assignRole('Dekan');

        return [
            'user_id' => $this->pegawai->id,
            'periode_skp_id' => $this->periode->id,
            'pejabat_penilai_id' => $penilai->id,
            'penilai_akhir_id' => $penilai->id,
            'kegiatan' => [[
                'kategori' => 'Pelaksanaan Pendidikan',
                'kegiatan' => 'Kegiatan Dosen Uji',
                'jenis' => 'utama',
            ]],
            'perilaku' => [
                'Integritas' => [
                    'indicators' => [['text' => 'Jujur', 'bobot_persen' => 100]],
                ],
            ],
        ];
    }

    public function test_skp_dosen_store_header_sudah_ada_ditolak_dengan_pesan(): void
    {
        $headerLama = SkpHeader::create(['user_id' => $this->pegawai->id, 'periode_skp_id' => $this->periode->id, 'status' => 'Draft']);
        MasterSkpTendik::create([
            'user_id' => $this->pegawai->id,
            'skp_header_id' => $headerLama->id,
            'role_name' => 'Staf',
            'tupoksi' => 'Tupoksi Lama',
            'jenis_aktivitas' => 'Kegiatan Lama',
            'periode' => 'bulanan',
            'frekuensi_per_tahun' => 1,
            'durasi_menit' => 60,
            'status_approval' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('superadmin.skp-dosen.create'))
            ->post(route('superadmin.skp-dosen.store'), $this->payloadSkpDosen());

        $response->assertRedirect();
        $response->assertSessionHas('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
        $this->assertSame(1, SkpHeader::count(), 'Header tidak boleh bertambah.');
        $this->assertSame('Kegiatan Lama', MasterSkpTendik::where('skp_header_id', $headerLama->id)->value('jenis_aktivitas'));
    }

    public function test_skp_dosen_store_header_belum_ada_berhasil(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.skp-dosen.store'), $this->payloadSkpDosen());

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(1, SkpHeader::count());
        $this->assertSame(1, \App\Models\SkpDosen::count());
    }

    // =========================================================================
    // Fallback race-condition: lewati guard aplikasi, constraint DB menolak
    // =========================================================================

    public function test_race_condition_query_exception_bukan_seluruh_queryexception_ditelan(): void
    {
        // QueryException yang BUKAN pelanggaran unik tetap dilempar (bukan pesan duplikat).
        // Simulasi: exception dengan pesan acak.
        $e = new \Illuminate\Database\QueryException(
            'sqlite', 'select 1', ['x' => 1], new \Exception('SQLSTATE[42S02]: Base table or view not found')
        );

        $reflection = new \ReflectionMethod(
            \App\Http\Controllers\SuperAdmin\MasterSkpTendikController::class,
            'adalahPelanggaranUnikHeader'
        );
        $reflection->setAccessible(true);

        $this->assertFalse($reflection->invoke(new \App\Http\Controllers\SuperAdmin\MasterSkpTendikController(), $e));

        // Pesan berisi nama constraint → terdeteksi
        $e2 = new \Illuminate\Database\QueryException(
            'sqlite', 'insert into skp_headers', ['x' => 1],
            new \Exception('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'2-7\' for key \'skp_headers_periode_user_unique\'')
        );
        $this->assertTrue($reflection->invoke(new \App\Http\Controllers\SuperAdmin\MasterSkpTendikController(), $e2));
    }
}
