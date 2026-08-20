<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen_seminars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_skp_dosen_id')->constrained('penilaian_skp_dosen')->onDelete('cascade');

            // Ubah struktur agar sesuai format semester
            $table->enum('semester', ['gasal', 'genap']);
            $table->integer('jumlah_mahasiswa')->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen_seminars');
    }
};
