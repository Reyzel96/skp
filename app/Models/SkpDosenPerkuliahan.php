<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpDosenPerkuliahan extends Model
{
    use HasFactory;
    protected $table = 'skp_dosen_perkuliahans';
    protected $fillable = [
            'penilaian_skp_dosen_id',
            'semester',
            'nama_mata_kuliah',
            'sks_mata_kuliah',
            'jumlah_kelas',
            'jumlah_dosen_pengampu',
            'total_sks_riel',
            'bukti_dokumen'
    ];

    public function penilaian()
    {
        return $this->belongsTo(PenilaianSkpDosen::class, 'penilaian_skp_dosen_id');
    }
}
