<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_tugas_tambahan', function (Blueprint $table) {
            $table->id();
            // Relasi ke User dan Periode
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skp_header_id')->nullable()->constrained('skp_headers')->onDelete('cascade');
            $table->foreignId('periode_skp_id')->constrained('periode_skp')->onDelete('cascade');

            // Isi form tugas tambahan
            $table->string('jenis');
            $table->string('kegiatan');
            $table->decimal('ak', 8, 3)->nullable()->default(0);
            $table->decimal('ak_realisasi', 8, 3)->nullable();
            $table->text('realisasi_kegiatan')->nullable();
            $table->string('realisasi_bukti')->nullable();

            // Kolom untuk menyimpan nilai hasil yang sudah dikonversi
            $table->decimal('penghitungan', 8, 3)->nullable()->default(0);
            $table->decimal('nilai_capaian', 8, 3)->nullable()->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_tugas_tambahan');
    }
};
