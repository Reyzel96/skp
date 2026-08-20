<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lpj extends Model
{
    use HasFactory;

    protected $table = 'lpj';

    protected $fillable = [
        'program_kegiatan_prodi_id',
        'item_pengeluaran',
        'nominal_penggunaan',
        'tanggal_kuitansi',
        'keterangan',
        'bukti_pengeluaran',
        'status_verifikasi',
        'verified_by',
        'catatan_verifikasi',
        'verified_at'
    ];

    protected $casts = [
        'nominal_penggunaan' => 'decimal:2',
        'tanggal_kuitansi'   => 'date',
        'verified_at'        => 'datetime'
    ];

    public function programKegiatanProdi() {
        return $this->belongsTo(ProgramKegiatanProdi::class, 'program_kegiatan_prodi_id');
    }

    public function verifier() {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending() {
        return $this->status_verifikasi === 'pending';
    }

    public function getStatusLabel() {
        return match($this->status_verifikasi) {
            'diverifikasi' => 'Valid / Tercatat',
            'ditolak' => 'Ditolak / Perlu Revisi',
            default => 'Tersimpan'
        };
    }

    public function getStatusClass() {
        return match($this->status_verifikasi) {
            'diverifikasi' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'ditolak' => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}
