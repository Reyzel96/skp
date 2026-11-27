<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class UsersImport implements OnEachRow, WithHeadingRow
{
    /**
     * Tugas 26: pesan peringatan per baris yang dapat ditampilkan ke user
     * (bukan hanya Log). Controller usersImport membaca ini lalu flash ke session.
     * @var string[]
     */
    public array $warnings = [];

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        // Map data from slugified headers (Maatwebsite secara otomatis menjadikan header huruf kecil, spasi jadi underscore, simbol dibuang)
        $email = $data['email_kampus'] ?? $data['email_login'] ?? $data['email'] ?? null;
        $nip = $data['nip'] ?? null;
        $namaLengkap = $data['nama_lengkap_gelar'] ?? $data['nama_lengkap'] ?? null;
        $namaLogin = $data['nama_login'] ?? $namaLengkap;
        $password = $data['password'] ?? null;
        
        // Gabungkan Jabatan Utama dan Peran Tendik
        $rolesList = [];
        if (!empty($data['jabatan_utama'])) {
            $rolesList[] = $data['jabatan_utama'];
        }
        if (!empty($data['peran_tambahan_tendik_opsional'])) {
            $rolesList[] = $data['peran_tambahan_tendik_opsional'];
        }
        // Jika pakai format lama 'roles'
        if (!empty($data['roles'])) {
            $rolesList[] = $data['roles'];
        }
        
        $rolesString = implode(',', $rolesList);
        $jabatanPekerjaan = $data['jabatan_pekerjaan'] ?? null;
        $pangkatGolongan = $data['pangkat_golongan'] ?? null;
        $jabatanFungsional = $data['jabatan_fungsional'] ?? null;
        $unitKerja = $data['unit_kerja_penempatan'] ?? $data['unit_kerja'] ?? null;

        // 1. VALIDASI DATA DASAR
        if (empty($email) || empty($nip) || empty($namaLengkap)) {
            return null; // Skip baris jika data utama kosong
        }

        // 2. VALIDASI ROLE (HANYA IMPORT JIKA ROLE VALID)
        if (empty($rolesString)) {
            return null; // Skip jika kolom role kosong
        }

        // 2b. FILTER ROLE SEPERTI usersStore (Tugas 26 — tutup celah Super Admin via Excel).
        // Kolom dipisah sesuai semantiknya:
        // - 'jabatan_utama'              : harus role kategori 'utama' DAN bukan 'Super Admin'.
        // - 'peran_tambahan_tendik_...' / 'roles' (format lama): harus kategori 'tendik'.
        // Role tidak valid TIDAK di-assign; kegagalan dicatat via Log DAN dikumpulkan
        // di $warnings agar bisa ditampilkan ke user per baris (nomor baris Excel + nama role).
        // Tugas 26: getIndex() SUDAH merupakan nomor baris sheet (heading = baris 1,
        // data pertama = baris 2) — terbukti via test HTTP: baris data pertama CSV
        // menghasilkan getIndex()=2. Tanpa +1 agar nomor yang ditampilkan ke user
        // sama dengan nomor baris di file Excel.
        $excelRow = $row->getIndex();

        $filterKategori = function (array $names, string $kategori, string $labelKolom) use ($excelRow): array {
            $db = Role::whereIn('name', $names)->get()->keyBy('name');
            $valid = [];
            foreach ($names as $name) {
                $role = $db->get($name);
                $lolos = $role
                    && $role->kategori === $kategori
                    && !($kategori === 'utama' && $name === 'Super Admin');
                if ($lolos) {
                    $valid[] = $name;
                } else {
                    $pesan = "Baris {$excelRow}: role '{$name}' (kolom {$labelKolom}) tidak valid dan tidak diberikan — harus kategori '{$kategori}'" . ($kategori === 'utama' ? " selain Super Admin" : "") . ".";
                    Log::warning("Import user: baris {$excelRow} — kolom '{$labelKolom}': role '{$name}' tidak valid dan tidak di-assign (harus kategori '{$kategori}'" . ($kategori === 'utama' ? " selain Super Admin" : "") . ").");
                    $this->warnings[] = $pesan;
                }
            }
            return $valid;
        };

        $validRoles = [];
        if (!empty($data['jabatan_utama'])) {
            $validRoles = array_merge($validRoles, $filterKategori([trim($data['jabatan_utama'])], 'utama', 'jabatan_utama'));
        }
        $kolomTendik = [];
        if (!empty($data['peran_tambahan_tendik_opsional'])) {
            $kolomTendik[] = trim($data['peran_tambahan_tendik_opsional']);
        }
        // Jika pakai format lama 'roles' (peran tambahan)
        if (!empty($data['roles'])) {
            $kolomTendik[] = trim($data['roles']);
        }
        if ($kolomTendik) {
            $validRoles = array_merge($validRoles, $filterKategori($kolomTendik, 'tendik', 'peran_tambahan_tendik'));
        }

        if (count($validRoles) === 0) {
            // Jika tidak ada satupun role yang valid, skip baris ini
            $pesan = "Baris {$excelRow} dilewati: tidak ada role valid ({$rolesString}).";
            Log::warning("Import user: baris {$excelRow} dilewati — tidak ada role valid ({$rolesString}).");
            $this->warnings[] = $pesan;
            return null;
        }

        // 3. PROSES SIMPAN (Hanya jika lolos validasi di atas)
        DB::transaction(function () use ($email, $nip, $namaLengkap, $namaLogin, $password, $validRoles, $jabatanPekerjaan, $pangkatGolongan, $jabatanFungsional, $unitKerja, $data) {
            // A. Create or Update User
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'password' => !empty($password) ? Hash::make($password) : Hash::make($nip),
                    'email_verified_at' => now(),
                ]
            );

            // B. Tentukan Jabatan Pekerjaan jika kosong (ambil dari role pertama yang valid)
            if (empty($jabatanPekerjaan)) {
                $jabatanPekerjaan = $validRoles[0];
            }

            // C. Create or Update Profile
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $namaLengkap,
                    'nip' => $nip,
                    'pangkat_golongan' => $pangkatGolongan,
                    'jabatan_pekerjaan' => $jabatanPekerjaan,
                    'jabatan_fungsional' => $jabatanFungsional,
                    'unit_kerja' => $unitKerja,
                ]
            );

            // D. Sync Roles yang valid saja (kategori sesuai aturan usersStore)
            $user->syncRoles($validRoles);
        });
    }
}