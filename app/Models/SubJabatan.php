<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubJabatan extends Model
{
    protected $table = 'sub_jabatan';
    protected $fillable = ['jabatan_id', 'kode', 'nama', 'is_single_user'];
    protected $casts = [
        'is_single_user' => 'boolean'
    ];

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Helper: cek apakah sudah ada user
    public function hasUser(): bool
    {
        return $this->users()->exists();
    }
}
