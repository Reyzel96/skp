<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skp_kualitatif_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_header_id')->constrained('skp_headers')->onDelete('cascade');
            $table->string('jenis'); // 'dukungan_sumber_daya', 'skema_pertanggungjawaban', 'konsekuensi'
            $table->text('deskripsi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skp_kualitatif_lampirans');
    }
};
