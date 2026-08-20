<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('penilaian_skp_tendik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->foreignId('skp_tendik_id')->constrained('skp_tendik')->onDelete('cascade');

            $table->decimal('realisasi_ak', 8, 3)->nullable()->default(0);
            $table->integer('realisasi_kuantitas')->nullable()->default(0);
            $table->decimal('realisasi_kualitas', 5, 2)->nullable()->default(0);
            $table->integer('realisasi_waktu')->nullable()->default(0);
            $table->decimal('realisasi_biaya', 15, 2)->nullable()->default(0);
            $table->text('bukti')->nullable();

            $table->decimal('nilai_penghitungan', 8, 2)->nullable()->default(0);
            $table->decimal('nilai_capaian', 8, 2)->nullable()->default(0);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('penilaian_skp_tendik');
    }
};
