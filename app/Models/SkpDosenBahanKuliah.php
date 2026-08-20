<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenBahanKuliah extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_bahan_kuliahs';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'semester',
        'jenis_bahan',
        'jumlah'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
