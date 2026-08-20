<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen_pengujis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_skp_dosen_id')->constrained('penilaian_skp_dosen')->onDelete('cascade');

            // Semester: Genap / Gasal
            $table->string('semester');

            // Input User
            $table->integer('jml_mhs_ketua')->default(0);
            $table->integer('jml_mhs_anggota')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen_pengujis');
    }
};
