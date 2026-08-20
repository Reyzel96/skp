<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenAbdimas extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_abdimas';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'kategori',
        'kegiatan',
        'jumlah'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
