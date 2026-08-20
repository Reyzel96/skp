<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpPerilakuIndicator extends Model
{
    use HasFactory;

    protected $table = 'skp_perilaku_indicators';

    protected $fillable = [
        'skp_header_id',
        'aspek',
        'indikator',
        'bobot_persen',
        'nilai',
        'is_realized',
        'is_from_admin'
    ];

    protected $casts = [
        'is_realized' => 'boolean',
        'is_from_admin' => 'boolean',
        'nilai' => 'float',
    ];

    public function header()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
}
