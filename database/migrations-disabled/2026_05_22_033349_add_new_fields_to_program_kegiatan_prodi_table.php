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
        Schema::table('program_kegiatan_prodi', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->after('nama_kegiatan_prodi');
            $table->text('indikator')->nullable()->after('semester');
            $table->string('target_capaian', 255)->nullable()->after('indikator');
            $table->string('waktu_pelaksanaan', 255)->nullable()->after('target_capaian');
            $table->string('tahun_anggaran', 20)->nullable()->after('waktu_pelaksanaan');
            $table->string('tahun_akademik', 50)->nullable()->after('tahun_anggaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kegiatan_prodi', function (Blueprint $table) {
            $table->dropColumn(['semester', 'indikator', 'target_capaian', 'waktu_pelaksanaan', 'tahun_anggaran', 'tahun_akademik']);
        });
    }
};
