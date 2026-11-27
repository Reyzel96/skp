<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('skp_kualitatifs', 'lampiran_snapshot')) {
            Schema::table('skp_kualitatifs', function (Blueprint $table) {
                $table->json('lampiran_snapshot')->nullable()->after('acuan_parent_id')
                    ->comment('Daftar kotak upload bukti (snapshot saat SKP dibuat/diedit/sinkron) [{child_id,label}]');
            });
        }

        // Backfill SKP existing (idempotent: hanya isi yang masih NULL).
        // Snapshot = children butuh_lampiran=true saat ini UNION children yang
        // sudah punya realisasi tersimpan (kotak berisi bukti tak boleh hilang).
        \App\Models\SkpKualitatif::where('jenis', 'utama')->whereNull('lampiran_snapshot')
            ->get()
            ->each(function ($row) {
                $row->update(['lampiran_snapshot' => $row->acuan_parent_id
                    ? \App\Models\SkpKualitatif::buildLampiranSnapshot($row->skp_header_id, $row->acuan_parent_id)
                    : []]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('skp_kualitatifs', 'lampiran_snapshot')) {
            Schema::table('skp_kualitatifs', function (Blueprint $table) {
                $table->dropColumn('lampiran_snapshot');
            });
        }
    }
};
