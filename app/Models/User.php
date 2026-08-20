<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'email',
        'password',
    ];

    /**
     * Virtual attribute for backward compatibility.
     * Returns nama_lengkap from profile if it exists.
     */
    public function getNameAttribute()
    {
        return $this->profile->nama_lengkap ?? null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke profil user (One-to-One).
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function masterSkpTendik()
    {
        return $this->hasMany(MasterSkpTendik::class, 'user_id');
    }

    public function skpHeaders()
    {
        return $this->hasMany(SkpHeader::class, 'user_id');
    }
}
