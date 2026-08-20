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
        Schema::table('master_skp_tendiks', function (Blueprint $table) {
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])->default('pending')->after('jumlah_sdm');
            $table->foreignId('approved_by')->nullable()->after('status_approval')->constrained('users')->onDelete('set null');
            $table->text('catatan_approval')->nullable()->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('catatan_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_skp_tendiks', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status_approval', 'approved_by', 'catatan_approval', 'approved_at']);
        });
    }
};
