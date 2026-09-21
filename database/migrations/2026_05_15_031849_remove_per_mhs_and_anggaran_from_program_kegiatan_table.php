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
            if (Schema::hasColumn('program_kegiatan', 'per_mhs')) {
                $table->dropColumn('per_mhs');
            }
            if (Schema::hasColumn('program_kegiatan', 'anggaran')) {
                $table->dropColumn('anggaran');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kegiatan', function (Blueprint $table) {
            if (!Schema::hasColumn('program_kegiatan', 'per_mhs')) {
                $table->decimal('per_mhs', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('program_kegiatan', 'anggaran')) {
                $table->decimal('anggaran', 15, 2)->default(0);
            }
        });
    }
};
