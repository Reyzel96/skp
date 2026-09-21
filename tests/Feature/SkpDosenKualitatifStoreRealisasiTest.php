<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\SkpKualitatifRealisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkpDosenKualitatifStoreRealisasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat user pemilik SKP + sejumlah row realisasi agar guard bisa dicek.
     */
    private function ownerWithRealisasi(int $count = 2): array
    {
        $user = User::create([
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'tahunan',
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
        $header = SkpHeader::create([
            'user_id' => $user->id,
            'periode_skp_id' => $periode->id,
            'status' => 'aktif',
        ]);

        for ($i = 0; $i < $count; $i++) {
            SkpKualitatifRealisasi::create([
                'skp_header_id' => $header->id,
                'target_id' => $i + 1,
                'acuan_child_id' => null,
                'target_type' => 'utama',
                'realisasi' => 'data lama ke-' . $i,
            ]);
        }

        return [$user, $header];
    }

    /**
     * Skenario 1: request TANPA field 'realisasi' -> redirect back + session('error'),
     * dan row realisasi yang sudah ada TIDAK terhapus (jumlah tak berubah).
     */
    public function test_request_without_realisasi_field_keeps_existing_data_and_redirects_with_error(): void
    {
        [$user, $header] = $this->ownerWithRealisasi(2);
        $before = SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count();

        $response = $this->actingAs($user)
            ->post(route('pegawai.skp-dosen-kualitatif.store-realisasi', $header->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $after = SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count();
        $this->assertSame($before, $after);
        $this->assertSame(2, $after);
    }

    /**
     * Skenario 2: request DENGAN field 'realisasi' terisi -> tersimpan normal,
     * guard tidak mengganggu flow sukses.
     */
    public function test_request_with_realisasi_field_saves_data_normally(): void
    {
        [$user, $header] = $this->ownerWithRealisasi(1);

        $response = $this->actingAs($user)
            ->post(route('pegawai.skp-dosen-kualitatif.store-realisasi', $header->id), [
                'realisasi' => [
                    'utama' => [1 => 'Realisasi baru A'],
                    'perilaku' => [2 => 'Realisasi baru B'],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Controller clear-all lalu insert dari request: 1 utama + 1 perilaku = 2 row.
        $this->assertSame(
            2,
            SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count()
        );
    }

    /**
     * Skenario 3 (a): tombol HEADER diklik saat $isRealisasiSaved sudah true.
     * Simulasi: header sudah punya row realisasi (field terkunci disabled), lalu tombol
     * header dipakai -> request POST TANPA field 'realisasi' (karena disabled).
     * Harus: redirect + session('error'), dan row lama TIDAK terhapus.
     */
    public function test_header_button_click_when_already_saved_does_not_lose_data(): void
    {
        [$user, $header] = $this->ownerWithRealisasi(3);
        $before = SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count();

        $response = $this->actingAs($user)
            ->post(route('pegawai.skp-dosen-kualitatif.store-realisasi', $header->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $after = SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count();
        $this->assertSame($before, $after);
        $this->assertSame(3, $after);
    }

    /**
     * Skenario 3 (b): request realisasi PARSIAL — hanya section 'utama' terisi,
     * 'perilaku' tidak dikirim meskipun ada row existing-nya.
     * Harus: section utama ter-update sesuai request, TAPI row perilaku lama tetap ada & tidak berubah.
     */
    public function test_partial_section_request_keeps_other_sections_intact(): void
    {
        [$user, $header] = $this->ownerWithRealisasi(1);

        // Tambahkan row perilaku yang sudah ada sebelumnya (simulasi data lama).
        \App\Models\SkpKualitatifRealisasi::create([
            'skp_header_id' => $header->id,
            'target_id' => 9,
            'acuan_child_id' => null,
            'target_type' => 'perilaku',
            'realisasi' => 'realisasi perilaku lama',
        ]);

        $countBefore = SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count();
        $this->assertSame(2, $countBefore); // 1 utama + 1 perilaku

        // POST hanya berisi section utama (perilaku tidak dikirim).
        $response = $this->actingAs($user)
            ->post(route('pegawai.skp-dosen-kualitatif.store-realisasi', $header->id), [
                'realisasi' => [
                    'utama' => [1 => 'Realisasi utama baru'],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Section utama: hapus lama (1 row) + insert baru (1 row) = tetap 1 row, isi berubah.
        $utama = SkpKualitatifRealisasi::where('skp_header_id', $header->id)
            ->where('target_type', 'utama')->get();
        $this->assertCount(1, $utama);
        $this->assertSame('Realisasi utama baru', $utama->first()->realisasi);

        // Section perilaku: tidak ada di request -> row lama TETAP ADA, tidak berubah.
        $perilaku = SkpKualitatifRealisasi::where('skp_header_id', $header->id)
            ->where('target_type', 'perilaku')->get();
        $this->assertCount(1, $perilaku);
        $this->assertSame('realisasi perilaku lama', $perilaku->first()->realisasi);

        // Total row tidak berkurang (utama replace 1:1, perilaku dipertahankan).
        $this->assertSame(
            2,
            SkpKualitatifRealisasi::where('skp_header_id', $header->id)->count()
        );
    }
}
