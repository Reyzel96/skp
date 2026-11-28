<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpDosen;
use App\Models\SkpHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuardFinalisasiTargetTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;
    private User $admin;
    private PeriodeSkp $periode;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Dosen', 'guard_name' => 'web']);

        $this->pegawai = User::create([
            'name' => 'Pegawai Satu',
            'email' => 'pegawai@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->pegawai->assignRole('Dosen');

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');

        $this->periode = PeriodeSkp::create([
            'tahun' => now()->year,
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
        ]);
    }

    private function buatSkpDosen(): SkpDosen
    {
        return SkpDosen::create([
            'periode_skp_id' => $this->periode->id,
            'user_id' => $this->pegawai->id,
            'kategori' => 'Pelaksanaan Pendidikan',
            'kegiatan' => 'Kegiatan Uji',
            'kegiatan_hash' => md5($this->pegawai->id . 'Kegiatan Uji'),
            'detail_kegiatan' => null,
        ]);
    }

    public function test_finalisasi_target_ditolak_bila_status_review_akhir(): void
    {
        $header = $this->buatHeader('Review Akhir');

        $response = $this->actingAs($this->pegawai)
            ->patch(route('pegawai.skp.finalisasi-target', $header->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('Review Akhir', $header->fresh()->status);
    }

    public function test_finalisasi_target_ditolak_bila_status_selesai(): void
    {
        $header = $this->buatHeader('Selesai');

        $response = $this->actingAs($this->pegawai)
            ->patch(route('pegawai.skp.finalisasi-target', $header->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('Selesai', $header->fresh()->status);
    }

    public function test_finalisasi_target_ditolak_bila_status_disetujui(): void
    {
        $header = $this->buatHeader('Disetujui');

        $response = $this->actingAs($this->pegawai)
            ->patch(route('pegawai.skp.finalisasi-target', $header->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('Disetujui', $header->fresh()->status);
    }

    public function test_finalisasi_target_boleh_dari_status_draft(): void
    {
        $header = $this->buatHeader('Draft');

        $response = $this->actingAs($this->pegawai)
            ->patch(route('pegawai.skp.finalisasi-target', $header->id));

        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $this->assertSame('Target Diajukan', $header->fresh()->status);
    }

    public function test_update_target_detail_tetap_boleh_bila_draft(): void
    {
        $header = $this->buatHeader('Draft');
        $skp = $this->buatSkpDosen();

        $response = $this->actingAs($this->pegawai)
            ->post(route('pegawai.skp-dosen.update-target-detail'), [
                'skp_id' => $skp->id,
                'detail' => ['Kegiatan uji'],
                'ak' => 1.5,
                'kuantitas' => 10,
                'kualitas' => 100,
                'waktu' => 12,
                'biaya' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $skp->refresh();
        $this->assertSame(['Kegiatan uji'], (array) $skp->detail_kegiatan);
    }

    public function test_update_target_detail_ditolak_bila_review_akhir(): void
    {
        $header = $this->buatHeader('Review Akhir');
        $skp = $this->buatSkpDosen();
        $skp->update(['detail_kegiatan' => 'Lama']);

        $response = $this->actingAs($this->pegawai)
            ->post(route('pegawai.skp-dosen.update-target-detail'), [
                'skp_id' => $skp->id,
                'detail' => ['Baru dilarang'],
                'ak' => 1.5,
                'kuantitas' => 10,
                'kualitas' => 100,
                'waktu' => 12,
                'biaya' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('Lama', $skp->fresh()->detail_kegiatan);
    }

    public function test_update_target_detail_tidak_fatal_bila_header_tidak_ada(): void
    {
        // SkpDosen tanpa SkpHeader (dulu: fatal 'property on null').
        $skp = SkpDosen::create([
            'periode_skp_id' => $this->periode->id,
            'user_id' => $this->pegawai->id,
            'kategori' => 'Pelaksanaan Pendidikan',
            'kegiatan' => 'Tanpa Header',
            'kegiatan_hash' => md5($this->pegawai->id . 'Tanpa Header'),
            'detail_kegiatan' => 'Lama',
        ]);

        $response = $this->actingAs($this->pegawai)
            ->post(route('pegawai.skp-dosen.update-target-detail'), [
                'skp_id' => $skp->id,
                'detail' => ['Baru'],
                'ak' => 1.5,
                'kuantitas' => 10,
                'kualitas' => 100,
                'waktu' => 12,
                'biaya' => 0,
            ]);

        // Bukan 500: redirect dengan pesan error.
        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertSame('Lama', $skp->fresh()->detail_kegiatan);
    }
}

