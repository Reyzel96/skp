<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skp_kualitatif_realisasis', function (Blueprint $table) {
            // Mengikat setiap baris realisasi utama ke sub-indikator (child) spesifik
            // yang dicentang "Lampiran"-nya di halaman Acuan SKP, bukan hanya ke parent.
            $table->unsignedBigInteger('acuan_child_id')->nullable()->after('target_id');
            $table->foreign('acuan_child_id')
                  ->references('id')
                  ->on('skp_kualitatif_acuan_children')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('skp_kualitatif_realisasis', function (Blueprint $table) {
            $table->dropForeign(['acuan_child_id']);
            $table->dropColumn('acuan_child_id');
        });
    }
};
