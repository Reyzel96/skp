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
        Schema::create('skp_kualitatif_realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_header_id')->constrained('skp_headers')->onDelete('cascade');
            
            // This references the ID of either skp_kualitatifs or skp_kualitatif_tambahans 
            // depending on the type
            $table->unsignedBigInteger('target_id'); 
            $table->string('target_type'); // e.g., 'utama', 'tambahan', 'perilaku'
            
            $table->text('realisasi')->nullable();
            $table->string('bukti_dukung_nama')->nullable();
            $table->string('bukti_dukung_url')->nullable();
            
            $table->text('umpan_balik_penilai')->nullable();
            $table->integer('nilai_capaian')->nullable(); // e.g., 1-5 or 1-100
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skp_kualitatif_realisasis');
    }
};
