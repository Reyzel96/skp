<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenKemahasiswaan extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_kemahasiswaans';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'nama_kegiatan',
        'jumlah_genap',
        'jumlah_gasal'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
