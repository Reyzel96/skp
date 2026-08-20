<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_skp_tendiks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skp_header_id')->nullable()->constrained('skp_headers')->onDelete('cascade');
            $table->string('role_name')->index();
            $table->text('tupoksi')->comment('Tugas Pokok & Fungsi Utama');
            $table->text('jenis_aktivitas')->comment('Detail/Rincian Kegiatan');
            $table->string('penilai_role')->nullable()->comment('Role atasan yang menilai/menyetujui tugas ini');
            $table->json('kategori')->nullable();
            $table->enum('periode', ['harian', 'mingguan', 'bulanan', 'triwulan', 'semester', 'tahunan', 'kegiatan'])->default('bulanan');
            $table->integer('frekuensi_per_tahun')->default(1);
            $table->integer('durasi_menit')->default(0)->comment('Durasi pengerjaan per satu kali aktivitas');
            $table->integer('jumlah_sdm')->default(1)->comment('Jumlah ideal orang yang mengerjakan');
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('catatan_approval')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_skp_tendiks');
    }
};
