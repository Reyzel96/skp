<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenjangJabatan;
use App\Models\PangkatGolongan;

class PangkatGolonganSeeder extends Seeder
{
    public function run(): void
    {
        $jenjangData = [
            ['urutan' => 1, 'nama' => 'Asisten Ahli'],
            ['urutan' => 2, 'nama' => 'Lektor'],
            ['urutan' => 3, 'nama' => 'Lektor Kepala'],
            ['urutan' => 4, 'nama' => 'Guru Besar / Profesor'],
        ];

        foreach ($jenjangData as $data) {
            JenjangJabatan::create($data);
        }

        $pangkatData = [
            ['jenjang' => 'Asisten Ahli',          'kode_pangkat' => 'III/b', 'nama_pangkat' => 'Penata Muda Tk. I',   'golongan' => 'III', 'angka_kredit_minimal' => 150,  'urutan' => 1],
            ['jenjang' => 'Lektor',                'kode_pangkat' => 'III/c', 'nama_pangkat' => 'Penata',              'golongan' => 'III', 'angka_kredit_minimal' => 200,  'urutan' => 1],
            ['jenjang' => 'Lektor',                'kode_pangkat' => 'III/d', 'nama_pangkat' => 'Penata Tk. I',        'golongan' => 'III', 'angka_kredit_minimal' => 300,  'urutan' => 2],
            ['jenjang' => 'Lektor Kepala',         'kode_pangkat' => 'IV/a',  'nama_pangkat' => 'Pembina',             'golongan' => 'IV',  'angka_kredit_minimal' => 400,  'urutan' => 1],
            ['jenjang' => 'Lektor Kepala',         'kode_pangkat' => 'IV/b',  'nama_pangkat' => 'Pembina Tk. I',       'golongan' => 'IV',  'angka_kredit_minimal' => 550,  'urutan' => 2],
            ['jenjang' => 'Lektor Kepala',         'kode_pangkat' => 'IV/c',  'nama_pangkat' => 'Pembina Utama Muda',  'golongan' => 'IV',  'angka_kredit_minimal' => 700,  'urutan' => 3],
            ['jenjang' => 'Guru Besar / Profesor', 'kode_pangkat' => 'IV/d',  'nama_pangkat' => 'Pembina Utama Madya', 'golongan' => 'IV',  'angka_kredit_minimal' => 850,  'urutan' => 1],
            ['jenjang' => 'Guru Besar / Profesor', 'kode_pangkat' => 'IV/e',  'nama_pangkat' => 'Pembina Utama',       'golongan' => 'IV',  'angka_kredit_minimal' => 1050, 'urutan' => 2],
        ];

        foreach ($pangkatData as $data) {
            $jenjang = JenjangJabatan::where('nama', $data['jenjang'])->firstOrFail();
            PangkatGolongan::create([
                'jenjang_jabatan_id' => $jenjang->id,
                'kode_pangkat' => $data['kode_pangkat'],
                'nama_pangkat' => $data['nama_pangkat'],
                'golongan' => $data['golongan'],
                'angka_kredit_minimal' => $data['angka_kredit_minimal'],
                'urutan' => $data['urutan'],
            ]);
        }
    }
}
