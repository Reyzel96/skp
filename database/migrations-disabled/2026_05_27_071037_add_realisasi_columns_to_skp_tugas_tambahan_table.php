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
        Schema::table('skp_tugas_tambahan', function (Blueprint $table) {
            $table->decimal('ak_realisasi', 8, 3)->nullable()->after('ak');
            $table->text('realisasi_kegiatan')->nullable()->after('ak_realisasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp_tugas_tambahan', function (Blueprint $table) {
            $table->dropColumn(['ak_realisasi', 'realisasi_kegiatan']);
        });
    }
};
