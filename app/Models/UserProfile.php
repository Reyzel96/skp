<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nama_lengkap', 'nip', 'pangkat_golongan',
        'jabatan_pekerjaan', 'jabatan_fungsional', 'unit_kerja', 'prodi_id',
        'pangkat_golongan_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function pangkatGolongan()
    {
        return $this->belongsTo(PangkatGolongan::class);
    }

    /**
     * Accessor: prefer kode_pangkat dari relasi, fall back ke kolom string lama.
     * Laravel melewatkan nilai raw attribute sebagai $value.
     */
    public function getPangkatGolonganAttribute($value)
    {
        return $this->pangkatGolongan?->kode_pangkat ?? $value;
    }
}
