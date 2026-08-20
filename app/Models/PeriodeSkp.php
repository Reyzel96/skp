<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodeSkp extends Model
{
    use HasFactory;

    protected $table = 'periode_skp';

    protected $fillable = [
        'tahun', 'tipe', 'semester', 'bulan', 'bulan_akhir', 'semester_genap', 'semester_gasal', 'mulai', 'selesai',
        'batas_pengisian', 'batas_penilaian', 'status'
    ];

    protected $casts = [
        'mulai' => 'date',
        'selesai' => 'date',
        'batas_pengisian' => 'date',
        'batas_penilaian' => 'date',
    ];

    public function skpDosen()
    {
        return $this->hasMany(SkpDosen::class);
    }

    // Helper untuk mengecek apakah saat ini masih boleh mengisi SKP
    public function isPengisianTerbuka(): bool
    {
        if ($this->status !== 'aktif') return false;
        if (!$this->batas_pengisian) return true; // Jika tidak diset, anggap buka

        return now()->startOfDay() <= $this->batas_pengisian->startOfDay();
    }

    // Helper untuk mengecek apakah atasan masih boleh menyimpan NILAI (penilaian)
    public function isPenilaianTerbuka(): bool
    {
        if (!$this->batas_penilaian) return true; // Jika tidak diset, anggap buka (konsisten dgn isPengisianTerbuka)

        return now()->startOfDay() <= $this->batas_penilaian->startOfDay();
    }

    public function getNamaPeriodeAttribute()
    {
        if ($this->tipe == 'tahunan') {
            return "Tahun {$this->tahun} : {$this->semester_genap} / {$this->semester_gasal}";
        } elseif ($this->tipe == 'semester') {
            return "Tahun {$this->tahun} : Semester " . ucfirst($this->semester);
        } elseif ($this->tipe == 'bulanan') {
            $startMonth = \Carbon\Carbon::create()->month($this->bulan)->translatedFormat('F');
            $endMonth = $this->bulan_akhir ? ' — ' . \Carbon\Carbon::create()->month($this->bulan_akhir)->translatedFormat('F') : '';
            return "Tahun {$this->tahun} : Bulan {$startMonth}{$endMonth}";
        }
        return "Tahun {$this->tahun}";
    }
}
