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
        Schema::table('program_kegiatan', function (Blueprint $table) {
            $table->dropColumn(['per_mhs', 'anggaran']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kegiatan', function (Blueprint $table) {
            $table->decimal('per_mhs', 15, 2)->nullable();
            $table->decimal('anggaran', 15, 2)->default(0);
        });
    }
};
