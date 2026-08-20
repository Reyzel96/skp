<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpKualitatifRumus extends Model
{
    use HasFactory;

    protected $table = 'skp_kualitatif_rumus';

    protected $fillable = [
        'jumlah_pegawai',
        'distribusi_nilai',
        'distribusi_persen',
        'hasil_kerja',
    ];

    protected $casts = [
        'distribusi_nilai' => 'array',
        'distribusi_persen' => 'array',
        'hasil_kerja' => 'array',
    ];
}
