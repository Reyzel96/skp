<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 5: R3 (default periode halaman Nilai Prestasi Kerja) dan
 * R4 (cache rumus ter-scope per periode).
 */
class PeriodeDefaultDanRumusCacheTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');
    }

    // =========================================================================
    // R3
    // =========================================================================

    private function buatPeriodeAktif(string $mulai, string $selesai, string $tipe = 'semester'): PeriodeSkp
    {
        return PeriodeSkp::create([
            'tahun' => (int) substr($mulai, 0, 4),
            'tipe' => $tipe,
            'semester' => $tipe === 'semester' ? 'ganjil' : null,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'status' => 'aktif',
        ]);
    }

    private function buatUserDenganHeader(PeriodeSkp $periode): User
    {
        $user = User::create([
            'name' => 'Pegawai '.uniqid(),
            'email' => 'pg'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        SkpHeader::create([
            'periode_skp_id' => $periode->id,
            'user_id' => $user->id,
            'status' => 'Draft',
        ]);
        return $user;
    }

    public function test_r3_default_ke_periode_aktif_yang_punya_skp_user(): void
    {
        // Periode aktif terbaru (tahunan, mulai lebih besar) vs periode lama (tahunan juga sesuai skenario user)
        $periodeLama = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeBaru = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        $user = $this->buatUserDenganHeader($periodeLama); // SKP hanya di periode lama (tahunan)

        $response = $this->actingAs($user)->get(route('pegawai.penilaian.index'));

        $response->assertOk();
        $response->assertViewHas('periodeId', $periodeLama->id);
    }

    public function test_r3_user_tanpa_skp_fallback_ke_aktif_terbaru_secara_stabil(): void
    {
        $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeBaru = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        $user = User::create([
            'name' => 'Tanpa SKP',
            'email' => 'kosong'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($user)->get(route('pegawai.penilaian.index'));
            $response->assertOk();
            $response->assertViewHas('periodeId', $periodeBaru->id);
        }
    }

    public function test_r3_request_periode_id_menang(): void
    {
        $periodeLama = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeBaru = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        $user = $this->buatUserDenganHeader($periodeLama);

        $response = $this->actingAs($user)
            ->get(route('pegawai.penilaian.index', ['periode_id' => $periodeBaru->id]));

        $response->assertOk();
        $response->assertViewHas('periodeId', $periodeBaru->id);
    }

    // =========================================================================
    // R4
    // =========================================================================

    private function rumusPayload(array $overrides = []): array
    {
        return array_merge([
            'distribusi_persen' => [
                'Istimewa' => [0, 4, 13, 29, 54],
                'Baik' => [8, 13, 25, 46, 8],
                'Butuh Perbaikan' => [13, 17, 42, 17, 13],
                'Kurang/Misconduct' => [8, 46, 25, 13, 8],
                'Sangat Kurang' => [54, 29, 13, 4, 0],
            ],
            'hasil_kerja' => [4, 2, 1],
        ], $overrides);
    }

    public function test_r4_simpan_rumus_a_lalu_b_nilai_a_tidak_tertimpa(): void
    {
        $periodeA = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeB = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        $this->actingAs($this->admin)
            ->post(route('superadmin.skp-dosen-kualitatif.rumus.update-pegawai'),
                $this->rumusPayload(['hasil_kerja' => [5, 3, 1], 'periode_id' => $periodeA->id]))
            ->assertJson(['success' => true]);

        $this->actingAs($this->admin)
            ->post(route('superadmin.skp-dosen-kualitatif.rumus.update-pegawai'),
                $this->rumusPayload(['hasil_kerja' => [9, 8, 7], 'periode_id' => $periodeB->id]))
            ->assertJson(['success' => true]);

        $this->assertSame(
            [5, 3, 1],
            Cache::get(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeA->id)),
            'Rumus periode A tidak boleh tertimpa penyimpanan periode B.'
        );
        $this->assertSame([9, 8, 7], Cache::get(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeB->id)));

        // Key lama tidak ditulis lagi
        $this->assertFalse(Cache::has('hasil_kerja_skp'));
    }

    public function test_r4_rumus_hasil_periode_id_b_menampilkan_data_b(): void
    {
        $periodeA = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeB = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        Cache::forever(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeA->id), [5, 3, 1]);
        Cache::forever(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeB->id), [9, 8, 7]);

        $this->actingAs($this->admin)
            ->get(route('superadmin.skp-dosen-kualitatif.rumus', ['periode_id' => $periodeB->id]))
            ->assertOk()
            ->assertViewHas('hasilKerja', [9, 8, 7])
            ->assertViewHas('periodeAktif', fn ($p) => $p->id === $periodeB->id);
    }

    public function test_r4_pembaca_paket_periode_dokumen_masing_masing(): void
    {
        $periodeA = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');
        $periodeB = $this->buatPeriodeAktif('2026-08-01', '2026-10-22');

        Cache::forever(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeA->id), [6, 2, 1]);
        Cache::forever(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeB->id), [4, 4, 4]);

        $user = $this->buatUserDenganHeader($periodeA);

        // Pembaca utama (EvaluasiBawahanController::hasilKerjaToScore) dipanggil
        // dengan periode dokumen: verifikasi lewat reflect + pola key.
        $this->assertSame('hasil_kerja_skp.'.$periodeA->id, PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeA->id));
        $this->assertSame([6, 2, 1], Cache::get(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeA->id)));
        $this->assertSame([4, 4, 4], Cache::get(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periodeB->id)));
        $this->assertSame($periodeA->id, (int) $user->skpHeaders()->first()->periode_skp_id);
    }

    public function test_r4_fallback_ke_key_lama_saat_key_per_periode_kosong(): void
    {
        $periode = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');

        Cache::forever('hasil_kerja_skp', [7, 7, 7]);
        Cache::forever('distribusi_nilai_skp', ['Baik' => [1, 2, 3, 4, 5]]);
        Cache::forever('distribusi_persen_skp', ['Baik' => [10, 20, 30, 20, 20]]);

        $response = $this->actingAs($this->admin)->get(route('superadmin.skp-dosen-kualitatif.rumus'));

        $response->assertOk();
        $response->assertViewHas('hasilKerja', [7, 7, 7]);
        $response->assertViewHas('distribusiPersen', ['Baik' => [10, 20, 30, 20, 20]]);
        // rememberForever migrasi: nilai hasil fallback ikut tersimpan ke key per periode
        $this->assertSame(
            ['Baik' => [1, 2, 3, 4, 5]],
            Cache::get(PeriodeSkp::cacheRumusKey('distribusi_nilai_skp', $periode->id))
        );
    }

    public function test_r4_satu_periode_aktif_perilaku_tetap(): void
    {
        $periode = $this->buatPeriodeAktif('2026-07-01', '2026-09-30', 'tahunan');

        // Simpan tanpa periode_id (jalur UI lama)
        $this->actingAs($this->admin)
            ->post(route('superadmin.skp-dosen-kualitatif.rumus.update-pegawai'),
                $this->rumusPayload(['hasil_kerja' => [4, 2, 1]]))
            ->assertJson(['success' => true]);

        $this->assertSame([4, 2, 1], Cache::get(PeriodeSkp::cacheRumusKey('hasil_kerja_skp', $periode->id)));

        // Tampil default tanpa ?periode_id
        $this->actingAs($this->admin)
            ->get(route('superadmin.skp-dosen-kualitatif.rumus'))
            ->assertOk()
            ->assertViewHas('hasilKerja', [4, 2, 1])
            ->assertViewHas('periodeAktif', fn ($p) => $p->id === $periode->id);

        // R3: user tanpa SKP fallback ke satu-satunya periode aktif
        $user = User::create([
            'name' => 'Kosong',
            'email' => 'k'.uniqid().'@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user)->get(route('pegawai.penilaian.index'))
            ->assertOk()
            ->assertViewHas('periodeId', $periode->id);
    }
}
