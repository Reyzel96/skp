<?php

namespace Database\Seeders;

use App\Models\PeriodeSkp;
use Illuminate\Database\Seeder;

class PeriodeSkpSeeder extends Seeder
{
    public function run()
    {
        PeriodeSkp::create([
            'tahun' => 2025,
            'tipe' => 'semester',
            'semester' => 'genap',
            'semester_genap' => '2024/2025',
            'mulai' => '2025-01-01',
            'selesai' => '2025-06-30',
            'status' => 'aktif'
        ]);

        PeriodeSkp::create([
            'tahun' => 2025,
            'tipe' => 'semester',
            'semester' => 'ganjil',
            'semester_gasal' => '2025/2026',
            'mulai' => '2025-08-01',
            'selesai' => '2026-01-31',
            'status' => 'draft'
        ]);
    }
}
