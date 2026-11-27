<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            // Hasil distribusi riil yang dibekukan saat periode ditutup.
            // Format JSON: ['total' => N, 'data' => [kategori => count], 'labels' => [...], 'order' => [...]]
            $table->json('distribusi_riil')->nullable()->after('capaian_kinerja_organisasi');
            $table->timestamp('distribusi_riil_dihitung_pada')->nullable()->after('distribusi_riil');
        });
    }

    public function down(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->dropColumn(['distribusi_riil', 'distribusi_riil_dihitung_pada']);
        });
    }
};
