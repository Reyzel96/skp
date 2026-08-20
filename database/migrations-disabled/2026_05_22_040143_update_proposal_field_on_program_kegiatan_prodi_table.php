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
        Schema::table('program_kegiatan_prodi', function (Blueprint $table) {
            $table->string('proposal_type', 20)->default('file')->after('sisa_anggaran');
            $table->longText('proposal')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kegiatan_prodi', function (Blueprint $table) {
            $table->string('proposal', 255)->nullable()->change();
            $table->dropColumn('proposal_type');
        });
    }
};
