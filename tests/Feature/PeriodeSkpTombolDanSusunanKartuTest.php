<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * TUGAS 12 — Tombol Simpan terlihat & berfungsi, susunan kartu 3 kolom sejajar
 * (>=xl), keterangan rentang kondisional per tipe, teks "Paling lambat" di baris
 * sendiri, anti double-click.
 */
class PeriodeSkpTombolDanSusunanKartuTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->superAdmin = User::create([
            'email' => 'admin-t12-' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('Super Admin');
    }

    private function buatPeriode(array $overrides = []): PeriodeSkp
    {
        return PeriodeSkp::create(array_merge([
            'tahun' => 2026,
            'tipe' => 'tahunan',
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
            'batas_pengisian' => '2026-12-30',
            'batas_penilaian' => '2026-12-31',
            'status' => 'draft',
        ], $overrides));
    }

    /** Poin 1+T13: tombol Simpan di header tampil SEMUA lebar layar; action bar bawah dihapus. */
    public function test_tombol_simpan_tersedia_di_header_dan_di_bawah(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            // Header: container SELALU flex (tanpa breakpoint) + tombol submit form="form-periode".
            $this->assertStringContainsString('flex items-center gap-2 ml-auto', $html, $mode);
            $this->assertStringNotContainsString('hidden lg:flex', $html, $mode);
            $this->assertStringContainsString('form="form-periode"', $html, $mode);
            // T13: action bar bawah dihapus — tidak ada tombol duplikat.
            $this->assertStringNotContainsString('lg:hidden', $html, $mode);
            // Anti double-click marker.
            $this->assertStringContainsString('data-tombol-simpan', $html, $mode);
            $this->assertStringContainsString('Menyimpan', $html, $mode);
        }
    }

    /** Poin 1: submit create & edit tersimpan benar (tombol berfungsi). */
    public function test_submit_create_dan_edit_tersimpan_benar(): void
    {
        // CREATE.
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2027,
                'tipe' => 'tahunan',
                'mulai' => '2027-01-01',
                'selesai' => '2027-12-31',
                'batas_pengisian' => '2027-12-30',
                'batas_penilaian' => '2027-12-31',
                'status' => 'draft',
            ]);
        $periode = PeriodeSkp::where('tahun', 2027)->first();
        $this->assertNotNull($periode);
        $this->assertSame('2027-12-30', $periode->batas_pengisian->toDateString());

        // EDIT.
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.edit', $periode->id))
            ->put(route('superadmin.periode-skp.update', $periode->id), [
                'tipe' => 'tahunan',
                'tahun' => 2027,
                'mulai' => '2027-01-01',
                'selesai' => '2027-12-31',
                'batas_pengisian' => '2027-12-31',
                'batas_penilaian' => '2027-12-31',
                'status' => 'draft',
            ]);
        $periode->refresh();
        $this->assertSame('2027-12-31', $periode->batas_pengisian->toDateString());
    }

    /** Poin 2 (Tugas 14): grid 2 kolom lg+ (kiri 1.1fr, kanan 1fr), kartu stretch h-full. */
    public function test_grid_tiga_kolom_mulai_xl_dan_kartu_tinggi_sama(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            // Tugas 14: grid 2 kolom mulai lg dengan rasio kiri 1.1fr : kanan 1fr.
            $this->assertStringContainsString('lg:grid-cols-[1.1fr_1fr]', $html, $mode);
            $this->assertStringContainsString('items-stretch', $html, $mode);
            // Kartu stretch tinggi sama (2 kartu form + wrapper kolom kanan).
            $this->assertGreaterThanOrEqual(2, substr_count($html, 'flex flex-col h-full'), $mode);
            // Kelas lama tidak tersisa.
            $this->assertStringNotContainsString('2xl:grid-cols-3', $html, $mode);
            $this->assertStringNotContainsString('xl:grid-cols-3', $html, $mode);
            $this->assertStringNotContainsString('lg:col-span-2', $html, $mode);
        }
    }

    /** Poin 3 (Tugas 14): teks keterangan panjang dihapus — tipe tahunan tanpa keterangan triwulan. */
    public function test_tipe_tahunan_tidak_menampilkan_keterangan_triwulan(): void
    {
        // Edit periode tahunan: elemen keterangan rentang sudah DIHAPUS (Tugas 14).
        $periode = $this->buatPeriode(['tipe' => 'tahunan']);
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('id="keterangan-bulanan"', $html);
        $this->assertStringNotContainsString('id="keterangan-semester"', $html);
        $this->assertStringNotContainsString('id="keterangan-rentang"', $html);
        // Teks keterangan panjang tidak ada lagi.
        $this->assertStringNotContainsString('Triwulan I: 1 Januari', $html);
    }

    /** Poin 3 (Tugas 14): teks keterangan panjang dihapus untuk tipe bulanan juga. */
    public function test_tipe_bulanan_menampilkan_keterangan_triwulan(): void
    {
        $periode = $this->buatPeriode(['tipe' => 'bulanan', 'bulan' => 2]);
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));
        $response->assertOk();
        $html = $response->getContent();

        // Teks keterangan panjang sudah dihapus — chip Triwulan tetap ada.
        $this->assertStringContainsString('data-triwulan="2"', $html);
        $this->assertStringNotContainsString('Triwulan I: 1 Januari', $html);
    }

    /** Poin 5 (Tugas 14): "Maks. dd/mm/yyyy" di baris sendiri (p terpisah), tooltip di label. */
    public function test_teks_paling_lambat_di_baris_sendiri_dan_tooltip_di_label(): void
    {
        $periode = $this->buatPeriode(['selesai' => '2026-12-31']);
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));
        $response->assertOk();
        $html = $response->getContent();

        // <p> terpisah dengan atribut data-teks-paling-lambat (baris sendiri di bawah input).
        $this->assertSame(2, substr_count($html, 'data-teks-paling-lambat='), 'p terpisah');
        $this->assertMatchesRegularExpression(
            '/data-teks-paling-lambat="batas_pengisian"\s*>Maks\. 07\/01\/2027</',
            $html
        );
        // Penjelasan panjang kini di atribut title pada LABEL (Tugas 14: tanpa ikon (i)).
        $this->assertStringNotContainsString('fa-info-circle', $html);
        $this->assertStringContainsString('title="Pegawai tidak bisa lagi menyimpan realisasi', $html);
        $this->assertStringContainsString('title="Atasan tidak bisa lagi memverifikasi', $html);
        // Struktur lama (berdampingan dalam satu <p> flex) tidak ada lagi.
        $this->assertStringNotContainsString('whitespace-nowrap text-gray-700 font-bold', $html);
    }

    /** Poin 5: JS updateDateConstraints menargetkan selector baru. */
    public function test_js_menargetkan_teks_paling_lambat_baru(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString("querySelector('[data-teks-paling-lambat]')", $html, $mode);
            $this->assertStringNotContainsString("querySelector('p.text-xs')", $html, $mode);
            // Anti double-click: submit handler menandai form.
            $this->assertStringContainsString("formPeriode.addEventListener('submit'", $html, $mode);
        }
    }

    /** T14 (A): header sentence case 'Batal' + 'Simpan'/'Simpan perubahan'; chip 'Triwulan'. */
    public function test_header_sentence_case_dan_chip_triwulan(): void
    {
        $create = $this->actingAs($this->superAdmin)->get(route('superadmin.periode-skp.create'));
        $create->assertOk();
        $this->assertMatchesRegularExpression('/>\s*Simpan\s*<\/button>/', $create->getContent());
        $this->assertMatchesRegularExpression('/>\s*Batal\s*<\/a>/', $create->getContent());
        $this->assertStringNotContainsString('SIMPAN PERIODE', $create->getContent());
        // Chip tipe 'Triwulan' (bukan 'Bulanan (Triwulan)'); nilai DB tetap 'bulanan'.
        $this->assertStringContainsString('Triwulan', $create->getContent());
        $this->assertStringNotContainsString('Bulanan (Triwulan)', $create->getContent());
        $this->assertStringContainsString('data-tipe="bulanan"', $create->getContent());

        $edit = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $this->buatPeriode()->id));
        $edit->assertOk();
        $this->assertStringContainsString('Simpan perubahan', $edit->getContent());
        $this->assertStringNotContainsString('SIMPAN PERUBAHAN', $edit->getContent());
    }

    /** T14 (B): flash session('error') dari controller DITAMPILKAN di banner atas form. */
    public function test_flash_error_ditampilkan_di_form(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->withSession(['error' => 'Periode ini bentrok dengan periode #1'])->get($url);
            $response->assertOk();
            $response->assertSee('Periode ini bentrok dengan periode #1', false);
        }
    }

    /** T14 (B): store bentrok → redirect back dengan flash error (terlihat di form). */
    public function test_store_bentrok_flash_error_terlihat(): void
    {
        $this->buatPeriode(['tahun' => 2027, 'mulai' => '2027-01-01', 'selesai' => '2027-12-31']);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2027,
                'tipe' => 'tahunan',
                'mulai' => '2027-02-01',
                'selesai' => '2027-03-31',
                'batas_pengisian' => '2027-03-30',
                'batas_penilaian' => '2027-03-31',
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('superadmin.periode-skp.create'));
        $response->assertSessionHas('error');
        // Flash error ditampilkan di form create.
        $render = $this->actingAs($this->superAdmin)
            ->withSession(['error' => session('error')])
            ->get(route('superadmin.periode-skp.create'));
        $render->assertSee('Periode gagal dibuat');
    }

    /** T14 (A): tipe beda tahun sama TIDAK dilanggar rule unique (tahun+mulai+selesai). */
    public function test_tipe_beda_tahun_sama_berhasil(): void
    {
        $this->buatPeriode(['tahun' => 2027, 'tipe' => 'tahunan', 'mulai' => '2027-01-01', 'selesai' => '2027-12-31']);

        // Triwulan I 2027 → BERHASIL (rentang beda).
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2027,
                'tipe' => 'bulanan',
                'bulan' => 1,
                'mulai' => '2027-01-01',
                'selesai' => '2027-03-31',
                'batas_pengisian' => '2027-04-05',
                'batas_penilaian' => '2027-04-07',
                'status' => 'draft',
            ]);
        $this->assertDatabaseHas('periode_skp', ['tahun' => 2027, 'tipe' => 'bulanan', 'bulan' => 1]);

        // Semester I 2027 → BERHASIL.
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2027,
                'tipe' => 'semester',
                'semester' => 'ganjil',
                'mulai' => '2027-01-01',
                'selesai' => '2027-06-30',
                'batas_pengisian' => '2027-06-28',
                'batas_penilaian' => '2027-06-29',
                'status' => 'draft',
            ]);
        $this->assertDatabaseHas('periode_skp', ['tahun' => 2027, 'tipe' => 'semester', 'semester' => 'ganjil']);
    }

    /** T14 (A): tautan '+ Label khusus (opsional)' membuka panel field (details dihapus). */
    public function test_label_khusus_via_tautan(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            $response->assertSee('+ Label khusus (opsional)', false);
            $this->assertStringContainsString('id="tampilkan-label-khusus"', $html);
            $this->assertStringContainsString('id="panel-label-khusus"', $html);
            // details/summary lama tidak ada lagi.
            $this->assertStringNotContainsString('<details', $html);
        }
    }

    /** T14 (A): default batas JS — pengisian selesai+5, penilaian selesai+7. */
    public function test_js_default_batas_pengisian_plus5_penilaian_plus7(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('PENGISIAN_HARI_DEFAULT = 5', $html, $mode);
            $this->assertStringContainsString('addDaysIso(range.selesai, PENGISIAN_HARI_DEFAULT)', $html, $mode);
            $this->assertStringContainsString('addDaysIso(range.selesai, MAKS_HARI_SETELAH_SELESAI)', $html, $mode);
            // Batas juga punya dataset.manual listener.
            $this->assertStringContainsString('inputBatasPengisian.dataset.manual', $html, $mode);
            $this->assertStringContainsString('inputBatasPenilaian.dataset.manual', $html, $mode);
        }
    }

    /** T15: init form Turbo-compatible — pemicu initPeriodeForm, bukan hanya DOMContentLoaded. */
    public function test_init_turbo_compatible(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            // Fungsi init bernama + pemicu ganda (readyState + DOMContentLoaded).
            $this->assertStringContainsString('function initPeriodeForm()', $html, $mode);
            $this->assertStringContainsString("document.addEventListener('DOMContentLoaded', initPeriodeForm)", $html, $mode);
            $this->assertStringContainsString('initPeriodeForm();', $html, $mode);
            // Fallback invalid: kotak pesan + listener invalid (capture).
            $this->assertStringContainsString('id="pesan-form-invalid"', $html, $mode);
            $this->assertStringContainsString("addEventListener('invalid'", $html, $mode);
        }
    }

    /** T16: penghalang submit dihapus — tanpa confirm, tanpa intercept Turbo, anti double-click di submit. */
    public function test_form_tanpa_penghalang_submit(): void
    {
        foreach ([
            'create' => route('superadmin.periode-skp.create'),
            'edit' => route('superadmin.periode-skp.edit', $this->buatPeriode()->id),
        ] as $mode => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            // data-turbo="false" pada form (submit native, tidak di-intercept Turbo).
            $this->assertStringContainsString('data-turbo="false"', $html, $mode);
            // onsubmit confirm() DIHAPUS dari form periode (form logout di layout memang memakai onsubmit — bukan bagian dari form ini).
            $this->assertStringNotContainsString('return confirm(\'Konfirmasi Penyimpanan', $html, $mode);
            $this->assertMatchesRegularExpression('/<form[^>]*id="form-periode"[^>]*>/', $html, $mode);
            $formTag = preg_match('/<form[^>]*id="form-periode"[^>]*>/', $html, $m) ? $m[0] : '';
            $this->assertStringNotContainsString('onsubmit', $formTag, $mode);
            // Anti double-click: disable via setTimeout(...,0), re-enable 3 detik.
            $this->assertStringContainsString('dataset.mengirim', $html, $mode);
            $this->assertStringContainsString('}, 3000)', $html, $mode);
            // Bfcache Turbo: penanda init dibuang sebelum cache.
            $this->assertStringContainsString("addEventListener('turbo:before-cache'", $html, $mode);
            // Spinner masih ada.
            $this->assertStringContainsString('Menyimpan', $html, $mode);
        }
    }

    /** T15 langkah 7: simpan payload PERSIS gejala user (Tahunan 2027, batas 2028-01-03 keduanya, aktif). */
    public function test_simpan_payload_persis_gejala_berhasil(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('superadmin.periode-skp.create'))
            ->post(route('superadmin.periode-skp.store'), [
                'tahun' => 2027,
                'tipe' => 'tahunan',
                'mulai' => '2027-01-01',
                'selesai' => '2027-12-31',
                'batas_pengisian' => '2028-01-03',
                'batas_penilaian' => '2028-01-03',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('superadmin.periode-skp.index'));

        $periode = PeriodeSkp::where('tahun', 2027)->where('tipe', 'tahunan')->first();
        $this->assertNotNull($periode);
        $this->assertSame('aktif', $periode->status);
        $this->assertSame('2028-01-03', $periode->batas_pengisian->toDateString());
        $this->assertSame('2028-01-03', $periode->batas_penilaian->toDateString());
    }
}
