<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenPenunjang extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_penunjangs';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'nama_kegiatan',
        'ak'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
