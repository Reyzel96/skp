<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpTendikDetail extends Model
{
    use HasFactory;
    protected $table = 'skp_tendik_detail';
    protected $fillable = ['skp_tendik_id', 'jenis_aktivitas', 'ak', 'kuantitas_output', 'kualitas_mutu', 'waktu', 'biaya'];

    public function skpTendik() {
        return $this->belongsTo(SkpTendik::class);
    }

    public function penilaian()
    {
        return $this->hasOne(PenilaianSkpTendik::class, 'skp_tendik_id');
    }
}
