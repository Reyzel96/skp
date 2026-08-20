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

class UsersImport implements OnEachRow, WithHeadingRow
{
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

        $roleNames = array_map('trim', explode(',', $rolesString));
        $existingRoles = Role::whereIn('name', $roleNames)->pluck('name')->toArray();

        if (count($existingRoles) === 0) {
            // Jika tidak ada satupun role yang cocok dengan database, skip baris ini
            return null;
        }

        // 3. PROSES SIMPAN (Hanya jika lolos validasi di atas)
        DB::transaction(function () use ($email, $nip, $namaLengkap, $namaLogin, $password, $existingRoles, $jabatanPekerjaan, $pangkatGolongan, $jabatanFungsional, $unitKerja, $data) {
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
                $jabatanPekerjaan = $existingRoles[0];
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

            // D. Sync Roles yang valid saja
            $user->syncRoles($existingRoles);
        });
    }
}