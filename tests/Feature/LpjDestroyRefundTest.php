<?php

namespace Tests\Feature;

use App\Models\Lpj;
use App\Models\ProgramKegiatan;
use App\Models\ProgramKegiatanProdi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fix audit #1: destroy() LPJ hanya refund saldo bila status_verifikasi
 * 'diverifikasi', dalam transaksi + lockForUpdate. LPJ 'ditolak' (sudah
 * direfund saat verify()) TIDAK boleh refund lagi (double increment).
 */
class LpjDestroyRefundTest extends TestCase
{
    use RefreshDatabase;

    private User $pengaju;

    private ProgramKegiatanProdi $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Route lpj.* berada di belakang middleware 'permission:akses-manajemen-anggaran'.
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'akses-manajemen-anggaran']);
        $role = Role::firstOrCreate(['name' => 'Kaprodi Teknik Informatika']);
        $role->givePermissionTo($permission);

        $this->pengaju = User::create([
            'name'     => 'Pengaju',
            'email'    => 'pengaju' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->pengaju->assignRole('Kaprodi Teknik Informatika');
        $this->pengaju->givePermissionTo('akses-manajemen-anggaran');

        $this->pengajuan = ProgramKegiatanProdi::create([
            'program_kegiatan_id'      => ProgramKegiatan::create([
                'nama_program'   => 'Program Uji',
                'nama_kegiatan'  => 'Kegiatan Induk',
                'unit_kerja'     => 'FT',
                'created_by'     => $this->pengaju->id,
            ])->id,
            'prodi_id'                 => null,
            'user_id'                  => $this->pengaju->id,
            'nama_kegiatan_prodi'      => 'Kegiatan Uji',
            'semester'                 => 'Ganjil',
            'tahun_anggaran'           => 2026,
            'tahun_akademik'           => '2025/2026',
            'anggaran_usulan_prodi'    => 1000000,
            'anggaran_disetujui_prodi' => 1000000,
            'sisa_anggaran'            => 400000,
            'status_persetujuan'       => 'dicairkan',
        ]);
    }

    private function buatLpj(string $status): Lpj
    {
        return Lpj::create([
            'program_kegiatan_prodi_id' => $this->pengajuan->id,
            'item_pengeluaran'          => 'Nota uji',
            'nominal_penggunaan'        => 100000,
            'tanggal_kuitansi'          => '2026-01-10',
            'bukti_pengeluaran'         => null,
            'status_verifikasi'         => $status,
        ]);
    }

    public function test_destroy_lpj_diverifikasi_merefund_saldo(): void
    {
        $lpj = $this->buatLpj('diverifikasi');

        $response = $this->actingAs($this->pengaju)
            ->delete(route('lpj.destroy', $lpj));

        $response->assertRedirect();
        $this->assertDatabaseMissing('lpj', ['id' => $lpj->id]);
        // 400000 + refund 100000 = 500000
        $this->assertDatabaseHas('program_kegiatan_prodi', [
            'id'            => $this->pengajuan->id,
            'sisa_anggaran' => 500000,
        ]);
    }

    public function test_destroy_lpj_ditolak_tidak_merefund_saldo(): void
    {
        // LPJ ditolak: saldo sudah dikembalikan oleh verify() sebelumnya.
        $lpj = $this->buatLpj('ditolak');

        $response = $this->actingAs($this->pengaju)
            ->delete(route('lpj.destroy', $lpj));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        // LPJ tetap ada, saldo TIDAK bertambah (tidak ada refund kedua).
        $this->assertDatabaseHas('lpj', ['id' => $lpj->id]);
        $this->assertDatabaseHas('program_kegiatan_prodi', [
            'id'            => $this->pengajuan->id,
            'sisa_anggaran' => 400000,
        ]);
    }

    public function test_destroy_lpj_bukan_milik_user_ditolak_403(): void
    {
        $lpj = $this->buatLpj('diverifikasi');

        $userLain = User::create([
            'name'     => 'Lain',
            'email'    => 'lain' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);
        $userLain->assignRole('Kaprodi Teknik Informatika');
        $userLain->givePermissionTo('akses-manajemen-anggaran');

        $this->actingAs($userLain)
            ->delete(route('lpj.destroy', $lpj))
            ->assertForbidden();

        $this->assertDatabaseHas('lpj', ['id' => $lpj->id]);
        $this->assertDatabaseHas('program_kegiatan_prodi', [
            'id'            => $this->pengajuan->id,
            'sisa_anggaran' => 400000,
        ]);
    }
}
