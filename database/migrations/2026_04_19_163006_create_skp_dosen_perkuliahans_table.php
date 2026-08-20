<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skp_dosen_perkuliahans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penilaian_skp_dosen_id')
                  ->constrained('penilaian_skp_dosen')
                  ->onDelete('cascade');

            $table->enum('semester', ['gasal', 'genap']);

            // HAPUS COMMENT DAN TAMBAHKAN KOLOM-KOLOM INI:
            $table->string('nama_mata_kuliah');
            $table->decimal('sks_mata_kuliah', 5, 2)->default(0);
            $table->integer('jumlah_kelas')->default(1);
            $table->integer('jumlah_dosen_pengampu')->default(1);
            $table->decimal('total_sks_riel', 5, 2)->default(0);
            $table->string('bukti_dokumen')->nullable(); // nullable karena berupa file path

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skp_dosen_perkuliahans');
    }
};
