<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->nullable()->constrained('periode_anggaran')->nullOnDelete();
            $table->unsignedBigInteger('fakultas_id')->nullable();

            $table->string('unit_kerja');
            $table->string('sasaran')->nullable();
            $table->string('nama_program');
            $table->string('jenis_kegiatan')->nullable();
            $table->string('nama_kegiatan');
            $table->string('sub_kegiatan')->nullable();

            $table->decimal('per_mhs', 15, 2)->nullable();
            $table->decimal('anggaran_per_prodi', 15, 2)->default(0);
            $table->decimal('anggaran_pagu', 15, 2)->default(0);
            $table->decimal('per_prodi', 15, 2)->nullable();

            // ✅ TAMBAHAN: Kolom untuk Fitur Custom Alokasi Prodi
            $table->enum('tipe_alokasi', ['Rata', 'Custom'])->default('Rata');
            $table->json('alokasi_custom')->nullable();

            $table->string('semester')->nullable();
            $table->text('indikator')->nullable();
            $table->string('target_capaian')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            $table->string('tahun_anggaran')->nullable();
            $table->string('tahun_akademik')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kegiatan');
    }
};
