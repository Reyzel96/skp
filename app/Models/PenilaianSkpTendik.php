<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianSkpTendik extends Model
{
    use HasFactory;
    protected $table = 'penilaian_skp_tendik';
    protected $guarded = [];

    protected $casts = [
        'bukti' => 'array',
    ];

    // Ubah nama fungsi ke skpTendik
    public function skpTendik()
    {
        return $this->belongsTo(SkpTendik::class, 'skp_tendik_id');
    }
}
