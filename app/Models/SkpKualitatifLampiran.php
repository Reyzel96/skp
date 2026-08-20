<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkpKualitatifLampiran extends Model
{
    use HasFactory;

    protected $fillable = [
        'skp_header_id',
        'jenis',
        'deskripsi'
    ];

    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
}
