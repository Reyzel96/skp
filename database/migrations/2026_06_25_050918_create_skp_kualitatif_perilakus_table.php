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
        Schema::create('skp_kualitatif_perilakus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_header_id')->constrained('skp_headers')->onDelete('cascade');
            $table->string('aspek');
            $table->text('indikator');
            $table->text('ekspektasi_pimpinan')->nullable();
            $table->text('umpan_balik')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skp_kualitatif_perilakus');
    }
};
