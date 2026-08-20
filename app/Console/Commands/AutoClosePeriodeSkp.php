<?php

namespace App\Console\Commands;

use App\Models\PeriodeSkp;
use Illuminate\Console\Command;

class AutoClosePeriodeSkp extends Command
{
    protected $signature = 'periode:auto-close {--dry-run : Tampilkan periode yang akan ditutup tanpa mengubah data}';

    protected $description = 'Menutup otomatis periode SKP yang masih aktif namun tanggal selesai-nya sudah lewat.';

    public function handle()
    {
        $now = now()->startOfDay();

        $affected = PeriodeSkp::query()
            ->where('status', 'aktif')
            ->where('selesai', '<', $now)
            ->get();

        if ($affected->isEmpty()) {
            $this->info('Tidak ada periode yang perlu ditutup otomatis.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');

        foreach ($affected as $periode) {
            $label = "#{$periode->id} {$periode->nama_periode} (selesai {$periode->selesai->format('Y-m-d')})";

            if ($dryRun) {
                $this->line("[DRY-RUN] Akan ditutup: {$label}");
            } else {
                $periode->update(['status' => 'selesai']);
                $this->line("Ditutup: {$label}");
            }
        }

        $this->info(
            ($dryRun ? '[DRY-RUN] ' : '')
            . "{$affected->count()} periode " . ($dryRun ? 'akan' : 'telah') . " ditutup otomatis."
        );

        return self::SUCCESS;
    }
}
