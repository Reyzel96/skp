<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('skp_kualitatif_acuans');

        Schema::create('skp_kualitatif_acuan_parents', function (Blueprint $table) {
            $table->id();
            $table->string('kategori')->comment('utama, tambahan, atau perilaku');
            $table->integer('urutan')->default(0);
            $table->text('sasaran_kinerja')->nullable();
            $table->timestamps();
        });

        Schema::create('skp_kualitatif_acuan_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('skp_kualitatif_acuan_parents')->onDelete('cascade');
            $table->integer('urutan')->default(0);
            $table->text('indikator_kinerja')->nullable();
            $table->text('definisi_operasional')->nullable();
            $table->text('bukti_utama')->nullable();
            $table->text('target_ekspektasi')->nullable();
            $table->text('acuan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skp_kualitatif_acuan_children');
        Schema::dropIfExists('skp_kualitatif_acuan_parents');
        Schema::create('skp_kualitatif_acuans', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->timestamps();
        });
    }
};
