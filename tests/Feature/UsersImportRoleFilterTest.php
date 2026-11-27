<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Imports\UsersImport;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * Tugas 26 — tutup celah role di UsersImport.
 *
 * Aturan (sama seperti usersStore):
 * - Jabatan utama hanya role kategori 'utama' DAN bukan 'Super Admin'.
 * - Peran tambahan hanya kategori 'tendik'.
 * - Role tidak valid TIDAK di-assign; baris tanpa role valid dilewati.
 *
 * Catatan kategori: migration permission_tables default enum kategori='tendik';
 * produksi (RoleAndAdminSeeder) men-set Super Admin kategori='utama' — setUp di
 * bawah meniru produksi agar uji realistis.
 */
class UsersImportRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    private int $rowIndex = 1; // heading = baris Excel 1 → data mulai index 1 (baris Excel 2)

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['akses-dashboard-pimpinan', 'akses-dashboard-prodi', 'akses-manajemen-anggaran'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $superadmin = Role::findOrCreate('Super Admin', 'web');
        $superadmin->kategori = 'utama'; // mirror produksi RoleAndAdminSeeder
        $superadmin->dashboard_route = 'superadmin.dashboard';
        $superadmin->save();

        $dosen = Role::findOrCreate('Dosen', 'web');
        $dosen->kategori = 'utama';
        $dosen->save();

        $tendik = Role::findOrCreate('Staf TU', 'web');
        $tendik->kategori = 'tendik';
        $tendik->save();
    }

    /** Panggil UsersImport::onRow dengan format kolom Excel yang sama (heading slug). */
    private function impor(array $data): mixed
    {
        // Buat Row asli via PhpSpreadsheet: 1 sheet, baris berisi nilai sesuai heading.
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $kolom = 'A';
        foreach (array_values($data) as $nilai) {
            $sheet->setCellValue($kolom++ . $this->rowIndex, $nilai);
        }
        $spreadsheetRow = new \PhpOffice\PhpSpreadsheet\Worksheet\Row($sheet, $this->rowIndex);

        // headingRow memetakan kolom → slug header (sama dgn perilaku WithHeadingRow).
        $headingRow = array_combine(
            range(0, count($data) - 1),
            array_map(fn ($k) => $k, array_keys($data))
        );
        $headerIsGrouped = array_fill(0, count($data), false);

        $excelRow = new \Maatwebsite\Excel\Row($spreadsheetRow, $headingRow, $headerIsGrouped);
        $this->rowIndex++;

        return (new UsersImport)->onRow($excelRow);
    }

    private function dataValid(array $overrides = []): array
    {
        return array_merge([
            'email_kampus' => 'uji-import@gmail.com',
            'nip' => '199000000000000001',
            'nama_lengkap_gelar' => 'User Uji Import',
            'password' => 'rahasia123',
            'jabatan_utama' => 'Dosen',
            'peran_tambahan_tendik_opsional' => '',
        ], $overrides);
    }

    public function test_super_admin_dari_excel_ditolak(): void
    {
        $hasil = $this->impor($this->dataValid(['jabatan_utama' => 'Super Admin']));

        $this->assertNull($hasil, 'Baris dengan Super Admin harus dilewati (return null)');
        $this->assertNull(User::where('email', 'uji-import@gmail.com')->first(), 'User TIDAK boleh dibuat');
    }

    public function test_role_kategori_salah_ditolak(): void
    {
        // 'Staf TU' ada di DB tapi kategori tendik — tidak boleh masuk kolom jabatan_utama (utama)
        $hasil = $this->impor($this->dataValid(['jabatan_utama' => 'Staf TU']));
        $this->assertNull($hasil);
        $this->assertNull(User::where('email', 'uji-import@gmail.com')->first());
    }

    public function test_peran_tendik_valid_masih_diterima(): void
    {
        $hasil = $this->impor($this->dataValid([
            'jabatan_utama' => '',
            'peran_tambahan_tendik_opsional' => 'Staf TU',
        ]));

        $user = User::where('email', 'uji-import@gmail.com')->first();
        $this->assertNotNull($user, 'Baris dengan role tendik valid harus terimport');
        $this->assertTrue($user->hasRole('Staf TU'));
        $this->assertFalse($user->hasRole('Super Admin'));
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'nama_lengkap' => 'User Uji Import']);
    }

    public function test_role_valid_tetap_lolos_dan_terimport(): void
    {
        $hasil = $this->impor($this->dataValid(['jabatan_utama' => 'Dosen']));

        $user = User::where('email', 'uji-import@gmail.com')->first();
        $this->assertNotNull($hasil ?: $user, 'Import sukses');
        $this->assertNotNull($user, 'User dibuat');
        $this->assertTrue($user->hasRole('Dosen'));
        $this->assertFalse($user->hasRole('Super Admin'));
        // password di-hash
        $this->assertNotSame('rahasia123', $user->password);
    }

    public function test_baris_valid_lain_di_file_sama_tetap_terimport_setelah_baris_invalid(): void
    {
        // Baris 2 (invalid: Super Admin) lalu baris 3 (valid: Dosen)
        $this->impor($this->dataValid(['jabatan_utama' => 'Super Admin']));
        $this->impor($this->dataValid([
            'email_kampus' => 'dosen-valid@gmail.com',
            'nip' => '199000000000000002',
            'jabatan_utama' => 'Dosen',
        ]));

        $this->assertNull(User::where('email', 'uji-import@gmail.com')->first(), 'Baris invalid tetap dilewati');
        $validUser = User::where('email', 'dosen-valid@gmail.com')->first();
        $this->assertNotNull($validUser, 'Baris valid di file yang sama tetap terimport');
        $this->assertTrue($validUser->hasRole('Dosen'));
    }

    public function test_role_invalid_dicatat_log_dengan_nama_dan_baris(): void
    {
        Log::spy();

        $this->impor($this->dataValid(['jabatan_utama' => 'Super Admin']));

        // 2 warning: 1 utk role invalid (Super Admin), 1 utk baris dilewati.
        Log::shouldHaveReceived('warning');
        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message) {
                return str_contains($message, 'Super Admin') && str_contains($message, 'baris 1');
            });
    }

    // ── Tugas 26 lanjutan: pesan peringatan TERLIHAT user ───────────────────

    public function test_warning_terkumpul_di_property_dengan_nama_dan_baris(): void
    {
        $import = new UsersImport;

        // Baris 2: jabatan_utama 'Super Admin' (invalid) → baris dilewati.
        $import->onRow($this->buatRow($this->dataValid(['jabatan_utama' => 'Super Admin'])));
        // Baris 3: role tendik di kolom peran (invalid) + jabatan_utama Dosen (valid).
        $import->onRow($this->buatRow($this->dataValid([
            'email_kampus' => 'dua@gmail.com',
            'nip' => '199000000000000003',
            'peran_tambahan_tendik_opsional' => 'Super Admin',
        ])));

        $this->assertNotEmpty($import->warnings, 'Property warnings harus terisi');
        $this->assertTrue(
            collect($import->warnings)->contains(fn ($w) => str_contains($w, 'Super Admin') && str_contains($w, 'Baris 1')),
            'Warning harus memuat nama role + nomor baris: ' . json_encode($import->warnings)
        );
        // Kolom peran Super Admin tidak valid → di-warning meski baris tetap terimport (Dosen valid).
        $this->assertTrue(
            collect($import->warnings)->contains(fn ($w) => str_contains($w, 'peran_tambahan_tendik') && str_contains($w, 'Super Admin')),
            'Role invalid di kolom peran juga di-warning: ' . json_encode($import->warnings)
        );
    }

    public function test_import_http_membawa_flash_import_warnings_dan_baris_valid_terimport(): void
    {
        $admin = $this->buatAdmin();

        // CSV dengan 2 baris: baris 2 invalid (Super Admin), baris 3 valid (Dosen).
        $csv = implode("\n", [
            'Email Kampus,NIP,Nama Lengkap & Gelar,Password,Jabatan Utama',
            'invalid@gmail.com,199000000000000009,Baris Invalid,rahasia123,Super Admin',
            'valid@gmail.com,199000000000000010,Baris Valid,rahasia123,Dosen',
        ]);
        $file = \Illuminate\Http\Testing\File::fake()->createWithContent('uji.csv', $csv);

        $response = $this->actingAs($admin)
            ->post(route('superadmin.users.import'), ['file' => $file]);

        $response->assertRedirect(route('superadmin.users.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_warnings');
        $warnings = session('import_warnings');
        // CSV: heading = baris Excel 1 → baris data pertama = baris Excel 2.
        // getIndex() mengembalikan nomor baris sheet, jadi warning menyebut 'Baris 2'.
        $this->assertTrue(
            collect($warnings)->contains(fn ($w) => str_contains($w, 'Super Admin') && str_contains($w, 'Baris 2')),
            'Flash import_warnings harus memuat pesan baris + role: ' . json_encode($warnings)
        );

        $this->assertNull(User::where('email', 'invalid@gmail.com')->first(), 'Baris invalid tidak terimport');
        $validUser = User::where('email', 'valid@gmail.com')->first();
        $this->assertNotNull($validUser, 'Baris valid tetap terimport');
        $this->assertTrue($validUser->hasRole('Dosen'));
    }

    public function test_import_http_tanpa_warning_tidak_membawa_flash(): void
    {
        $admin = $this->buatAdmin();

        $csv = implode("\n", [
            'Email Kampus,NIP,Nama Lengkap & Gelar,Password,Jabatan Utama',
            'valid2@gmail.com,199000000000000011,Semua Valid,rahasia123,Dosen',
        ]);
        $file = \Illuminate\Http\Testing\File::fake()->createWithContent('uji.csv', $csv);

        $response = $this->actingAs($admin)
            ->post(route('superadmin.users.import'), ['file' => $file]);

        $response->assertRedirect(route('superadmin.users.index'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('import_warnings');
    }

    // ── Tugas 26 lanjutan: akses import non-superadmin ditolak ─────────────

    public function test_import_ditolak_untuk_non_superadmin(): void
    {
        // Route sudah dijaga middleware role:Super Admin (web.php:182,198) —
        // test memastikan perilaku; TANPA perubahan route/controller.
        $pegawai = User::create(['email' => 'pegawai@gmail.com', 'password' => bcrypt('password')]);
        UserProfile::create([
            'user_id' => $pegawai->id,
            'nama_lengkap' => 'Pegawai Uji',
            'nip' => '199000000000000099',
            'jabatan_pekerjaan' => 'Staf',
        ]);
        $pegawai->assignRole('Staf TU');

        $file = \Illuminate\Http\Testing\File::fake()->createWithContent('uji.csv', 'Email Kampus,NIP\nx@y.com,1\n');

        // GET modal/halaman index → redirect (bukan 200).
        $this->actingAs($pegawai)->get(route('superadmin.users.index'))
            ->assertStatus(302);
        // POST import → redirect, dan TIDAK ada user terimport.
        $this->actingAs($pegawai)
            ->post(route('superadmin.users.import'), ['file' => $file])
            ->assertStatus(302);

        $this->assertNull(User::where('email', 'x@y.com')->first(), 'Import non-superadmin tidak boleh dieksekusi');
    }

    public function test_import_ditolak_untuk_tamu(): void
    {
        $file = \Illuminate\Http\Testing\File::fake()->createWithContent('uji.csv', 'Email Kampus,NIP\nx@y.com,1\n');

        $this->post(route('superadmin.users.import'), ['file' => $file])
            ->assertRedirect(route('login'));

        $this->assertNull(User::where('email', 'x@y.com')->first());
    }

    // ── Helper ──────────────────────────────────────────────────────────────

    /** Buat Row Excel asli (PhpSpreadsheet) tanpa memengaruhi rowIndex helper impor(). */
    private function buatRow(array $data): \Maatwebsite\Excel\Row
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $kolom = 'A';
        foreach (array_values($data) as $nilai) {
            $sheet->setCellValue($kolom++ . '1', $nilai);
        }
        $spreadsheetRow = new \PhpOffice\PhpSpreadsheet\Worksheet\Row($sheet, 1);
        $headingRow = array_combine(range(0, count($data) - 1), array_keys($data));
        $headerIsGrouped = array_fill(0, count($data), false);

        return new \Maatwebsite\Excel\Row($spreadsheetRow, $headingRow, $headerIsGrouped);
    }

    /** Admin Super Admin lengkap dengan profil (dipakai test HTTP import). */
    private function buatAdmin(): User
    {
        $admin = User::create(['email' => 'admin-import@gmail.com', 'password' => bcrypt('password')]);
        UserProfile::create([
            'user_id' => $admin->id,
            'nama_lengkap' => 'Admin Uji Import',
            'nip' => '198000000000000001',
            'jabatan_pekerjaan' => 'Admin',
        ]);
        $admin->assignRole('Super Admin');
        return $admin;
    }
}
