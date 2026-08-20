<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skp_kualitatif_realisasis', function (Blueprint $table) {
            // Menampung teks label lampiran yang diketik manual oleh dosen
            // (per baris realisasi / per sub-indikator). Bukan label master dari acuan.
            // NULL = belum diisi dosen -> fallback ke label_lampiran belajar lalu auto-detect.
            $table->string('label_realisasi')->nullable()->after('nilai_capaian');
        });
    }

    public function down(): void
    {
        Schema::table('skp_kualitatif_realisasis', function (Blueprint $table) {
            $table->dropColumn('label_realisasi');
        });
    }
};
