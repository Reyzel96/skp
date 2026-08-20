<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenPkl extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_pkls';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'jenis_kegiatan',
        'jml_mhs_genap',
        'jml_mhs_gasal'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
