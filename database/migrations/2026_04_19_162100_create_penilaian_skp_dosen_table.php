<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('penilaian_skp_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skp_dosen_id')->constrained('skp_dosen')->onDelete('cascade');

            // Kolom Realisasi (Diperbesar presisinya untuk keamanan perhitungan)
            $table->decimal('realisasi_ak', 8, 3)->nullable()->default(0);
            $table->decimal('realisasi_kuantitas', 10, 2)->nullable()->default(0);
            $table->decimal('realisasi_kualitas', 5, 2)->nullable()->default(0);
            $table->decimal('realisasi_waktu', 5, 2)->nullable()->default(0);
            $table->decimal('realisasi_biaya', 15, 2)->nullable()->default(0);

            // Kolom Hasil (Dihitung Otomatis oleh Sistem)
            $table->decimal('nilai_penghitungan', 8, 2)->nullable()->default(0);
            $table->decimal('nilai_capaian', 8, 2)->nullable()->default(0);

            $table->timestamps();

            // Mencegah duplikasi: 1 Dosen hanya punya 1 form realisasi per item kegiatan target
            $table->unique(['user_id', 'skp_dosen_id']);
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('penilaian_skp_dosen');

        Schema::enableForeignKeyConstraints();
    }
};
