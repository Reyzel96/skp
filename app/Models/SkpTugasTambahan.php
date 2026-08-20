<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpTugasTambahan extends Model
{
    use HasFactory;

    protected $table = 'skp_tugas_tambahan';

    protected $fillable = [
        'user_id',
        'periode_skp_id',
        'skp_header_id',
        'role_name',
        'tugas_tambahan',
        'detail_tupoksi',
        'kuantitas',
        'satuan',
        'kualitas',
        'waktu',
        'biaya',
        'jenis',
        'kegiatan',
        'ak',
        'ak_realisasi',
        'realisasi_kuantitas',
        'realisasi_kualitas',
        'realisasi_waktu',
        'realisasi_biaya',
        'realisasi_kegiatan',
        'realisasi_bukti',
        'penghitungan',
        'nilai_capaian',
        'status_approval'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeSkp::class, 'periode_skp_id');
    }

    public function header()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
}
