<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpKualitatifPerilaku extends Model
{
    protected $table = 'skp_kualitatif_perilakus';
    protected $fillable = [
        'skp_header_id',
        'aspek',
        'indikator',
        'ekspektasi_pimpinan',
        'umpan_balik',
        'hasil_kerja',
    ];

    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
}
