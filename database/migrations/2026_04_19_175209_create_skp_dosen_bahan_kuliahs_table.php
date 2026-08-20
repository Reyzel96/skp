<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen_bahan_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_skp_dosen_id')->constrained('penilaian_skp_dosen')->onDelete('cascade');

            // Semester: gasal / genap
            $table->string('semester');

            // Jenis: buku_ajar, diktat, modul, petunjuk_praktikum, model, alat_bantu, audio_visual, naskah_tutorial
            $table->string('jenis_bahan');

            // Jumlah item yang dibuat
            $table->integer('jumlah')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen_bahan_kuliahs');
    }
};
