<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenBimbing extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_bimbings';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'kategori',
        'semester',
        'jml_mhs_utama',
        'jml_mhs_penyerta'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
