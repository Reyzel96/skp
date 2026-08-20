<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosen extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen';

    protected $fillable = [
        'user_id',
        'skp_header_id',
        'periode_skp_id',
        'kategori',
        'kegiatan',
        'kegiatan_hash',
        'jenis',
        'detail_kegiatan', // TAMBAHAN
        'ak',
        'kuantitas_output',
        'kualitas_mutu',
        'waktu',
        'biaya'
    ];

    // TAMBAHAN CASTING JSON -> ARRAY
    protected $casts = [
        'detail_kegiatan' => 'array',
        'skp_header_id' => 'integer',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function header() { return $this->belongsTo(SkpHeader::class, 'skp_header_id'); }
    public function periodeSkp() { return $this->belongsTo(PeriodeSkp::class, 'periode_skp_id'); }
    public function penilaian() { return $this->hasOne(PenilaianSkpDosen::class, 'skp_dosen_id'); }
}
