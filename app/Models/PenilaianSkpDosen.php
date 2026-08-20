<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianSkpDosen extends Model
{
    use HasFactory;

    protected $table = 'penilaian_skp_dosen';

    // Semua kolom diizinkan untuk diisi massal kecuali 'id'
    protected $guarded = ['id'];

    public function skpDosen()
    {
        return $this->belongsTo(SkpDosen::class, 'skp_dosen_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
