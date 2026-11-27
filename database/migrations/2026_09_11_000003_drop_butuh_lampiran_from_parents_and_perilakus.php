<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom parent-level & perilaku-level butuh_lampiran tidak pernah dibaca
        // (alur upload bukti memakai CHILD-level di skp_kualitatif_acuan_children).
        if (Schema::hasColumn('skp_kualitatif_acuan_parents', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_acuan_parents', function (Blueprint $table) {
                $table->dropColumn('butuh_lampiran');
            });
        }
        if (Schema::hasColumn('skp_kualitatif_perilakus', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->dropColumn('butuh_lampiran');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('skp_kualitatif_acuan_parents', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_acuan_parents', function (Blueprint $table) {
                $table->boolean('butuh_lampiran')->default(false)->after('sasaran_kinerja');
            });
        }
        if (!Schema::hasColumn('skp_kualitatif_perilakus', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->boolean('butuh_lampiran')->default(false)->after('ekspektasi_pimpinan');
            });
        }
    }
};
