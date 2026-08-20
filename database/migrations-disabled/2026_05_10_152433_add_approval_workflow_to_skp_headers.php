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
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('penilai_utama_id')->nullable()->after('pejabat_penilai_id');
            $table->unsignedBigInteger('penilai_akhir_id')->nullable()->after('penilai_utama_id');
            
            $table->string('status')->default('Draft')->change();

            $table->foreign('penilai_utama_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('penilai_akhir_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->dropForeign(['penilai_utama_id']);
            $table->dropForeign(['penilai_akhir_id']);
            $table->dropColumn(['penilai_utama_id', 'penilai_akhir_id']);
        });
    }
};
