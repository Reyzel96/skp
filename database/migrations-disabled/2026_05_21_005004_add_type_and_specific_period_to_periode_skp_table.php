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
            $table->enum('tipe', ['tahunan', 'semester', 'bulanan'])->default('tahunan')->after('tahun');
            $table->enum('semester', ['ganjil', 'genap'])->nullable()->after('tipe');
            $table->integer('bulan')->nullable()->after('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'semester', 'bulan']);
        });
    }
};
