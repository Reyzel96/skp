<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_headers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('periode_skp_id')
                ->constrained('periode_skp')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Pejabat Penilai
            $table->unsignedBigInteger('pejabat_penilai_id')->nullable();
            $table->unsignedBigInteger('atasan_pejabat_penilai_id')->nullable();
            $table->unsignedBigInteger('penilai_utama_id')->nullable();
            $table->unsignedBigInteger('penilai_akhir_id')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();

            // Kolom Persetujuan Target
            $table->boolean('is_target_approved_penilai')->default(false);
            $table->boolean('is_target_approved_atasan')->default(false);
            $table->timestamp('target_approved_at')->nullable();

            // ✅ LANGSUNG TAMBAHKAN DI SINI
            $table->string('status')->default('Draft');

            $table->text('catatan_penilai')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('pejabat_penilai_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('atasan_pejabat_penilai_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('penilai_utama_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('penilai_akhir_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Hapus Unique Constraint (Agar bisa >1 target per periode)
            // $table->unique(['periode_skp_id', 'user_id']);
            $table->index('periode_skp_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_headers');
    }
};