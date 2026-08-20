<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SkpKualitatif extends Model
{
    protected $table = 'skp_kualitatifs';
    protected $fillable = [
        'skp_header_id',
        'jenis',
        'rencana_hasil_kerja_pimpinan',
        'rencana_hasil_kerja',
        'indikator_target',
        'realisasi',
        'umpan_balik',
        'hasil_kerja',
        'acuan_parent_id',
    ];
    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
    public function acuanParent()
    {
        return $this->belongsTo(SkpKualitatifAcuanParent::class, 'acuan_parent_id');
    }
}