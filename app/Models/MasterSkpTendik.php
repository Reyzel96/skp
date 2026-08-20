<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterSkpTendik extends Model
{
    use HasFactory;

    protected $table = 'master_skp_tendiks';

    protected $fillable = [
        'user_id',
        'skp_header_id',
        'role_name',
        'tupoksi',
        'jenis_aktivitas',
        'penilai_role',
        'kategori',
        'periode',
        'frekuensi_per_tahun',
        'durasi_menit',
        'jumlah_sdm',
        'status_approval',
        'approved_by',
        'catatan_approval',
        'approved_at',
    ];

    /**
     * Casting atribut agar Laravel paham tipe datanya saat diambil/disimpan
     */
    protected $casts = [
        // Otomatis mengubah JSON di DB menjadi Array PHP saat diambil
        // dan mengubah Array PHP menjadi JSON saat disimpan
        'kategori' => 'array',

        'frekuensi_per_tahun' => 'integer',
        'durasi_menit' => 'integer',
        'jumlah_sdm' => 'integer',
        'approved_at' => 'datetime',
        'skp_header_id' => 'integer',
    ];

    /**
     * Atribut Appends: Kolom virtual yang dihitung secara dinamis.
     * Ini mewakili field "Volume Kerja" Anda.
     */
    protected $appends = ['volume_kerja'];

    /**
     * Accessor untuk Volume Kerja (Frekuensi * Durasi * SDM)
     * Pemanggilan: $master->volume_kerja
     */
    public function getVolumeKerjaAttribute()
    {
        return $this->frekuensi_per_tahun * $this->durasi_menit * $this->jumlah_sdm;
    }

    public function user() { return $this->belongsTo(User::class); }

    public function header() { return $this->belongsTo(SkpHeader::class, 'skp_header_id'); }

    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
