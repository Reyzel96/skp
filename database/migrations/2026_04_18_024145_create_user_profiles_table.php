<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('nama_lengkap');
            $table->string('nip')->unique();
            $table->string('pangkat_golongan')->nullable();
            $table->string('jabatan_pekerjaan')->nullable();
            $table->string('jabatan_fungsional')->nullable();
            $table->string('unit_kerja')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
