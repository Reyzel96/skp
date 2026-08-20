<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('skp_kualitatifs', 'hasil_kerja')) {
            Schema::table('skp_kualitatifs', function (Blueprint $table) {
                $table->string('hasil_kerja')->nullable()->after('realisasi');
            });
        }

        if (!Schema::hasColumn('skp_kualitatif_tambahans', 'hasil_kerja')) {
            Schema::table('skp_kualitatif_tambahans', function (Blueprint $table) {
                $table->string('hasil_kerja')->nullable()->after('rencana_hasil_kerja');
            });
        }
        if (!Schema::hasColumn('skp_kualitatif_tambahans', 'umpan_balik')) {
            Schema::table('skp_kualitatif_tambahans', function (Blueprint $table) {
                $table->text('umpan_balik')->nullable();
            });
        }
        if (!Schema::hasColumn('skp_kualitatif_tambahans', 'realisasi')) {
            Schema::table('skp_kualitatif_tambahans', function (Blueprint $table) {
                $table->text('realisasi')->nullable();
            });
        }

        if (!Schema::hasColumn('skp_kualitatif_perilakus', 'hasil_kerja')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->string('hasil_kerja')->nullable()->after('ekspektasi_pimpinan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('skp_kualitatifs', 'hasil_kerja')) {
            Schema::table('skp_kualitatifs', function (Blueprint $table) {
                $table->dropColumn('hasil_kerja');
            });
        }

        if (Schema::hasColumn('skp_kualitatif_tambahans', 'hasil_kerja')) {
            Schema::table('skp_kualitatif_tambahans', function (Blueprint $table) {
                $table->dropColumn(['hasil_kerja', 'umpan_balik', 'realisasi']);
            });
        }

        if (Schema::hasColumn('skp_kualitatif_perilakus', 'hasil_kerja')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->dropColumn('hasil_kerja');
            });
        }
    }
};
