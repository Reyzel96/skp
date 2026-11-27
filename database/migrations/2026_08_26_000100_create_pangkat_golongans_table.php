<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pangkat_golongans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenjang_jabatan_id')->constrained()->cascadeOnDelete();
            $table->string('kode_pangkat')->unique();
            $table->string('nama_pangkat');
            $table->string('golongan');
            $table->unsignedInteger('angka_kredit_minimal')->default(0);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pangkat_golongans');
    }
};
