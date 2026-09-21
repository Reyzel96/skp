<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->string('label_triwulan')->nullable()
                ->comment('Override label Triwulan (kosong = auto dari bulan mulai)');
            $table->string('label_periode_penilaian')->nullable()
                ->comment('Override teks Periode Penilaian (kosong = auto dari mulai/selesai)');
        });
    }

    public function down(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->dropColumn(['label_triwulan', 'label_periode_penilaian']);
        });
    }
};
