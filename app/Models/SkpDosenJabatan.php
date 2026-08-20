<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenJabatan extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_jabatans';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'kode_genap',
        'kode_gasal'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
