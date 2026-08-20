<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen_pkls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_skp_dosen_id')->constrained('penilaian_skp_dosen')->onDelete('cascade');

            // Jenis Kegiatan: KKN, PKN, atau PKL
            $table->string('jenis_kegiatan');

            $table->integer('jml_mhs_genap')->default(0);
            $table->integer('jml_mhs_gasal')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen_pkls');
    }
};
