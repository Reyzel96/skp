<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('pangkat_golongan_id')->nullable()->after('pangkat_golongan');
            $table->foreign('pangkat_golongan_id')->references('id')->on('pangkat_golongans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['pangkat_golongan_id']);
            $table->dropColumn('pangkat_golongan_id');
        });
    }
};
