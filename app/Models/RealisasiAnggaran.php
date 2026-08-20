<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiAnggaran extends Model
{
    use HasFactory;

    protected $table = 'realisasi_anggaran';

    protected $fillable = [
        'program_kegiatan_prodi_id',
        'tanggal_pencairan',
        'nominal',
        'keterangan',
        'created_by'
    ];

    protected $casts = [
        'tanggal_pencairan' => 'date',
        'nominal' => 'decimal:2'
    ];

    public function programKegiatanProdi(): BelongsTo
    {
        return $this->belongsTo(ProgramKegiatanProdi::class, 'program_kegiatan_prodi_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
