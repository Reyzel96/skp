<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenjangJabatan extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'urutan'];

    public function pangkatGolongans()
    {
        return $this->hasMany(PangkatGolongan::class);
    }
}
