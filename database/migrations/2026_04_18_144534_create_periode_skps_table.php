<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('periode_skp', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->enum('tipe', ['tahunan', 'semester', 'bulanan'])->default('tahunan');
            $table->enum('semester', ['ganjil', 'genap'])->nullable();
            $table->integer('bulan')->nullable();
            $table->integer('bulan_akhir')->nullable();
            $table->string('semester_genap')->nullable();
            $table->string('semester_gasal')->nullable();
            $table->date('mulai');
            $table->date('selesai');

            // Kolom Baru untuk Deadline
            $table->date('batas_pengisian')->nullable()->comment('Batas akhir pegawai input realisasi');
            $table->date('batas_penilaian')->nullable()->comment('Batas akhir atasan memberi nilai');

            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');

            // ✅ KUNCI DATABASE: Mencegah duplikasi data periode dengan isian yang persis sama
            $table->unique(['tahun', 'mulai', 'selesai'], 'periode_skp_unique');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('periode_skp');
    }
};
