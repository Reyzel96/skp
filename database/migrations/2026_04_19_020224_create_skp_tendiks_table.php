<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // TABEL INDUK (Per Tupoksi)
        Schema::create('skp_tendik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_skp_id')->constrained('periode_skp')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // PENTING: Tambahkan user_id agar tahu ini SKP milik siapa
            $table->foreignId('skp_header_id')->nullable()->constrained('skp_headers')->onDelete('cascade');
            $table->string('role_name')->index();
            $table->text('tupoksi'); // Induk Pekerjaan
            $table->timestamps();
        });

        // TABEL ANAK (Per Jenis Aktivitas dari Tupoksi di atas)
        Schema::create('skp_tendik_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_tendik_id')->constrained('skp_tendik')->onDelete('cascade');
            $table->text('jenis_aktivitas');
            $table->decimal('ak', 5, 2)->nullable()->default(0);
            $table->integer('kuantitas_output')->nullable()->default(0);
            $table->decimal('kualitas_mutu', 5, 2)->nullable()->default(100);
            $table->integer('waktu')->nullable()->default(0);
            $table->decimal('biaya', 15, 2)->nullable()->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_tendik_detail');
        Schema::dropIfExists('skp_tendik');
    }
};
