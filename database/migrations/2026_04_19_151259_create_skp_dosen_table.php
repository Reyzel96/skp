<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skp_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skp_header_id')->nullable()->constrained('skp_headers')->onDelete('cascade');
            $table->foreignId('periode_skp_id')->constrained('periode_skp')->onDelete('cascade');

            $table->enum('kategori', [
                'Pelaksanaan Pendidikan',
                'Pelaksanaan Penelitian',
                'Pelaksanaan Pengabdian Kepada Masyarakat',
                'Menduduki Jabatan Di Perguruan Tinggi',
                'Unsur Penunjang'
            ]);

            $table->text('kegiatan');
            $table->string('jenis')->nullable();

            // TAMBAHAN: Kolom JSON untuk menyimpan Rincian Matkul, dll.
            $table->json('detail_kegiatan')->nullable();

            $table->string('kegiatan_hash', 191)->index();
            $table->decimal('ak', 5, 2)->nullable()->default(0);
            $table->decimal('kuantitas_output', 10, 2)->nullable()->default(0);
            $table->decimal('kualitas_mutu', 5, 2)->nullable()->default(100);
            $table->decimal('waktu', 5, 2)->nullable()->default(0);
            $table->decimal('biaya', 15, 2)->nullable()->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skp_dosen');
    }
};
