<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 8: form Periode SKP triwulan/semester/tahunan.
 * - Bulanan = triwulan (bulan 1-4): Triwulan I 01-01→03-31, II 04-01→06-30,
 *   III 07-01→09-30, IV 10-01→12-31.
 * - Semester = kalender akademik (T17): Ganjil (1 Sep s.d. akhir Feb tahun berikutnya,
 *   dinamai tahun mulai), Genap (1 Mar s.d. 31 Agu).
 * - Tahunan 01-01→12-31.
 * - Tanggal auto terisi tapi TETAP bisa diubah bebas (non-default diterima).
 * - nama_periode: "Triwulan {I-IV} {tahun}", "Ganjil|Genap {tahun}", "Tahunan {tahun}".
 */
class PeriodeSkpTriwulanSemesterTest extends TestCase
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
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tahun' => 2026,
            'tipe' => 'bulanan',
            'bulan' => 1,
            'mulai' => '2026-01-01',
            'selesai' => '2026-03-31',
            'status' => 'draft',
        ], $overrides);
    }

    // =========================================================================
    // Accessor nama_periode
    // =========================================================================

    public function test_nama_periode_triwulan_satu_sampai_empat(): void
    {
        foreach ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $bulan => $roman) {
            $p = PeriodeSkp::create([
                'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => $bulan,
                'mulai' => '2026-01-01', 'selesai' => '2026-03-31', 'status' => 'draft',
            ]);
            $this->assertSame("Triwulan {$roman} 2026", $p->nama_periode, "bulan={$bulan}");
            $p->delete();
        }
    }

    public function test_nama_periode_semester_dan_tahunan(): void
    {
        $sem1 = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2026-01-01', 'selesai' => '2026-06-30', 'status' => 'draft',
        ]);
        $this->assertSame('Ganjil 2026', $sem1->nama_periode);

        $sem2 = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'genap',
            'mulai' => '2026-07-01', 'selesai' => '2026-12-31', 'status' => 'draft',
        ]);
        $this->assertSame('Genap 2026', $sem2->nama_periode);

        // Tahunan TANPA semester_genap/gasal (nullable) → "Tahunan 2026".
        $tahunan = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'mulai' => '2026-01-01', 'selesai' => '2026-12-31', 'status' => 'draft',
        ]);
        $this->assertSame('Tahunan 2026', $tahunan->nama_periode);
    }

    public function test_triwulan_label_dari_kolom_bulan_bukan_bulan_mulai(): void
    {
        // Triwulan IV tapi tanggal mulai di Januari (custom) → label tetap Triwulan IV.
        $p = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'bulanan', 'bulan' => 4,
            'mulai' => '2026-01-15', 'selesai' => '2026-03-20', 'status' => 'draft',
        ]);
        $this->assertSame('Triwulan IV', $p->triwulan_label);
        $this->assertSame('Triwulan IV 2026', $p->nama_periode);

        // Override manual tetap menang.
        $p->update(['label_triwulan' => 'Triwulan Khusus']);
        $this->assertSame('Triwulan Khusus', $p->fresh()->triwulan_label);
    }

    public function test_rentang_triwulan_helper(): void
    {
        $this->assertSame(['mulai' => '2026-01-01', 'selesai' => '2026-03-31'], PeriodeSkp::rentangTriwulan(1, 2026));
        $this->assertSame(['mulai' => '2026-04-01', 'selesai' => '2026-06-30'], PeriodeSkp::rentangTriwulan(2, 2026));
        $this->assertSame(['mulai' => '2026-07-01', 'selesai' => '2026-09-30'], PeriodeSkp::rentangTriwulan(3, 2026));
        $this->assertSame(['mulai' => '2026-10-01', 'selesai' => '2026-12-31'], PeriodeSkp::rentangTriwulan(4, 2026));
    }

    // =========================================================================
    // Store: default tanggal diisi otomatis bila kosong (semua tipe)
    // =========================================================================

    public function test_store_bulanan_tanpa_tanggal_terisi_default_triwulan(): void
    {
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'bulan' => 2,
            'mulai' => '',
            'selesai' => '',
        ]));
        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'bulanan')->where('bulan', 2)->first();
        $this->assertNotNull($p);
        $this->assertSame('2026-04-01', $p->mulai->toDateString());
        $this->assertSame('2026-06-30', $p->selesai->toDateString());
        $this->assertSame('Triwulan II 2026', $p->nama_periode);
    }

    public function test_store_semester_tanpa_tanggal_terisi_default_kalender(): void
    {
        // T17: kalender akademik — Genap = 01-03 s.d. 31-08 (tahun sama),
        // Ganjil = 01-09 s.d. akhir Februari TAHUN BERIKUTNYA.
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'tipe' => 'semester', 'semester' => 'genap',
            'mulai' => '', 'selesai' => '',
        ]));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'semester')->where('semester', 'genap')->first();
        $this->assertSame('2026-03-01', $p->mulai->toDateString());
        $this->assertSame('2026-08-31', $p->selesai->toDateString());
        $this->assertSame('Genap 2026', $p->nama_periode);

        // Ganjil tanpa tanggal → lintas tahun (Sep 2026 s.d. Feb 2027).
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '', 'selesai' => '',
        ]));
        $r->assertSessionHas('success');
        $p = PeriodeSkp::where('tipe', 'semester')->where('semester', 'ganjil')->first();
        $this->assertSame('2026-09-01', $p->mulai->toDateString());
        $this->assertSame('2027-02-28', $p->selesai->toDateString());
        $this->assertSame('Ganjil 2026', $p->nama_periode);
    }

    public function test_store_tahunan_tanpa_tanggal_dan_tanpa_label_semester(): void
    {
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'tipe' => 'tahunan',
            'mulai' => '', 'selesai' => '',
        ]));
        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'tahunan')->first();
        $this->assertNotNull($p);
        $this->assertSame('2026-01-01', $p->mulai->toDateString());
        $this->assertSame('2026-12-31', $p->selesai->toDateString());
        $this->assertSame('Tahunan 2026', $p->nama_periode);
    }

    // =========================================================================
    // Tanggal custom (non-default) TETAP diterima — tidak pernah ditolak
    // =========================================================================

    public function test_bulanan_tanggal_custom_diterima(): void
    {
        // Triwulan I tapi rentang custom 15 Jan s.d. 20 Apr.
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'bulan' => 1,
            'mulai' => '2026-01-15',
            'selesai' => '2026-04-20',
        ]));
        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'bulanan')->first();
        $this->assertSame('2026-01-15', $p->mulai->toDateString());
        $this->assertSame('2026-04-20', $p->selesai->toDateString());
    }

    public function test_bulanan_rentang_satu_bulan_diterima(): void
    {
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'bulan' => 3,
            'mulai' => '2026-07-01',
            'selesai' => '2026-07-31',
        ]));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'bulanan')->first();
        $this->assertSame('2026-07-01', $p->mulai->toDateString());
        $this->assertSame('2026-07-31', $p->selesai->toDateString());
        $this->assertSame('Triwulan III 2026', $p->nama_periode);
    }

    // =========================================================================
    // Validasi yang tetap berlaku
    // =========================================================================

    public function test_bulanan_bulan_7_ditolak_pesan_jelas(): void
    {
        $r = $this->actingAs($this->admin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'bulan' => 7,
                'mulai' => '2026-07-01',
                'selesai' => '2026-07-31',
            ]));
        $r->assertSessionHasErrors(['bulan']);
        $this->assertNull(PeriodeSkp::where('tipe', 'bulanan')->first());
    }

    public function test_selesai_sebelum_mulai_ditolak(): void
    {
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'mulai' => '2026-04-30',
            'selesai' => '2026-04-01',
        ]));
        $r->assertSessionHasErrors(['selesai']);
    }

    public function test_overlap_setipe_ditolak(): void
    {
        PeriodeSkp::create($this->payload(['bulan' => 1])); // TW I 01-01→03-31

        $r = $this->actingAs($this->admin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'bulan' => 2,
                'mulai' => '2026-03-15', // overlap dgn TW I
                'selesai' => '2026-06-30',
            ]));
        $r->assertSessionHas('error');
    }

    public function test_tahunan_semester_dan_triwulan_aktif_bersamaan(): void
    {
        $tahunan = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'mulai' => '2026-01-01', 'selesai' => '2026-12-31', 'status' => 'aktif',
        ]);
        $sem1 = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2026-01-01', 'selesai' => '2026-06-30', 'status' => 'aktif',
        ]);
        $tw1 = PeriodeSkp::create($this->payload(['status' => 'aktif']));

        foreach ([$tahunan, $sem1, $tw1] as $p) {
            $this->assertSame('aktif', $p->fresh()->status, "tipe {$p->tipe} tidak boleh ditutup tipe lain");
        }
    }

    public function test_tugas_6_perilaku_penutupan_setipe_tetap(): void
    {
        // TW I aktif lalu TW II diaktifkan → TW I tetap aktif (jendela masih terbuka).
        \Illuminate\Support\Carbon::setTestNow('2026-04-02 08:00:00');
        $tw1 = PeriodeSkp::create($this->payload([
            'status' => 'aktif',
            'batas_penilaian' => '2026-04-07',
        ]));
        $tw2 = PeriodeSkp::create($this->payload([
            'bulan' => 2,
            'mulai' => '2026-04-01',
            'selesai' => '2026-06-30',
            'batas_penilaian' => '2026-07-07',
            'status' => 'draft',
        ]));

        $r = $this->actingAs($this->admin)
            ->from(route('superadmin.periode-skp.index'))
            ->patch(route('superadmin.periode-skp.update-status', $tw2), ['status' => 'aktif']);
        $r->assertRedirect(route('superadmin.periode-skp.index'));

        $this->assertSame('aktif', $tw1->fresh()->status);
        $this->assertSame('aktif', $tw2->fresh()->status);
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_update_semester_label_dan_bulan_nullable(): void
    {
        $p = PeriodeSkp::create($this->payload(['status' => 'draft']));

        // Kirim tanpa semester_genap/gasal, tanpa bulan_akhir → tetap lolos.
        $r = $this->actingAs($this->admin)->put(route('superadmin.periode-skp.update', $p), $this->payload([
            'label_triwulan' => 'TW Update',
        ]));
        $r->assertRedirect(route('superadmin.periode-skp.index'));
        $r->assertSessionHas('success');
        $this->assertSame('TW Update', $p->fresh()->label_triwulan);
    }

    // =========================================================================
    // Render view per kelompok (label baru, tanpa "Bulan Februari")
    // =========================================================================

    private function buatTigaPeriode(): array
    {
        $tw2 = PeriodeSkp::create($this->payload([
            'bulan' => 2,
            'mulai' => '2026-04-01', 'selesai' => '2026-06-30', 'status' => 'aktif',
        ]));
        $sem1 = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2026-01-01', 'selesai' => '2026-06-30', 'status' => 'draft',
        ]);
        $tahunan = PeriodeSkp::create([
            'tahun' => 2026, 'tipe' => 'tahunan',
            'mulai' => '2026-01-01', 'selesai' => '2026-12-31', 'status' => 'draft',
        ]);

        return [$tw2, $sem1, $tahunan];
    }

    public function test_render_skp_dosen_create_tampil_label_baru(): void
    {
        $this->buatTigaPeriode();

        $r = $this->actingAs($this->admin)->get(route('superadmin.skp-dosen.create'));
        $r->assertOk();
        $r->assertSee('Triwulan II 2026', false);
        $r->assertSee('Ganjil 2026', false);
        $r->assertSee('Tahunan 2026', false);
        $r->assertDontSee('Bulan Februari', false);
    }

    public function test_render_skp_dosen_index_tampil_label_baru(): void
    {
        [$tw2, $sem1, $tahunan] = $this->buatTigaPeriode();

        $r = $this->actingAs($this->admin)->get(route('superadmin.skp-dosen.index', ['periode_id' => $tw2->id]));
        $r->assertOk();
        $r->assertSee('Triwulan II 2026', false);
        $r->assertDontSee('Bulan Februari', false);
    }

    public function test_render_skp_dosen_kualitatif_index_tampil_label_baru(): void
    {
        $this->buatTigaPeriode();

        $r = $this->actingAs($this->admin)->get(route('superadmin.skp-dosen-kualitatif.index'));
        $r->assertOk();
        $r->assertSee('Triwulan II 2026', false);
        $r->assertSee('Ganjil 2026', false);
        $r->assertSee('Tahunan 2026', false);
        $r->assertDontSee('Bulan Februari', false);
    }

    public function test_render_rekap_skp_tampil_label_baru(): void
    {
        $this->buatTigaPeriode();

        $r = $this->actingAs($this->admin)->get(route('rekap-skp.index'));
        $r->assertOk();
        $r->assertSee('Triwulan II 2026', false);
        $r->assertSee('Ganjil 2026', false);
        $r->assertSee('Tahunan 2026', false);
        $r->assertDontSee('Bulan Februari', false);
    }

    public function test_render_periode_skp_index_tampil_label_baru_dan_badge_tipe(): void
    {
        $this->buatTigaPeriode();

        $r = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.index'));
        $r->assertOk();
        $r->assertSee('Triwulan II 2026', false);
        $r->assertSee('Ganjil 2026', false);
        $r->assertSee('Tahunan 2026', false);
        $r->assertSee('Bulanan (Triwulan)', false);
        $r->assertDontSee('Bulan Februari', false);
    }

    /** T17: Ganjil lintas tahun — simpan, bentrok, tampil di daftar. */
    public function test_semester_ganjil_lintas_tahun_simpan_bentrok_tampil(): void
    {
        // Simpan Ganjil 2027 (Sep 2027 s.d. Feb 2028) sukses.
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'tahun' => 2027,
            'tipe' => 'semester', 'semester' => 'ganjil',
            'mulai' => '2027-09-01', 'selesai' => '2028-02-28',
            'batas_pengisian' => '2027-09-30', 'batas_penilaian' => '2028-01-31',
        ]));
        $r->assertSessionHas('success');

        $p = PeriodeSkp::where('tipe', 'semester')->where('semester', 'ganjil')
            ->where('tahun', 2027)->first();
        $this->assertNotNull($p);
        $this->assertSame('Ganjil 2027', $p->nama_periode);
        $this->assertSame('2027-09-01', $p->mulai->toDateString());
        $this->assertSame('2028-02-28', $p->selesai->toDateString());

        // Bentrok dgn Ganjil 2027 lain (overlap Sep-Feb) → ditolak (flash error).
        $r = $this->actingAs($this->admin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), $this->payload([
                'tipe' => 'semester', 'semester' => 'ganjil',
                'mulai' => '2027-10-01', 'selesai' => '2028-01-31',
                'batas_pengisian' => '2027-11-30', 'batas_penilaian' => '2028-01-15',
            ]));
        $r->assertSessionHas('error');
        $this->assertSame(1, PeriodeSkp::where('tipe', 'semester')->where('semester', 'ganjil')->count());

        // Genap 2027 (Mar-Agu 2027) tidak bentrok dgn Ganjil 2027 → sukses.
        $r = $this->actingAs($this->admin)->post(route('superadmin.periode-skp.store'), $this->payload([
            'tipe' => 'semester', 'semester' => 'genap',
            'mulai' => '2027-03-01', 'selesai' => '2027-08-31',
            'batas_pengisian' => '2027-04-30', 'batas_penilaian' => '2027-08-15',
        ]));
        $r->assertSessionHas('success');

        // Tampil di daftar dgn label Ganjil 2027, bukan 'Semester I 2027'.
        $r = $this->actingAs($this->admin)->get(route('superadmin.periode-skp.index'));
        $r->assertOk();
        $r->assertSee('Ganjil 2027', false);
        $r->assertDontSee('Semester I 2027', false);
        $r->assertDontSee('Semester II 2027', false);
    }
}
