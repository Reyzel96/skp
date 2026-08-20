<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kegiatan_prodi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_kegiatan_id')->constrained('program_kegiatan')->onDelete('cascade');
            $table->unsignedBigInteger('prodi_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // User Kaprodi pengaju

            // Mapping Kolom Excel spesifik Prodi
            $table->string('nama_kegiatan_prodi')->nullable(); 
            $table->string('semester', 50)->nullable();
            $table->text('indikator')->nullable();
            $table->decimal('target_capaian', 15, 2)->nullable();
            $table->string('waktu_pelaksanaan', 255)->nullable();
            $table->string('tahun_anggaran', 20)->nullable();
            $table->string('tahun_akademik', 50)->nullable();
            
            $table->date('tanggal_implementasi')->nullable();
            $table->text('indikator_keberhasilan')->nullable();

            // Nominal Pengajuan & Saldo
            $table->decimal('anggaran_usulan_prodi', 15, 2)->default(0); 
            $table->decimal('anggaran_disetujui_prodi', 15, 2)->default(0); 
            $table->decimal('sisa_anggaran', 15, 2)->default(0); 

            // File Pendukung / Proposal
            $table->string('proposal_type', 20)->default('file');
            $table->longText('proposal')->nullable();

            // Status Persetujuan & Workflow
            $table->enum('status_persetujuan', [
                'pending',
                'disetujui_dekan',
                'disetujui_wadek',
                'disetujui_admin',
                'dicairkan',
                'selesai',
                'ditolak'
            ])->default('pending');
            $table->boolean('wadek_can_edit')->default(true);
            $table->text('catatan')->nullable();

            // Tracking Approver
            $table->foreignId('approved_by_dekan')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by_wadek')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by_admin')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kegiatan_prodi');
    }
};
