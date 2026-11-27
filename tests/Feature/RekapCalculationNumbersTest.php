<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, UserProfile, PeriodeSkp, SkpHeader, SkpDosen, SkpTendik, PenilaianSkpDosen, PenilaianSkpTendik, SkpTugasTambahan, SkpPerilakuIndicator, SkpKualitatif, SkpKualitatifPerilaku};
use Spatie\Permission\Models\Role;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Test ANGKA (bukan label) untuk calculateRekap & calculateRekapKualitatif
 * dengan dataset kecil dan ekspektasi manual. Tugas 26 langkah 7.
 *
 * CATATAN: file ini BOLEH ditambahkan ke working tree tetapi JANGAN di-commit
 * (instruksi verifikasi PR #1).
 */
class RekapCalculationNumbersTest extends TestCase
{
    use RefreshDatabase;

    private function buatUserDenganRole(string $email, string $roleName, string $kategori): User
    {
        $user = User::create(['email' => $email, 'password' => bcrypt('password')]);
        UserProfile::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Uji ' . $roleName,
            'nip' => '19' . random_int(100000000000, 999999999999),
            'pangkat_golongan' => 'III/c',
            'jabatan_pekerjaan' => 'Staf',
            'jabatan_fungsional' => null,
            'unit_kerja' => 'Informatika',
        ]);
        Role::findOrCreate($roleName, 'web');
        $role = Role::where('name', $roleName)->first();
        $role->update(['kategori' => $kategori]);
        $user->assignRole($roleName);

        return $user->fresh();
    }

    private function buatPeriode(): PeriodeSkp
    {
        return PeriodeSkp::create([
            'tahun' => 2027,
            'tipe' => 'tahunan',
            'mulai' => '2027-01-01',
            'selesai' => '2027-12-31',
            'batas_pengisian' => '2027-02-01',
            'batas_penilaian' => '2027-03-01',
            'status' => 'aktif',
        ]);
    }

    private function buatHeader(int $periodeId, int $userId, string $status = 'selesai'): SkpHeader
    {
        return SkpHeader::create([
            'periode_skp_id' => $periodeId,
            'user_id' => $userId,
            'status' => $status,
        ]);
    }

    private function invokeCalculate(array $users, int $periodeId, string $type): array
    {
        $controller = new \App\Http\Controllers\RekapSkpController();
        $m = new ReflectionMethod($controller, 'calculateRekap');
        $m->setAccessible(true);

        return $m->invoke($controller, collect($users), $periodeId, $type);
    }

    private function invokeKualitatif(array $users, int $periodeId): array
    {
        $controller = new \App\Http\Controllers\RekapSkpController();
        $m = new ReflectionMethod($controller, 'calculateRekapKualitatif');
        $m->setAccessible(true);

        return $m->invoke($controller, collect($users), $periodeId);
    }

    /** @test DOSEN: 2 kegiatan (penilaian 80 & 90) → avg = 85. Perilaku: 2 aspek (80, 90) → avg aspek = (80+90)/2 = 85. Final = 85*0.6 + 85*0.4 = 85 */
    public function test_angka_rekap_dosen_avg_penilaian_dan_bobot_60_40()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('dosen.angka@test.local', 'Dosen Informatika', 'utama');
        $header = $this->buatHeader($periode->id, $dosen->id);

        $k1 = SkpDosen::create([
            'user_id' => $dosen->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'kategori' => 'Pelaksanaan Pendidikan',
            'kegiatan' => 'Mengajar', 'kegiatan_hash' => 'h1', 'jenis' => 'utama',
            'ak' => 1, 'kuantitas_output' => 1, 'kualitas_mutu' => 1, 'waktu' => 1, 'biaya' => 0,
        ]);
        $k2 = SkpDosen::create([
            'user_id' => $dosen->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'kategori' => 'Pelaksanaan Penelitian',
            'kegiatan' => 'Publikasi', 'kegiatan_hash' => 'h2', 'jenis' => 'utama',
            'ak' => 1, 'kuantitas_output' => 1, 'kualitas_mutu' => 1, 'waktu' => 1, 'biaya' => 0,
        ]);
        PenilaianSkpDosen::create(['skp_dosen_id' => $k1->id, 'user_id' => $dosen->id, 'realisasi_ak' => 1, 'realisasi_kuantitas' => 1, 'realisasi_kualitas' => 1, 'realisasi_waktu' => 1, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 80, 'nilai_capaian' => 80]);
        PenilaianSkpDosen::create(['skp_dosen_id' => $k2->id, 'user_id' => $dosen->id, 'realisasi_ak' => 1, 'realisasi_kuantitas' => 1, 'realisasi_kualitas' => 1, 'realisasi_waktu' => 1, 'realisasi_biaya' => 0, 'nilai_penghitungan' => 90, 'nilai_capaian' => 90]);

        // Perilaku: aspek A dengan 2 indikator (80, 90) → avg aspek = 85
        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Orientasi Pelayanan', 'indikator' => 'i1', 'bobot_persen' => 50, 'nilai' => 80]);
        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Orientasi Pelayanan', 'indikator' => 'i2', 'bobot_persen' => 50, 'nilai' => 90]);

        $rekap = $this->invokeCalculate([$dosen], $periode->id, 'dosen');
        $this->assertCount(1, $rekap);
        $row = $rekap[0];

        // EKSPEKTASI MANUAL: score_skp = (80+90)/2 = 85
        $this->assertSame(85.0, $row['score_skp']);
        // score_perilaku = avg dari avg-aspek = 85
        $this->assertSame(85.0, $row['score_perilaku']);
        // final = 85*0.6 + 85*0.4 = 85.0
        $this->assertSame(85.0, $row['final_score']);
        $this->assertSame('Baik', $row['predikat']); // 85 >= 76
        $this->assertSame('selesai', $row['status']);
    }

    /** @test TENDIK: 2 SKP (nilai_capaian 60 & 90) → avg 75; tambahan penghitungan 25 → score_skp = 75+25 = 100 */
    public function test_angka_rekap_tendik_avg_plus_tambahan()
    {
        $periode = $this->buatPeriode();
        $tendik = $this->buatUserDenganRole('tendik.angka@test.local', 'IT', 'tendik');
        $header = $this->buatHeader($periode->id, $tendik->id);

        $s1 = SkpTendik::create([
            'user_id' => $tendik->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'role_name' => 'IT', 'tupoksi' => 'Tupoksi utama',
        ]);
        $s2 = SkpTendik::create([
            'user_id' => $tendik->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'role_name' => 'IT', 'tupoksi' => 'Tupoksi kedua',
        ]);
        PenilaianSkpTendik::create(['skp_tendik_id' => $s1->id, 'user_id' => $tendik->id, 'realisasi_kuantitas' => 1, 'realisasi_kualitas' => 1, 'realisasi_waktu' => 1, 'nilai_penghitungan' => 60, 'nilai_capaian' => 60]);
        PenilaianSkpTendik::create(['skp_tendik_id' => $s2->id, 'user_id' => $tendik->id, 'realisasi_kuantitas' => 1, 'realisasi_kualitas' => 1, 'realisasi_waktu' => 1, 'nilai_penghitungan' => 90, 'nilai_capaian' => 90]);

        // Tugas tambahan: penghitungan 10 + 15 = 25
        SkpTugasTambahan::create(['user_id' => $tendik->id, 'skp_header_id' => $header->id, 'periode_skp_id' => $periode->id, 'jenis' => 'tambahan', 'kegiatan' => 'Panitia', 'ak' => 0, 'ak_realisasi' => 0, 'realisasi_kegiatan' => 1, 'realisasi_bukti' => 1, 'penghitungan' => 10, 'nilai_capaian' => 10]);
        SkpTugasTambahan::create(['user_id' => $tendik->id, 'skp_header_id' => $header->id, 'periode_skp_id' => $periode->id, 'jenis' => 'tambahan', 'kegiatan' => 'Pembimbing', 'ak' => 0, 'ak_realisasi' => 0, 'realisasi_kegiatan' => 1, 'realisasi_bukti' => 1, 'penghitungan' => 15, 'nilai_capaian' => 15]);

        $rekap = $this->invokeCalculate([$tendik], $periode->id, 'tendik');
        $row = $rekap[0];

        // EKSPEKTASI MANUAL: score_skp = (60+90)/2 + (10+15) = 75 + 25 = 100
        $this->assertSame(100.0, $row['score_skp']);
        // tanpa perilaku → 0
        $this->assertSame(0.0, $row['score_perilaku']);
        // final = 100*0.6 + 0 = 60 → threshold: >90 SB, >=76 Baik, >=61 Cukup, >=51 Kurang
        $this->assertSame(60.0, $row['final_score']);
        $this->assertSame('Kurang', $row['predikat']); // 60 < 61 → Kurang (bukan Cukup)
    }

    /** @test TENDIK dengan perilaku: skp 75, perilaku aspek (90) → final = 75*0.6 + 90*0.4 = 81 */
    public function test_angka_rekap_tendik_dengan_perilaku()
    {
        $periode = $this->buatPeriode();
        $tendik = $this->buatUserDenganRole('tendik.angka2@test.local', 'Humas', 'tendik');
        $header = $this->buatHeader($periode->id, $tendik->id);

        $s1 = SkpTendik::create([
            'user_id' => $tendik->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'role_name' => 'Humas', 'tupoksi' => 'Tupoksi humas',
        ]);
        PenilaianSkpTendik::create(['skp_tendik_id' => $s1->id, 'user_id' => $tendik->id, 'realisasi_kuantitas' => 1, 'realisasi_kualitas' => 1, 'realisasi_waktu' => 1, 'nilai_penghitungan' => 75, 'nilai_capaian' => 75]);
        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Komitmen', 'indikator' => 'i1', 'bobot_persen' => 100, 'nilai' => 90]);

        $rekap = $this->invokeCalculate([$tendik], $periode->id, 'tendik');
        $row = $rekap[0];

        // EKSPEKTASI MANUAL: skp = 75, perilaku = 90 → final = 45 + 36 = 81 → 'Baik'
        $this->assertSame(75.0, $row['score_skp']);
        $this->assertSame(90.0, $row['score_perilaku']);
        $this->assertSame(81.0, $row['final_score']);
        $this->assertSame('Baik', $row['predikat']);
    }

    /** @test User TANPA header → default row: semua 0, 'Belum Mulai', predikat '-' */
    public function test_angka_user_tanpa_header_default_row()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('kosong.angka@test.local', 'Dosen Teknik Mesin', 'utama');

        $rekap = $this->invokeCalculate([$dosen], $periode->id, 'dosen');
        $row = $rekap[0];
        $this->assertSame(0, $row['score_skp']);
        $this->assertSame(0, $row['score_perilaku']);
        $this->assertSame(0, $row['final_score']);
        $this->assertSame('Belum Mulai', $row['status']);
        $this->assertSame('-', $row['predikat']);
        $this->assertNull($row['header']);
    }

    /** @test KUALITATIF: hasil kerja 3 kegiatan (Diatas, Sesuai, Diatas) → avgH = (3+2+3)/3 = 2.67 → DIATAS; perilaku (Sesuai) → SESUAI → predikat 'Baik' */
    public function test_angka_kualitatif_rating_dan_predikat_matriks()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('kual.angka@test.local', 'Dosen Teknik Elektro', 'utama');
        $header = $this->buatHeader($periode->id, $dosen->id);

        foreach (['Diatas Ekspektasi', 'Sesuai Ekspektasi', 'Diatas Ekspektasi'] as $i => $hasil) {
            SkpKualitatif::create(['skp_header_id' => $header->id, 'rencana_hasil_kerja' => 'RHK ' . $i, 'indikator_target' => 'Target ' . $i, 'hasil_kerja' => $hasil]);
        }
        SkpKualitatifPerilaku::create(['skp_header_id' => $header->id, 'aspek' => 'Orientasi', 'indikator' => 'Indikator orientasi', 'hasil_kerja' => 'Sesuai Ekspektasi']);

        $rekap = $this->invokeKualitatif([$dosen], $periode->id);
        $row = $rekap[0];

        // avgH = (3+2+3)/3 = 2.667 >= 2.5 → DIATAS EKSPEKTASI
        // avgP = 2 → SESUAI EKSPEKTASI → predikat matriks = 'BAIK' → 'Baik'
        $this->assertSame('Baik', $row['predikat']);
        $this->assertSame('selesai', $row['status']);
    }

    /** @test KUALITATIF: hasil kerja semua Diatas + perilaku Diatas → SANGAT BAIK */
    public function test_angka_kualitatif_predikat_sangat_baik()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('kual.sb@test.local', 'Dosen Teknik Sipil', 'utama');
        $header = $this->buatHeader($periode->id, $dosen->id);

        SkpKualitatif::create(['skp_header_id' => $header->id, 'rencana_hasil_kerja' => 'RHK', 'indikator_target' => 'Target', 'hasil_kerja' => 'Diatas Ekspektasi']);
        SkpKualitatifPerilaku::create(['skp_header_id' => $header->id, 'aspek' => 'Orientasi', 'indikator' => 'Indikator orientasi', 'hasil_kerja' => 'Diatas Ekspektasi']);

        $rekap = $this->invokeKualitatif([$dosen], $periode->id);
        $this->assertSame('Sangat Baik', $rekap[0]['predikat']);
    }

    /** @test KUALITATIF: H=Dibawah (1) & P=Dibawah (1) → SANGAT KURANG */
    public function test_angka_kualitatif_predikat_sangat_kurang()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('kual.sk@test.local', 'Dosen Teknik Industri', 'utama');
        $header = $this->buatHeader($periode->id, $dosen->id);

        SkpKualitatif::create(['skp_header_id' => $header->id, 'rencana_hasil_kerja' => 'RHK', 'indikator_target' => 'Target', 'hasil_kerja' => 'Dibawah Ekspektasi']);
        SkpKualitatifPerilaku::create(['skp_header_id' => $header->id, 'aspek' => 'Orientasi', 'indikator' => 'Indikator orientasi', 'hasil_kerja' => 'Dibawah Ekspektasi']);

        $rekap = $this->invokeKualitatif([$dosen], $periode->id);
        $this->assertSame('Sangat Kurang', $rekap[0]['predikat']);
    }

    /** @test Perilaku multi-aspek: aspek A (80,90)→85, aspek B (100)→100 → perilaku = (85+100)/2 = 92.5 */
    public function test_angka_perilaku_avg_antar_aspek()
    {
        $periode = $this->buatPeriode();
        $dosen = $this->buatUserDenganRole('peril.angka@test.local', 'Dosen Teknik Perkapalan', 'utama');
        $header = $this->buatHeader($periode->id, $dosen->id);

        SkpDosen::create([
            'user_id' => $dosen->id, 'skp_header_id' => $header->id,
            'periode_skp_id' => $periode->id, 'kategori' => 'Pelaksanaan Pendidikan',
            'kegiatan' => 'Mengajar', 'kegiatan_hash' => 'h1', 'jenis' => 'utama',
            'ak' => 1, 'kuantitas_output' => 1, 'kualitas_mutu' => 1, 'waktu' => 1, 'biaya' => 0,
        ]);

        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Aspek A', 'indikator' => 'a1', 'bobot_persen' => 50, 'nilai' => 80]);
        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Aspek A', 'indikator' => 'a2', 'bobot_persen' => 50, 'nilai' => 90]);
        SkpPerilakuIndicator::create(['skp_header_id' => $header->id, 'aspek' => 'Aspek B', 'indikator' => 'b1', 'bobot_persen' => 100, 'nilai' => 100]);

        $rekap = $this->invokeCalculate([$dosen], $periode->id, 'dosen');
        $row = $rekap[0];

        // EKSPEKTASI MANUAL: avg A = 85, avg B = 100 → perilaku = 92.5
        $this->assertSame(92.5, $row['score_perilaku']);
        // skp tanpa penilaian = 0 → final = 92.5 * 0.4 = 37 → 'Sangat Kurang'
        $this->assertSame(37.0, $row['final_score']);
        $this->assertSame('Sangat Kurang', $row['predikat']);
    }
}
