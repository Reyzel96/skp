<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->unique(['periode_skp_id', 'user_id'], 'skp_headers_periode_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('skp_headers', function (Blueprint $table) {
            $table->dropUnique('skp_headers_periode_user_unique');
        });
    }
};
