<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PangkatGolongan extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenjang_jabatan_id',
        'kode_pangkat',
        'nama_pangkat',
        'golongan',
        'angka_kredit_minimal',
        'urutan',
    ];

    public function jenjangJabatan()
    {
        return $this->belongsTo(JenjangJabatan::class);
    }
}
