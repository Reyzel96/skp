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
        Schema::table('daftar_tupoksis', function (Blueprint $table) {
            $table->integer('jumlah_sdm')->default(1)->after('durasi_menit');
            $table->integer('volume_kerja')->default(0)->after('jumlah_sdm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daftar_tupoksis', function (Blueprint $table) {
            $table->dropColumn(['jumlah_sdm', 'volume_kerja']);
        });
    }
};
