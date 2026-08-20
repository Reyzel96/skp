<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpKualitatifTugasTambahan extends Model
{
    protected $table = 'skp_kualitatif_tambahans';
    protected $fillable = [
        'skp_header_id',
        'rencana_hasil_kerja',
        'hasil_kerja',
        'umpan_balik',
        'realisasi',
    ];

    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
}
