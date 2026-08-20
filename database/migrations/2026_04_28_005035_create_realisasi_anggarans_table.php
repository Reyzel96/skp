<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kegiatan_prodi_id')->constrained('program_kegiatan_prodi')->onDelete('cascade');

            $table->date('tanggal_pencairan');
            $table->decimal('nominal', 15, 2);
            $table->string('bukti_transfer')->nullable(); // File bukti transfer dari Fakultas ke Prodi

            $table->enum('status', ['pending', 'berhasil', 'gagal'])->default('berhasil');
            $table->text('keterangan')->nullable();

            // User dari bagian Keuangan/Admin Fakultas yang melakukan pencairan
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_anggaran');
    }
};
