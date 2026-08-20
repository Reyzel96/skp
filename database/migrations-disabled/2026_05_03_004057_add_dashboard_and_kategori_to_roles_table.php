<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Rute default ke dashboard pegawai jika tidak diatur
            $table->string('dashboard_route')->default('pegawai.dashboard')->after('name');
            // Untuk mengelompokkan tampilan di form Super Admin
            $table->enum('kategori', ['utama', 'tendik'])->default('tendik')->after('dashboard_route');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['dashboard_route', 'kategori']);
        });
    }
};
