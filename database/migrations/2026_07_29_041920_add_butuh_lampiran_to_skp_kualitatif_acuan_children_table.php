<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('skp_kualitatif_acuan_children', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_acuan_children', function (Blueprint $table) {
                $table->boolean('butuh_lampiran')->default(false)->after('indikator_kinerja');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('skp_kualitatif_acuan_children', 'butuh_lampiran')) {
            Schema::table('skp_kualitatif_acuan_children', function (Blueprint $table) {
                $table->dropColumn('butuh_lampiran');
            });
        }
    }
};
