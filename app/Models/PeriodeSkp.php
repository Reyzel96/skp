<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodeSkp extends Model
{
    use HasFactory;

    // Batas pengisian/penilaian boleh melewati tanggal selesai periode maksimal
    // sebanyak nilai ini (hari, inklusif). Sinkron dengan config('periode.maks_hari_setelah_selesai').
    public const MAKS_HARI_SETELAH_SELESAI = 7;

    protected $table = 'periode_skp';

    protected $fillable = [
        'tahun', 'tipe', 'semester', 'bulan', 'bulan_akhir', 'semester_genap', 'semester_gasal', 'mulai', 'selesai',
        'batas_pengisian', 'batas_penilaian', 'status', 'label_triwulan', 'label_periode_penilaian'
    ];

    protected $casts = [
        'mulai' => 'date',
        'selesai' => 'date',
        'batas_pengisian' => 'date',
        'batas_penilaian' => 'date',
    ];

    protected static function booted(): void
    {
        // Pembekuan distribusi riil:
        // - periode berubah status menjadi 'selesai' → hitung + simpan (timpa bekuan lama);
        // - periode dibuka kembali dari 'selesai' → hapus bekuan.
        static::updated(function (PeriodeSkp $periode) {
            // Abaikan save yang tidak benar-benar mengubah status (mis. save internal
            // dari freeze()/unfreeze() — updated event dipanggil sebelum syncOriginal()).
            if ($periode->getOriginal('status') === $periode->status) {
                return;
            }

            if ($periode->status === 'selesai') {
                \App\Support\DistribusiRiil::freeze($periode);
            } elseif ($periode->getOriginal('status') === 'selesai') {
                \App\Support\DistribusiRiil::unfreeze($periode);
            }
        });

        static::deleted(function (PeriodeSkp $periode) {
            // Tidak ada aksi khusus: kolom bekuan ikut terhapus bersama baris.
        });
    }

    public function skpDosen()
    {
        return $this->hasMany(SkpDosen::class);
    }

    public function skpHeaders() { return $this->hasMany(SkpHeader::class); }

    public function skpTendiks() { return $this->hasMany(SkpTendik::class); }

    public function skpTugasTambahans() { return $this->hasMany(SkpTugasTambahan::class); }

    public function penilaianPerilakus() { return $this->hasMany(PenilaianPerilaku::class); }

    // Jumlah data SKP terkait periode ini, per jenis (tabel dengan FK periode_skp_id).
    // Dipakai guard hapus/edit, pesan error, dan view (index/edit).
    public function jumlahDataTerkait(): array
    {
        return [
            'SKP' => $this->skpHeaders()->count(),
            'baris kualitatif tendik' => $this->skpTendiks()->count(),
            'tugas tambahan' => $this->skpTugasTambahans()->count(),
            'penilaian perilaku' => $this->penilaianPerilakus()->count(),
            'SKP dosen' => $this->skpDosen()->count(),
        ];
    }

    // Periode dianggap "terpakai" bila punya data terkait apa pun.
    public function adaDataTerkait(): bool
    {
        foreach ($this->jumlahDataTerkait() as $jumlah) {
            if ($jumlah > 0) return true;
        }
        return false;
    }

    // ===== Cache rumus distribusi (per periode) =====
    // Key lama (tanpa suffix periode) hanya dipakai sebagai fallback baca;
    // penulisan selalu ke key per periode.
    public const CACHE_RUMUS_KEYS = ['distribusi_nilai_skp', 'distribusi_persen_skp', 'hasil_kerja_skp'];

    public static function cacheRumusKey(string $key, int $periodeId): string
    {
        return $key . '.' . $periodeId;
    }

    // ===== Triwulan (tipe 'bulanan' menyimpan nomor triwulan 1-4 di kolom bulan) =====

    // Rentang tanggal default triwulan: I 01-01→03-31, II 04-01→06-30, III 07-01→09-30, IV 10-01→12-31.
    public static function rentangTriwulan(int $triwulan, int $tahun): array
    {
        $startBulan = match ($triwulan) {
            1 => 1,
            2 => 4,
            3 => 7,
            default => 10,
        };
        $endBulan = $startBulan + 2;

        return [
            'mulai' => \Carbon\Carbon::create($tahun, $startBulan, 1)->toDateString(),
            'selesai' => \Carbon\Carbon::create($tahun, $endBulan, 1)->endOfMonth()->toDateString(),
        ];
    }

    // Angka Romawi triwulan dari nomor 1-4.
    public static function romanTriwulan(?int $triwulan): string
    {
        return match ($triwulan) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            default => '?',
        };
    }

    // Tanggal terakhir yang menentukan penutupan periode:
    // MAX(tanggal selesai, batas pengisian, batas penilaian) — yang NULL diabaikan.
    // Dipakai oleh periode:auto-close.
    public function batasTutupTerakhir(): ?\Carbon\Carbon
    {
        $kandidat = array_filter([
            $this->selesai,
            $this->batas_pengisian,
            $this->batas_penilaian,
        ]);

        if (empty($kandidat)) {
            return null;
        }

        return collect($kandidat)->max()->copy()->startOfDay();
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
        if ($this->status !== 'aktif') return false; // draft/selesai = tertutup
        if (!$this->batas_penilaian) return true; // Jika tidak diset, anggap buka (konsisten dgn isPengisianTerbuka)

        return now()->startOfDay() <= $this->batas_penilaian->startOfDay();
    }

    public function getNamaPeriodeAttribute()
    {
        if ($this->tipe == 'tahunan') {
            return "Tahunan {$this->tahun}";
        } elseif ($this->tipe == 'semester') {
            // Kalender akademik: Ganjil = Sep s.d. Feb tahun berikutnya (tahun mulai),
            // Genap = Mar s.d. Agu (tahun sama).
            return $this->labelSemester() . " {$this->tahun}";
        } elseif ($this->tipe == 'bulanan') {
            // Kolom bulan menyimpan nomor triwulan 1-4.
            return 'Triwulan ' . self::romanTriwulan((int) $this->bulan) . " {$this->tahun}";
        }
        return "Tahun {$this->tahun}";
    }

    // Mapping kolom semester (ganjil/genap) → label tampilan 'Ganjil'/'Genap'.
    public function labelSemester(): string
    {
        return $this->semester === 'genap' ? 'Genap' : 'Ganjil';
    }

    // Label Triwulan (I-IV) dihitung otomatis dari kolom bulan (nomor triwulan 1-4).
    // Admin bisa override lewat kolom label_triwulan (kosong = auto).
    public function getTriwulanLabelAttribute(): string
    {
        if (!empty($this->label_triwulan)) {
            return $this->label_triwulan;
        }

        if ($this->tipe === 'bulanan' && $this->bulan) {
            return 'Triwulan ' . self::romanTriwulan((int) $this->bulan);
        }

        return '-';
    }

    // Teks "PERIODE PENILAIAN" (rentang tanggal kalender murni: tgl 1 s.d. akhir bulan).
    // Admin bisa override lewat kolom label_periode_penilaian (kosong = auto).
    public function getPeriodePenilaianLabelAttribute(): string
    {
        if (!empty($this->label_periode_penilaian)) {
            return $this->label_periode_penilaian;
        }

        if (!$this->mulai || !$this->selesai) {
            return '-';
        }

        $mulai = \Carbon\Carbon::parse($this->mulai)->startOfMonth()->locale('id');
        $selesai = \Carbon\Carbon::parse($this->selesai)->endOfMonth()->locale('id');

        return $mulai->translatedFormat('j F Y') . ' s.d. ' . $selesai->translatedFormat('j F Y');
    }
}
