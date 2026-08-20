<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daftar_tupoksis', function (Blueprint $table) {
            $table->id();
            $table->string('bidang')->index();
            $table->text('tupoksi');
            $table->text('jenis_aktivitas');
            $table->json('kategori')->nullable();
            $table->string('periode')->default('Bulanan');
            $table->integer('frekuensi_per_tahun')->default(1);
            $table->integer('durasi_menit')->default(0);
            $table->integer('jumlah_sdm')->default(1);
            $table->integer('volume_kerja')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daftar_tupoksis');
    }
};
