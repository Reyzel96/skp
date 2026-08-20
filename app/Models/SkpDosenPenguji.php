<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenPenguji extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_pengujis';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'semester',
        'jml_mhs_ketua',
        'jml_mhs_anggota'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
