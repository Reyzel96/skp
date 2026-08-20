<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodeAnggaran extends Model
{
    use HasFactory;

    protected $table = 'periode_anggaran';

    protected $fillable = [
        'tahun_anggaran',
        'tahun_akademik',
        'tanggal_mulai',
        'tanggal_selesai',
        'status'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];
}
