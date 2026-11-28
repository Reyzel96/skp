<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fix audit #2: tutupPeriodeLamaJikaSudahLewat() harus inklusif pada hari
 * batas (startOfDay), konsisten dengan periode:auto-close. Periode dengan
 * batas_tutup = HARI INI tidak boleh ditutup otomatis saat periode baru
 * setipe diaktifkan; batas kemarin boleh.
 */
class TutupPeriodeLamaOffByOneTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create([
            'name'     => 'Admin Uji',
            'email'    => 'admin' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');
    }

    /** Payload periode baru setipe, status aktif. */
    private function payloadTahunan(int $tahun): array
    {
        return [
            'tahun'   => $tahun,
            'tipe'    => 'tahunan',
            'mulai'   => sprintf('%d-01-01', $tahun),
            'selesai' => sprintf('%d-12-31', $tahun),
            'status'  => 'aktif',
        ];
    }

    /**
     * Periode lama aktif setipe. Rentang dihitung relatif terhadap periode baru
     * ($tahunBaru) supaya TIDAK bentrok (validasi periodeBentrok menolak overlap).
     * $batasPenilaianOffset: offset hari dari SEKARANG untuk batas_penilaian
     * (0 = hari ini, -1 = kemarin). batasTutupTerakhir() = MAX(selesai,
     * batas_pengisian, batas_penilaian) → dengan selesai tahun lama, penentu
     * efektifnya batas_penilaian.
     */
    private function buatPeriodeLama(int $tahunBaru, int $batasPenilaianOffset): PeriodeSkp
    {
        $tahunLama = $tahunBaru - 1;

        return PeriodeSkp::create([
            'tahun'           => $tahunLama,
            'tipe'            => 'tahunan',
            'mulai'           => "{$tahunLama}-01-01",
            'selesai'         => "{$tahunLama}-12-31",
            'batas_pengisian' => "{$tahunLama}-12-31",
            'batas_penilaian' => now()->addDays($batasPenilaianOffset)->toDateString(),
            'status'          => 'aktif',
        ]);
    }

    /** POST store periode baru + fail dengan exception asli bila ada. */
    private function storePeriodeBaru(int $tahun)
    {
        $response = $this->actingAs($this->admin)
            ->post(route('superadmin.periode-skp.store'), $this->payloadTahunan($tahun));

        $response->assertRedirect();
        if ($response->exception) {
            throw $response->exception;
        }
        $response->assertSessionHas('success');

        return $response;
    }

    public function test_periode_dengan_batas_hari_ini_tidak_ditutup_otomatis(): void
    {
        // Periode lama: batas tutup terakhir = HARI INI (batas_penilaian = now).
        $lama = $this->buatPeriodeLama(2026, 0);

        $this->storePeriodeBaru(2026);

        // Periode lama HARUS tetap aktif: hari batas sendiri belum lewat (inklusif).
        $this->assertDatabaseHas('periode_skp', ['id' => $lama->id, 'status' => 'aktif']);
    }

    public function test_periode_dengan_batas_kemarin_ditutup_otomatis(): void
    {
        // Periode lama: batas tutup terakhir = kemarin → sudah lewat, boleh ditutup.
        $lama = $this->buatPeriodeLama(2026, -1);

        $this->storePeriodeBaru(2026);

        $this->assertDatabaseHas('periode_skp', ['id' => $lama->id, 'status' => 'selesai']);
    }

    public function test_periode_baru_tetap_terbuat(): void
    {
        $this->buatPeriodeLama(2026, 0);

        $this->storePeriodeBaru(2026);

        $this->assertDatabaseHas('periode_skp', [
            'tahun'  => 2026,
            'tipe'   => 'tahunan',
            'status' => 'aktif',
        ]);
    }
}
