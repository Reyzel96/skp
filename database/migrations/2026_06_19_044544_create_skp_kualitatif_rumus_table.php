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
        Schema::create('skp_kualitatif_rumus', function (Blueprint $table) {
            $table->id();
            $table->integer('jumlah_pegawai')->default(1000);
            $table->json('distribusi_nilai')->nullable();
            $table->json('distribusi_persen')->nullable();
            $table->json('hasil_kerja')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skp_kualitatif_rumus');
    }
};
