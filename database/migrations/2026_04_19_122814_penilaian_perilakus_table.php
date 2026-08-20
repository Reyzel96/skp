<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('penilaian_perilaku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('periode_skp_id')->constrained('periode_skp')->onDelete('cascade');
            $table->decimal('orientasi_pelayanan', 5, 2)->nullable();
            $table->decimal('integritas', 5, 2)->nullable();
            $table->decimal('komitmen', 5, 2)->nullable();
            $table->decimal('disiplin', 5, 2)->nullable();
            $table->decimal('kerjasama', 5, 2)->nullable();
            $table->decimal('kepemimpinan', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('penilaian_perilaku');
    }
};
