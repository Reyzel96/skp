<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kegiatan_prodi_id')->constrained('program_kegiatan_prodi')->onDelete('cascade');

            // Detail Pengeluaran (Rincian per Nota/Kuitansi)
            $table->string('item_pengeluaran');
            $table->decimal('nominal_penggunaan', 15, 2);
            $table->date('tanggal_kuitansi');
            $table->text('keterangan')->nullable();

            // Bukti Fisik
            $table->string('bukti_pengeluaran')->nullable(); // File scan nota/kuitansi

            // Workflow Verifikasi LPJ oleh Fakultas
            $table->enum('status_verifikasi', ['pending', 'diverifikasi', 'ditolak'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null'); // SPI / Keuangan Fakultas
            $table->text('catatan_verifikasi')->nullable(); // Alasan jika ditolak/revisi
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpj');
    }
};
