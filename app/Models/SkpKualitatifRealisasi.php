<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpKualitatifRealisasi extends Model
{
    protected $table = 'skp_kualitatif_realisasis';
    
    protected $fillable = [
        'skp_header_id',
        'target_id',
        'acuan_child_id',
        'target_type',
        'realisasi',
        'label_realisasi',
        'bukti_dukung_nama',
        'bukti_dukung_url',
        'umpan_balik_penilai',
        'nilai_capaian'
    ];

    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }

    // Polymorphic-like method to get the target
    public function getTarget()
    {
        if ($this->target_type === 'utama' || $this->target_type === 'jabatan') {
            return SkpKualitatif::find($this->target_id);
        } elseif ($this->target_type === 'tambahan') {
            return SkpKualitatifTugasTambahan::find($this->target_id);
        } elseif ($this->target_type === 'perilaku') {
            return SkpKualitatifPerilaku::find($this->target_id);
        }
        return null;
    }
}
