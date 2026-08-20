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
            $table->string('semester_genap')->nullable()->change();
            $table->string('semester_gasal')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_skp', function (Blueprint $table) {
            $table->string('semester_genap')->nullable(false)->change();
            $table->string('semester_gasal')->nullable(false)->change();
        });
    }
};
