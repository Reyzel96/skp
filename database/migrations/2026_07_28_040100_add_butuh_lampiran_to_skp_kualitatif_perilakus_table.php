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
        if (!Schema::hasColumn('skp_kualitatif_perilakus', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->boolean('butuh_lampiran')->default(false)->after('hasil_kerja');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('skp_kualitatif_perilakus', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_perilakus', function (Blueprint $table) {
                $table->dropColumn('butuh_lampiran');
            });
        }
    }
};
