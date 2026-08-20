<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianPerilaku extends Model
{
    use HasFactory;

    protected $table = 'penilaian_perilaku';

    protected $fillable = [
        'user_id', 'periode_skp_id', 'orientasi_pelayanan',
        'integritas', 'komitmen', 'disiplin', 'kerjasama', 'kepemimpinan'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function periodeSkp()
    {
        return $this->belongsTo(PeriodeSkp::class, 'periode_skp_id');
    }
}
