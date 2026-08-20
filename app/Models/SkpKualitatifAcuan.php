<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpKualitatifAcuan extends Model
{
    use HasFactory;

    protected $table = 'skp_kualitatif_acuans';

    protected $fillable = [
        'kategori',
        'sasaran_kinerja',
        'indikator_kinerja',
        'definisi_operasional',
        'bukti_utama',
        'target_ekspektasi',
        'acuan',
        'urutan',
    ];
}
