<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('skp_lampiran_sync_logs')) {
            Schema::create('skp_lampiran_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('skp_header_id')->constrained('skp_headers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->json('preview_json')->comment('Diff hasil recompute saat apply: add/remove list per parent');
                $table->timestamp('applied_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skp_lampiran_sync_logs');
    }
};
