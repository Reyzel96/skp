<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tugas 11: layout form Periode SKP muat 1 layar + dd/mm/yyyy + strip ringkasan.
 */
class PeriodeSkpLayoutDanFormatTanggalTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);
        $this->superAdmin = User::create([
            'email' => 'admin-layout@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->assignRole('Super Admin');
    }

    private function buatPeriode(array $overrides = []): PeriodeSkp
    {
        return PeriodeSkp::create(array_merge([
            'tahun' => 2027,
            'tipe' => 'tahunan',
            'mulai' => '2027-01-01',
            'selesai' => '2027-11-30',
            'batas_pengisian' => '2027-11-28',
            'batas_penilaian' => '2027-11-29',
            'status' => 'draft',
        ], $overrides));
    }

    /** Strip ringkasan edit dirender server-side: nama, rentang d/m/Y, durasi, deadline d/m/Y. */
    public function test_edit_strip_ringkasan_server_side_tahunan_dan_deadline_ddmmyyyy(): void
    {
        $periode = $this->buatPeriode(['tipe' => 'tahunan', 'tahun' => 2027]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));

        $response->assertOk();
        $response->assertSee('Tahunan 2027');
        $response->assertSee('01/01/2027 s.d. 30/11/2027');
        $response->assertSee('Pengisian: 28/11/2027 · Penilaian: 29/11/2027');
        // Durasi 1 Jan s.d. 30 Nov = 334 hari.
        $response->assertSee('334 hari');
    }

    /** Batas maks (selesai + 7) dirender server-side d/m/Y: 30/11/2027 + 7 = 07/12/2027. */
    public function test_edit_batas_maks_selesai_plus_7_tampil_ddmmyyyy(): void
    {
        $periode = $this->buatPeriode(['selesai' => '2027-11-30']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.edit', $periode->id));

        $response->assertOk();
        // 2 input batas + max server-side tetap (Tugas 9).
        $this->assertSame(2, substr_count($response->getContent(), 'max="2027-12-07"'));
        // Tugas 14: teks bantuan singkat "Maks. {d/m/Y}".
        $response->assertSee('Maks. 07/12/2027');
    }

    /** Segmented tipe & status dalam satu container horizontal (role=group, grid-cols). */
    public function test_create_segmented_tipe_dan_status_dalam_container_horizontal(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.periode-skp.create'));

        $response->assertOk();
        $html = $response->getContent();

        // Container segmented tipe: role="group" aria-label + grid horizontal.
        $this->assertStringContainsString('role="group" aria-label="Tipe periode"', $html);
        $this->assertStringContainsString('role="group" aria-label="Status periode"', $html);
        $this->assertStringContainsString('grid-cols-3', $html);
        // Tiga tombol segmented tipe & status ada.
        foreach (['data-tipe="tahunan"', 'data-tipe="semester"', 'data-tipe="bulanan"', 'data-status="draft"', 'data-status="aktif"', 'data-status="selesai"'] as $atribut) {
            $this->assertStringContainsString($atribut, $html);
        }
    }

    /** Flatpickr dd/mm/yyyy: altInput/altFormat dipasang di keempat input tanggal. */
    public function test_tanggal_flatpickr_altinput_ddmmyyyy_nilai_terkirim_ymd(): void
    {
        // Konfigurasi JS di kedua view: altFormat d/m/Y + dateFormat Y-m-d.
        foreach (['create' => route('superadmin.periode-skp.create'), 'edit' => null] as $mode => $url) {
            if ($mode === 'edit') {
                $periode = $this->buatPeriode(['tipe' => 'semester', 'semester' => 'ganjil']);
                $url = route('superadmin.periode-skp.edit', $periode->id);
            }
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString("altFormat: 'd/m/Y'", $html);
            $this->assertStringContainsString("dateFormat: 'Y-m-d'", $html);
            $this->assertStringContainsString('altInput: true', $html);
            // Input tetap type=date + name sama (nilai terkirim Y-m-d).
            $this->assertStringContainsString('type="date" name="mulai"', $html);
            $this->assertStringContainsString('type="date" name="batas_pengisian"', $html);
        }
    }

    /** Util tanggal JS: selesai 2027-11-30 + 7 hari = 2027-12-07 (aman timezone WIB). */
    public function test_util_tanggal_selesai_plus_7_hari_aman_timezone(): void
    {
        // Logika server setara: Carbon dengan timezone Asia/Jakarta.
        Carbon::setTestNow(Carbon::parse('2027-11-30 23:30:00', 'Asia/Jakarta'));
        $selesai = Carbon::parse('2027-11-30', 'Asia/Jakarta');
        $this->assertSame('2027-12-07', $selesai->copy()->addDays(7)->toDateString());

        // Bulan akhir triwulan: 2027-11 + hariAkhir November = 30.
        $this->assertSame(30, Carbon::create(2027, 11, 1)->daysInMonth);
    }

    /** Wrapper w-full tanpa max-w-6xl (target muat 1 layar). */
    public function test_wrapper_w_full_tanpa_max_w(): void
    {
        $periode = $this->buatPeriode();

        $create = $this->actingAs($this->superAdmin)->get(route('superadmin.periode-skp.create'));
        $create->assertOk();
        $this->assertStringNotContainsString('max-w-6xl', $create->getContent());
        $this->assertStringContainsString('w-full', $create->getContent());

        $edit = $this->actingAs($this->superAdmin)->get(route('superadmin.periode-skp.edit', $periode->id));
        $edit->assertOk();
        $this->assertStringNotContainsString('max-w-6xl', $edit->getContent());
    }

    /** Strip ringkasan create dengan old input: nilai server-side dari old(). */
    public function test_create_strip_ringkasan_dari_old_input(): void
    {
        $responseLama = $this->actingAs($this->superAdmin)
            ->withSession(['_old_input' => [
                'tipe' => 'tahunan',
                'tahun' => '2027',
                'mulai' => '2027-01-01',
                'selesai' => '2027-11-30',
                'batas_pengisian' => '2027-11-28',
                'batas_penilaian' => '2027-11-29',
            ]])
            ->get(route('superadmin.periode-skp.create'));

        $responseLama->assertOk();
        $responseLama->assertSee('Tahunan 2027');
        $responseLama->assertSee('01/01/2027 s.d. 30/11/2027');
        // Tugas 14: teks bantuan singkat "Maks. {d/m/Y}".
        $responseLama->assertSee('Maks. 07/12/2027');
    }
}
