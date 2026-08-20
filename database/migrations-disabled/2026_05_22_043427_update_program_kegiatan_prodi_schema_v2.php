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
            // Change target_capaian to decimal if it's not already
            $table->decimal('target_capaian', 15, 2)->nullable()->change();
            
            // Ensure other fields exist (they should, but just in case)
            if (!Schema::hasColumn('program_kegiatan_prodi', 'semester')) {
                $table->string('semester', 50)->nullable()->after('nama_kegiatan_prodi');
            }
            if (!Schema::hasColumn('program_kegiatan_prodi', 'indikator')) {
                $table->text('indikator')->nullable()->after('semester');
            }
            if (!Schema::hasColumn('program_kegiatan_prodi', 'waktu_pelaksanaan')) {
                $table->string('waktu_pelaksanaan', 255)->nullable()->after('target_capaian');
            }
            if (!Schema::hasColumn('program_kegiatan_prodi', 'tahun_anggaran')) {
                $table->string('tahun_anggaran', 20)->nullable()->after('waktu_pelaksanaan');
            }
            if (!Schema::hasColumn('program_kegiatan_prodi', 'tahun_akademik')) {
                $table->string('tahun_akademik', 50)->nullable()->after('tahun_anggaran');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kegiatan_prodi', function (Blueprint $table) {
            $table->string('target_capaian', 255)->nullable()->change();
        });
    }
};
