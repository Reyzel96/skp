<?php

namespace Tests\Feature;

use App\Models\PeriodeSkp;
use App\Models\SkpHeader;
use App\Models\SkpKualitatifAcuanParent;
use App\Models\SkpKualitatifAcuanChild;
use App\Models\SkpKualitatifRealisasi;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkpDosenKualitatifShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bangun graf relasi minimal agar view bisa render tanpa error:
     * owner + penilai (masing-masing punya profile), periode, header.
     * Section SKP/Evaluasi dibiarkan kosong agar loop di view terlewati.
     */
    private function baseContext(): array
    {
        $owner = User::create([
            'email' => 'owner-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        UserProfile::create([
            'user_id' => $owner->id,
            'nama_lengkap' => 'Owner Test',
            'nip' => '123456',
            'pangkat_golongan' => 'III/a',
        ]);

        $penilai = User::create([
            'email' => 'penilai-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        UserProfile::create([
            'user_id' => $penilai->id,
            'nama_lengkap' => 'Penilai Test',
            'nip' => '654321',
            'pangkat_golongan' => 'IV/a',
        ]);

        $periode = PeriodeSkp::create([
            'tahun' => 2026,
            'tipe' => 'tahunan',
            'mulai' => '2026-01-01',
            'selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $header = SkpHeader::create([
            'user_id' => $owner->id,
            'periode_skp_id' => $periode->id,
            'pejabat_penilai_id' => $penilai->id,
            'status' => 'aktif',
        ]);

        // show() memanggil SkpKualitatif::where('skp_header_id', $id)->firstOrFail(),
        // jadi minimal satu row SkpKualitatif harus ada agar tidak 404.
        \App\Models\SkpKualitatif::create([
            'skp_header_id' => $header->id,
            'jenis' => 'utama',
            'rencana_hasil_kerja' => 'Rencana contoh',
            'indikator_target' => 'Indikator contoh',
        ]);

        // Acuan perilaku (kategori='perilaku') agar $perilakuAcuan tidak kosong saat di-query view.
        $acuanParent = SkpKualitatifAcuanParent::create([
            'kategori' => 'perilaku',
            'sasaran_kinerja' => 'Kerjasama',
        ]);
        SkpKualitatifAcuanChild::create([
            'parent_id' => $acuanParent->id,
            'butuh_lampiran' => false,
        ]);

        return [$owner, $header];
    }

    /**
     * Skenario A: header TANPA realisasi sama sekali.
     * GET show harus 200, dan tombol header "Simpan Realisasi" MUNCUL.
     */
    public function test_show_without_realisasi_returns_200_and_button_visible(): void
    {
        [$owner, $header] = $this->baseContext();

        $response = $this->actingAs($owner)
            ->get(route('pegawai.skp-dosen-kualitatif.show', $header->id));

        $response->assertStatus(200);
        $response->assertSee('Simpan Realisasi'); // tombol header tampil (belum tersimpan)
    }

    /**
     * Skenario B: header DENGAN realisasi di semua section (simulasi sudah tersimpan).
     * GET show harus 200, dan tombol header "Simpan Realisasi" TIDAK tampil.
     */
    public function test_show_with_all_sections_saved_returns_200_and_button_hidden(): void
    {
        [$owner, $header] = $this->baseContext();

        SkpKualitatifRealisasi::create([
            'skp_header_id' => $header->id,
            'target_id' => 1,
            'acuan_child_id' => null,
            'target_type' => 'utama',
            'realisasi' => 'sudah tersimpan utama',
        ]);
        SkpKualitatifRealisasi::create([
            'skp_header_id' => $header->id,
            'target_id' => 9,
            'acuan_child_id' => null,
            'target_type' => 'perilaku',
            'realisasi' => 'sudah tersimpan perilaku',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('pegawai.skp-dosen-kualitatif.show', $header->id));

        $response->assertStatus(200);
        // isRealisasiSaved=true -> tombol header disembunyikan
        $response->assertDontSee('Simpan Realisasi');
    }

    /**
     * Skenario C: header dengan SEBAGIAN section tersimpan (partial, dari Poin 2).
     * GET show harus 200 (tidak 500), sesuai definisi any-section.
     */
    public function test_show_with_partial_sections_returns_200(): void
    {
        [$owner, $header] = $this->baseContext();

        // Hanya section utama yang tersimpan; tambahan & perilaku belum.
        SkpKualitatifRealisasi::create([
            'skp_header_id' => $header->id,
            'target_id' => 1,
            'acuan_child_id' => null,
            'target_type' => 'utama',
            'realisasi' => 'hanya utama',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('pegawai.skp-dosen-kualitatif.show', $header->id));

        $response->assertStatus(200);
        // Definisi: any-section -> isRealisasiSaved true -> tombol header hidden.
        $response->assertDontSee('Simpan Realisasi');
    }
}
