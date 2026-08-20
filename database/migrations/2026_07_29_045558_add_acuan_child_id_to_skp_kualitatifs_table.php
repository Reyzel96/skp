<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skp_kualitatifs', function (Blueprint $table) {
            $table->unsignedBigInteger('acuan_parent_id')->nullable()->after('indikator_target');
            $table->foreign('acuan_parent_id')
                  ->references('id')
                  ->on('skp_kualitatif_acuan_parents')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('skp_kualitatifs', function (Blueprint $table) {
            $table->dropForeign(['acuan_parent_id']);
            $table->dropColumn('acuan_parent_id');
        });
    }
};