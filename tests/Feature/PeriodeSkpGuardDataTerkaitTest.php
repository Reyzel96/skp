<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\SkpKualitatif;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodeSkpGuardDataTerkaitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Super Admin');
    }

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

    private function buatPeriode(array $overrides = []): PeriodeSkp
    {
        return PeriodeSkp::create($this->payload($overrides));
    }

    private function buatHeader(PeriodeSkp $periode): SkpHeader
    {
        $user = User::create([
            'name' => 'Pegawai',
            'email' => 'pegawai-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);

        return SkpHeader::create([
            'user_id' => $user->id,
            'periode_skp_id' => $periode->id,
            'status' => 'Draft',
        ]);
    }

    // ================= HAPUS =================

    public function test_hapus_periode_dengan_skp_headers_ditolak_dan_data_tetap_ada(): void
    {
        $periode = $this->buatPeriode();
        $header = $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->delete(route('superadmin.periode-skp.destroy', $periode->id));

        $res->assertRedirect()
            ->assertSessionHas('error', function ($msg) {
                return str_contains($msg, 'Periode tidak bisa dihapus')
                    && str_contains($msg, '1 SKP');
            });

        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id]);
        $this->assertDatabaseHas('skp_headers', ['id' => $header->id]);
    }

    public function test_hapus_periode_dengan_kualitatif_saja_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $header = $this->buatHeader($periode);
        SkpKualitatif::create([
            'skp_header_id' => $header->id,
            'rencana_hasil_kerja' => 'Rencana 1',
            'indikator_target' => 'Indikator 1',
        ]);

        // header dihapus agar yang tersisa hanya kualitatif? Tidak — kualitatif butuh header.
        // Cukup: punya header + kualitatif → pesan menyebut keduanya.
        $res = $this->actingAs($this->admin)
            ->delete(route('superadmin.periode-skp.destroy', $periode->id));

        $res->assertRedirect()
            ->assertSessionHas('error', function ($msg) {
                return str_contains($msg, '1 SKP');
            });

        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id]);
    }

    public function test_hapus_periode_tanpa_data_berhasil(): void
    {
        $periode = $this->buatPeriode();

        $res = $this->actingAs($this->admin)
            ->delete(route('superadmin.periode-skp.destroy', $periode->id));

        $res->assertRedirect(route('superadmin.periode-skp.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('periode_skp', ['id' => $periode->id]);
    }

    public function test_hapus_dengan_2_skp_pesan_menyebut_jumlah(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->delete(route('superadmin.periode-skp.destroy', $periode->id));

        $res->assertRedirect()
            ->assertSessionHas('error', fn ($m) => str_contains($m, '2 SKP'));
    }

    // ================= EDIT TERKUNCI =================

    public function test_edit_periode_terpakai_ubah_tipe_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['tipe' => 'tahunan', 'semester_genap' => 'Genap', 'semester_gasal' => 'Gasal']));

        $res->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'tipe'));
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'tipe' => 'semester']);
    }

    public function test_edit_periode_terpakai_ubah_tahun_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['tahun' => 2027]));

        $res->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'tahun'));
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'tahun' => 2026]);
    }

    public function test_edit_periode_terpakai_ubah_semester_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['semester' => 'genap']));

        $res->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'semester' => 'ganjil']);
    }

    public function test_edit_periode_terpakai_ubah_bulan_ditolak(): void
    {
        $periode = $this->buatPeriode(['tipe' => 'bulanan', 'semester' => null, 'bulan' => 1, 'mulai' => '2026-01-01', 'selesai' => '2026-01-31']);
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['tipe' => 'bulanan', 'semester' => null, 'bulan' => 2, 'mulai' => '2026-02-01', 'selesai' => '2026-02-28']));

        $res->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'bulan' => 1]);
    }

    public function test_edit_periode_terpakai_ubah_tanggal_mulai_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['mulai' => '2026-01-15']));

        $res->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id]);
        $this->assertSame('2026-01-01', PeriodeSkp::find($periode->id)->mulai->toDateString());
    }

    public function test_edit_periode_terpakai_ubah_tanggal_selesai_ditolak(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload(['selesai' => '2026-04-30']));

        $res->assertRedirect()->assertSessionHas('error');
        $this->assertSame('2026-03-31', PeriodeSkp::find($periode->id)->selesai->toDateString());
    }

    // ================= EDIT BEBAS (terpakai, non-identitas) =================

    public function test_edit_periode_terpakai_ubah_batas_status_label_berhasil(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload([
                'batas_pengisian' => '2026-04-01',
                'batas_penilaian' => '2026-04-05',
                'status' => 'aktif',
                'label_triwulan' => 'Triwulan Khusus',
                'label_periode_penilaian' => 'Label Penilaian',
            ]));

        $res->assertRedirect()->assertSessionHas('success');

        $fresh = PeriodeSkp::find($periode->id);
        $this->assertSame('2026-04-01', $fresh->batas_pengisian->toDateString());
        $this->assertSame('2026-04-05', $fresh->batas_penilaian->toDateString());
        $this->assertSame('aktif', $fresh->status);
        $this->assertSame('Triwulan Khusus', $fresh->label_triwulan);
        $this->assertSame('Label Penilaian', $fresh->label_periode_penilaian);
    }

    public function test_edit_periode_terpakai_validasi_7_hari_tetap_berlaku(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        // 03-31 + 7 hari = 04-07 → 04-08 harus ditolak
        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload([
                'batas_pengisian' => '2026-04-08',
                'batas_penilaian' => '2026-04-08',
            ]));

        $res->assertRedirect()->assertSessionHasErrors('batas_pengisian');
    }

    public function test_edit_periode_terpakai_kirim_semua_nilai_sama_berhasil(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $this->payload());

        $res->assertRedirect()->assertSessionHas('success');
    }

    // ================= GUARD BULAN (normalisasi nilai) =================

    // Payload persis seperti browser pada periode semester terpakai:
    // hanya hidden inputs + field bebas; TANPA bulan/bulan_akhir.
    // $idx memvariasikan rentang tanggal agar tidak kena constraint unik saat loop.
    private function periodeDanPayloadBrowser(int $idx = 0): array
    {
        $mulai = ['2026-08-01', '2026-02-01', '2026-05-01'][$idx % 3];
        $selesai = ['2026-10-22', '2026-04-30', '2026-07-31'][$idx % 3];
        $pengisian = ['2026-10-20', '2026-04-28', '2026-07-29'][$idx % 3];
        $penilaian = ['2026-10-21', '2026-04-29', '2026-07-30'][$idx % 3];

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'bulan' => 1, // nilai legacy di DB nyata (ID 2)
            'mulai' => $mulai,
            'selesai' => $selesai,
            'batas_pengisian' => $pengisian,
            'batas_penilaian' => $penilaian,
            'status' => 'aktif',
        ]);

        foreach ([1, 2] as $i) {
            $user = User::create([
                'name' => "Pegawai {$i}",
                'email' => "pegawai-{$idx}-{$i}-" . uniqid() . '@test.local',
                'password' => bcrypt('password'),
            ]);
            SkpHeader::create([
                'user_id' => $user->id,
                'periode_skp_id' => $periode->id,
                'status' => 'Disetujui',
            ]);
        }

        // Persis browser: hanya hidden inputs + field bebas; TANPA bulan/bulan_akhir.
        // Batas disesuaikan agar tetap <= selesai + 7 hari untuk tiap variasi rentang.
        $batasPengisian = ['2026-10-25', '2026-05-05', '2026-08-05'][$idx % 3];
        $batasPenilaian = ['2026-10-27', '2026-05-07', '2026-08-07'][$idx % 3];
        $payload = [
            'tipe' => 'semester',
            'tahun' => 2026,
            'semester' => 'ganjil',
            'mulai' => $mulai,
            'selesai' => $selesai,
            'batas_pengisian' => $batasPengisian,
            'batas_penilaian' => $batasPenilaian,
            'status' => 'aktif',
        ];

        return [$periode, $payload];
    }

    public function test_edit_semester_terpakai_dengan_bulan_legacy_tanpa_kirim_bulan_berhasil(): void
    {
        [$periode, $payload] = $this->periodeDanPayloadBrowser();

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), $payload);

        $res->assertRedirect()->assertSessionHas('success');
        $fresh = $periode->fresh();
        $this->assertSame('2026-10-25', $fresh->batas_pengisian->toDateString());
        $this->assertSame('2026-10-27', $fresh->batas_penilaian->toDateString());
    }

    public function test_edit_semester_terpakai_bulan_dikirim_kosong_atau_angka_tetap_berhasil(): void
    {
        // Tugas 8: bulan kini wajib 1-4 (triwulan). Nilai legacy 8 tidak lagi
        // lolos validasi — gunakan nilai triwulan yang sah untuk kasus "angka".
        foreach ([null, '', 2] as $i => $nilaiBulan) {
            [$periode, $payload] = $this->periodeDanPayloadBrowser($i);
            if ($nilaiBulan === null) {
                unset($payload['bulan']);
            } else {
                $payload['bulan'] = $nilaiBulan;
            }

            $res = $this->actingAs($this->admin)
                ->put(route('superadmin.periode-skp.update', $periode->id), $payload);

            $res->assertRedirect()->assertSessionHas('success');
        }
    }

    public function test_edit_bulanan_terpakai_bulan_diganti_ditolak(): void
    {
        $periode = $this->buatPeriode([
            'tipe' => 'bulanan',
            'semester' => null, // periode bulanan tidak punya semester
            'bulan' => 1,
            'bulan_akhir' => 3,
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
        ]);
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), [
                'tipe' => 'bulanan',
                'tahun' => 2026,
                'bulan' => 4, // diganti
                'bulan_akhir' => 3,
                'mulai' => '2026-01-01',
                'selesai' => '2026-03-31',
                'status' => 'aktif',
            ]);

        $res->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'bulan'));
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'bulan' => 1]);
    }

    public function test_edit_bulanan_terpakai_bulan_sama_berhasil(): void
    {
        $periode = $this->buatPeriode([
            'tipe' => 'bulanan',
            'semester' => null, // periode bulanan tidak punya semester
            'bulan' => 1,
            'bulan_akhir' => 3,
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
        ]);
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), [
                'tipe' => 'bulanan',
                'tahun' => 2026,
                'bulan' => '1',      // string dari select, dibanding int di DB
                'bulan_akhir' => '3',
                'mulai' => '2026-01-01',
                'selesai' => '2026-03-31',
                'status' => 'aktif',
            ]);

        $res->assertRedirect()->assertSessionHas('success');
    }

    // ================= EDIT TANPA DATA: bebas =================

    public function test_edit_periode_tanpa_data_semua_bebas(): void
    {
        $periode = $this->buatPeriode();

        // Tugas 8: bulan wajib 1-4 (triwulan) — 5 diganti 2.
        $res = $this->actingAs($this->admin)
            ->put(route('superadmin.periode-skp.update', $periode->id), [
                'tahun' => 2027,
                'tipe' => 'bulanan',
                'semester' => null,
                'bulan' => 2,
                'mulai' => '2027-05-01',
                'selesai' => '2027-05-31',
                'status' => 'aktif',
            ]);

        $res->assertRedirect()->assertSessionHas('success');

        $fresh = PeriodeSkp::find($periode->id);
        $this->assertSame(2027, $fresh->tahun);
        $this->assertSame('bulanan', $fresh->tipe);
        $this->assertSame(2, $fresh->bulan);
        $this->assertSame('aktif', $fresh->status);
    }

    // ================= PATCH STATUS TIDAK TERKENA GUARD =================

    public function test_patch_update_status_pada_periode_terpakai_berfungsi(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)
            ->patch(route('superadmin.periode-skp.update-status', $periode->id), ['status' => 'selesai']);

        $res->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('periode_skp', ['id' => $periode->id, 'status' => 'selesai']);
    }

    // ================= VIEW RENDER =================

    public function test_index_menampilkan_tombol_hapus_disabled_untuk_periode_terpakai(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.index'));

        $res->assertOk()
            ->assertSee('disabled', false)
            ->assertSee('Tidak bisa dihapus: dipakai 1 SKP', false);
    }

    public function test_index_tombol_hapus_aktif_untuk_periode_kosong(): void
    {
        $periode = $this->buatPeriode();

        $res = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.index'));

        $res->assertOk()
            ->assertSee('Hapus Periode', false)
            ->assertDontSee('Tidak bisa dihapus: dipakai', false);
    }

    public function test_edit_view_menampilkan_catatan_terkunci_untuk_periode_terpakai(): void
    {
        $periode = $this->buatPeriode();
        $this->buatHeader($periode);

        $res = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.edit', $periode->id));

        $res->assertOk()
            ->assertSee('Terkunci karena periode sudah dipakai 1 SKP', false)
            ->assertSee('input type="hidden" name="tipe"', false)
            ->assertSee('input type="hidden" name="mulai"', false);
    }

    public function test_edit_view_tanpa_kunci_untuk_periode_kosong(): void
    {
        $periode = $this->buatPeriode();

        $res = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.edit', $periode->id));

        $res->assertOk()
            ->assertDontSee('Terkunci karena periode sudah dipakai', false)
            ->assertDontSee('input type="hidden" name="tipe"', false);
    }
}
