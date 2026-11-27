<?php

namespace App\Console\Commands;

use App\Models\PeriodeSkp;
use Illuminate\Console\Command;

class AutoClosePeriodeSkp extends Command
{
    protected $signature = 'periode:auto-close {--dry-run : Tampilkan periode yang akan ditutup tanpa mengubah data}';

    protected $description = 'Menutup otomatis periode SKP aktif yang seluruh jendela waktunya (selesai, batas pengisian, batas penilaian) sudah lewat.';

    public function handle()
    {
        $now = now()->startOfDay();

        // Tutup hanya bila semua jendela waktu benar-benar lewat:
        // hari ini > MAX(tanggal selesai, batas pengisian, batas penilaian) — NULL diabaikan.
        $affected = PeriodeSkp::query()
            ->where('status', 'aktif')
            ->get()
            ->filter(function (PeriodeSkp $periode) use ($now) {
                $batasTutup = $periode->batasTutupTerakhir();

                return $batasTutup !== null && $now->gt($batasTutup);
            });

        if ($affected->isEmpty()) {
            $this->info('Tidak ada periode yang perlu ditutup otomatis.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');

        foreach ($affected as $periode) {
            $label = "#{$periode->id} {$periode->nama_periode} (batas tutup {$periode->batasTutupTerakhir()->format('Y-m-d')})";

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
