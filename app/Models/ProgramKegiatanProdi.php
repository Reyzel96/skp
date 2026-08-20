<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKegiatanProdi extends Model
{
    use HasFactory;

    protected $table = 'program_kegiatan_prodi';

    protected $fillable = [
        'program_kegiatan_id', 'prodi_id', 'user_id',
        'nama_kegiatan_prodi', 'semester', 'indikator', 'target_capaian', 'waktu_pelaksanaan',
        'tahun_anggaran', 'tahun_akademik',
        'tanggal_implementasi', 'indikator_keberhasilan',
        'anggaran_usulan_prodi', 'anggaran_disetujui_prodi', 'sisa_anggaran',
        'proposal_type', 'proposal', 'status_persetujuan', 'wadek_can_edit', 'catatan',
        'approved_by_dekan', 'approved_by_wadek', 'approved_by_admin', 'approved_at'
    ];

    protected $casts = [
        'anggaran_usulan_prodi'    => 'decimal:2',
        'anggaran_disetujui_prodi' => 'decimal:2',
        'sisa_anggaran'            => 'decimal:2',
        'target_capaian'           => 'decimal:2',
        'tanggal_implementasi'     => 'date',
        'approved_at'              => 'datetime',
        'wadek_can_edit'           => 'boolean'
    ];

    // --- RELASI ---
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function programKegiatan() {
        return $this->belongsTo(ProgramKegiatan::class, 'program_kegiatan_id');
    }
    public function prodi() {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }
    public function dekanApprover() {
        return $this->belongsTo(User::class, 'approved_by_dekan');
    }
    public function wadekApprover() {
        return $this->belongsTo(User::class, 'approved_by_wadek');
    }
    public function adminApprover() {
        return $this->belongsTo(User::class, 'approved_by_admin');
    }

    public function realisasi() {
        return $this->hasMany(RealisasiAnggaran::class, 'program_kegiatan_prodi_id');
    }

    public function daftarLpj() {
        return $this->hasMany(Lpj::class, 'program_kegiatan_prodi_id');
    }

    // --- HELPER METHODS ---
    public function isDicairkan() {
        return in_array($this->status_persetujuan, ['dicairkan', 'selesai']);
    }

    public function getTotalRealisasi() {
        return $this->realisasi()->where('status', 'berhasil')->sum('nominal');
    }

    public function getTotalLpj() {
        return $this->daftarLpj()->sum('nominal_penggunaan');
    }

    public function getTotalLpjVerified() {
        return $this->daftarLpj()->where('status_verifikasi', 'diverifikasi')->sum('nominal_penggunaan');
    }
}
