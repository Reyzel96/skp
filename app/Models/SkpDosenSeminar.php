<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenSeminar extends Model
{
    use HasFactory;

    protected $table = 'skp_dosen_seminars';

    protected $fillable = [
        'penilaian_skp_dosen_id',
        'semester',
        'jumlah_mahasiswa'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
