<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nama_lengkap', 'nip', 'pangkat_golongan',
        'jabatan_pekerjaan', 'jabatan_fungsional', 'unit_kerja'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
