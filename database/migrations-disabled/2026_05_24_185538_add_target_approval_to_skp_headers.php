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
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->boolean('is_target_approved_penilai')->default(false)->after('atasan_pejabat_penilai_id');
            $table->boolean('is_target_approved_atasan')->default(false)->after('is_target_approved_penilai');
            $table->timestamp('target_approved_at')->nullable()->after('is_target_approved_atasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->dropColumn(['is_target_approved_penilai', 'is_target_approved_atasan', 'target_approved_at']);
        });
    }
};
