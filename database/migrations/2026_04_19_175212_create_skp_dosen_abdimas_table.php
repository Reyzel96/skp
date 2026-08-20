<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen_abdimas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_skp_dosen_id')->constrained('penilaian_skp_dosen')->onDelete('cascade');

            // Kategori: A, B, atau C
            $table->char('kategori', 1);

            // Nama Kegiatan (Sesuai List)
            $table->string('kegiatan');

            // Jumlah
            $table->integer('jumlah')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen_abdimas');
    }
};
