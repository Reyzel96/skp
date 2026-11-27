<?php

namespace App\Console\Commands;

use App\Models\PangkatGolongan;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePangkatGolongan extends Command
{
    protected $signature = 'skp:migrate-pangkat-golongan {--dry-run : Tampilkan preview perubahan tanpa menuliskan ke database} {--commit : Benarkan dan laksanakan UPDATE ke database}';

    protected $description = 'Migrasi selektif user id=3,5,8 ke pangkat_golongan_id III/c (kode III/c). Hanya user dengan jabatan_fungsional Lektor yang cocok.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $commit = $this->option('commit');

        if (!$dryRun && !$commit) {
            $this->error('Gunakan --dry-run untuk preview, atau --commit untuk menyimpan ke database.');
            return self::FAILURE;
        }

        // Cari pangkat dengan kode III/c
        $pangkat = PangkatGolongan::where('kode_pangkat', 'III/c')->first();

        if (!$pangkat) {
            $this->error('Pangkat dengan kode III/c tidak ditemukan di tabel pangkat_golongans. Jalankan seeder dulu.');
            return self::FAILURE;
        }

        // User yang sesuai (id=3, id=5, id=8)
        $targetUserIds = [3, 5, 8];

        $profiles = UserProfile::whereIn('user_id', $targetUserIds)->get();

        if ($profiles->isEmpty()) {
            $this->info('Tidak ada user profile ditemukan untuk id=' . implode(',', $targetUserIds) . '.');
            return self::SUCCESS;
        }

        $results = [];

        foreach ($profiles as $profile) {
            $alreadySet = $profile->pangkat_golongan_id === $pangkat->id;

            $results[] = [
                'user_id' => $profile->user_id,
                'nama' => $profile->nama_lengkap ?? '-',
                'pangkat_golongan_lama' => $profile->getRawOriginal('pangkat_golongan') ?? '-',
                'pangkat_golongan_id_lama' => $profile->pangkat_golongan_id ?? '-',
                'pangkat_golongan_baru_id' => $pangkat->id,
                'kode_pangkat_baru' => $pangkat->kode_pangkat,
                'status' => $alreadySet ? 'SUDAH SESUAI (tidak perlu diubah)' : 'AKAN DIUPDATE',
            ];

            if ($commit && !$alreadySet) {
                $profile->pangkat_golongan_id = $pangkat->id;
                $profile->save();
            }
        }

        $header = [
            'User ID', 'Nama', 'Pangkat String Lama', 'ID Lama', 'ID Baru', 'Kode Baru', 'Status'
        ];

        $rows = array_map(fn($r) => [
            $r['user_id'],
            $r['nama'],
            $r['pangkat_golongan_lama'],
            $r['pangkat_golongan_id_lama'],
            $r['pangkat_golongan_baru_id'],
            $r['kode_pangkat_baru'],
            $r['status'],
        ], $results);

        if ($dryRun) {
            $this->info('[DRY-RUN] Preview migrasi pangkat golongan:');
        } else {
            $this->info('[COMMIT] Migrasi pangkat golongan berhasil disimpan:');
        }

        $this->table($header, $rows);

        // Summary
        $updated = count(array_filter($results, fn($r) => $r['status'] === 'AKAN DIUPDATE'));
        $already = count(array_filter($results, fn($r) => str_contains($r['status'], 'SUDAH')));

        $this->info("Total user ditemukan: " . count($results));
        $this->info("Akan diupdate: {$updated}");
        $this->info("Sudah sesuai (tidak diubah): {$already}");

        if ($dryRun) {
            $this->info("[DRY-RUN] Tidak ada data yang diubah. Jalankan dengan --commit untuk menyimpan.");
        }

        return self::SUCCESS;
    }
}
