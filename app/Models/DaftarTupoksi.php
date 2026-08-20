<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaftarTupoksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'bidang',
        'tupoksi',
        'jenis_aktivitas',
        'kategori',
        'periode',
        'frekuensi_per_tahun',
        'durasi_menit',
        'jumlah_sdm',
        'volume_kerja',
    ];

    protected $casts = [
        'kategori' => 'array',
        'frekuensi_per_tahun' => 'integer',
        'durasi_menit' => 'integer',
        'jumlah_sdm' => 'integer',
        'volume_kerja' => 'integer',
    ];
}
