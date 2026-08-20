<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_perilaku_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_header_id')->constrained('skp_headers')->onDelete('cascade');
            $table->string('aspek'); // Orientasi Pelayanan, Integritas, Komitmen, Disiplin, Kerjasama, Kepemimpinan
            $table->text('indikator');
            $table->decimal('bobot_persen', 5, 2);
            $table->decimal('nilai', 5, 2)->default(0);
            $table->boolean('is_realized')->default(false);
            $table->boolean('is_from_admin')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_perilaku_indicators');
    }
};
