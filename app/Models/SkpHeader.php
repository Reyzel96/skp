<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpHeader extends Model
{
    use HasFactory;

    protected $table = 'skp_headers';
    // Tambahkan status dan catatan_penilai
    protected $fillable = [
        'periode_skp_id', 
        'user_id', 
        'pejabat_penilai_id', 
        'atasan_pejabat_penilai_id', 
        'is_target_approved_penilai',
        'is_target_approved_atasan',
        'target_approved_at',
        'penilai_utama_id', 
        'penilai_akhir_id', 
        'status', 'capaian_kinerja_organisasi', 
        'catatan_penilai', 
        'approved_by_id'
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function pejabatPenilai() { return $this->belongsTo(User::class, 'pejabat_penilai_id'); }
    public function atasanPejabatPenilai() { return $this->belongsTo(User::class, 'atasan_pejabat_penilai_id'); }
    public function penilaiUtama() { return $this->belongsTo(User::class, 'penilai_utama_id'); }
    public function penilaiAkhir() { return $this->belongsTo(User::class, 'penilai_akhir_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by_id'); }
    public function periode() { return $this->belongsTo(PeriodeSkp::class, 'periode_skp_id'); }

    public function perilakuIndicators() { return $this->hasMany(SkpPerilakuIndicator::class, 'skp_header_id'); }

    public function masterActivities() { return $this->hasMany(MasterSkpTendik::class, 'skp_header_id'); }
    
    public function skpTendiks() { return $this->hasMany(SkpTendik::class, 'skp_header_id'); }

    public function skpDosens() { return $this->hasMany(SkpDosen::class, 'skp_header_id'); }

    public function skpKualitatifs() { return $this->hasMany(SkpKualitatif::class, 'skp_header_id'); }

    public function skpTugasTambahans() { return $this->hasMany(SkpTugasTambahan::class, 'skp_header_id'); }

    // Helper untuk cek status
    public function isApproved()
    {
        return $this->status === 'Disetujui';
    }
}
