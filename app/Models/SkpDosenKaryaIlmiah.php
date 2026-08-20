<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenKaryaIlmiah extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_karya_ilmiahs';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'jenis_karya',
        'jumlah'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
