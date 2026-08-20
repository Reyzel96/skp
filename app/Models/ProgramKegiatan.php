<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKegiatan extends Model
{
    use HasFactory;

    protected $table = 'program_kegiatan';

    protected $fillable = [
        'periode_id', 'fakultas_id', 'unit_kerja', 'sasaran', 'nama_program',
        'jenis_kegiatan', 'nama_kegiatan', 'sub_kegiatan',
        'per_mhs', 'anggaran_per_prodi', 'anggaran_pagu', 'per_prodi',
        'tipe_alokasi', 'alokasi_custom',
        'semester', 'indikator', 'target_capaian', 'waktu_pelaksanaan',
        'tahun_anggaran', 'tahun_akademik', 'created_by'
    ];

    public function periode() {
        return $this->belongsTo(PeriodeAnggaran::class, 'periode_id');
    }

    protected $casts = [
        'per_mhs'               => 'decimal:2',
        'anggaran_per_prodi'    => 'decimal:2',
        'anggaran_pagu'         => 'decimal:2',
        'per_prodi'             => 'decimal:2',
        'alokasi_custom'        => 'array', // ✅ Cast JSON ke Array PHP
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function programKegiatanProdi() {
        return $this->hasMany(ProgramKegiatanProdi::class, 'program_kegiatan_id');
    }
}
