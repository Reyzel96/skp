<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skp_kualitatif_acuan_children', function (Blueprint $table) {
            $table->string('label_lampiran')->nullable()->after('indikator_kinerja');
        });
    }

    public function down(): void
    {
        Schema::table('skp_kualitatif_acuan_children', function (Blueprint $table) {
            $table->dropColumn('label_lampiran');
        });
    }
};
