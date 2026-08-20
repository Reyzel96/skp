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
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->integer('bulan_akhir')->nullable()->after('bulan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->dropColumn('bulan_akhir');
        });
    }
};
