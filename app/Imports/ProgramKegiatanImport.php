<?php

namespace App\Imports;

use App\Models\ProgramKegiatan;
use Maatwebsite\Excel\Concerns\ToModel;
// 1. Ganti WithHeadingRow menjadi WithStartRow
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ProgramKegiatanImport implements ToModel, WithStartRow, SkipsEmptyRows, WithChunkReading, WithBatchInserts, WithCalculatedFormulas
{
    /**
     * Mulai membaca data dari baris ke-2 (melewati baris header)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // Berdasarkan header excel user (A=0, B=1, dst):
        // 0: Unit Kerja, 1: Sasaran, 2: Nama Program, 3: Jenis Kegiatan, 4: Nama Kegiatan, 5: Sub Kegiatan, 
        // 6: Per mhs, 7: Anggaran, 8: Anggaran PAGU, 9: Per Prodi, 
        // 10: Semester, 11: Indikator, 12: Target Capaian, 13: Waktu Pelaksanaan, 14: Tahun Anggaran, 15: Tahun Akademik

        $namaProgram  = $row[2] ?? ''; // Kolom C
        $namaKegiatan = $row[4] ?? ''; // Kolom E

        // 1. SKIP BARIS KOSONG
        if (empty($namaProgram) && empty($namaKegiatan)) {
            return null;
        }

        // 2. SKIP BARIS TANDA TANGAN
        $stopWords = ['mengetahui', 'menyetujui', 'dekan', 'wakil dekan', 'wadek', 'dibuat oleh'];
        $programValue = strtolower((string) $namaProgram);
        $kegiatanValue = strtolower((string) $namaKegiatan);

        foreach ($stopWords as $word) {
            if (str_contains($programValue, $word) || str_contains($kegiatanValue, $word)) {
                return null;
            }
        }

        if (empty($namaKegiatan) || trim($namaKegiatan) === '' || trim($namaKegiatan) === '?') {
            return null;
        }

        // 3. AMBIL DATA DARI INDEX YANG BENAR (Sesuai List User)
        $rawPerMhs    = $row[6] ?? 0;
        $rawPagu      = $row[8] ?? 0; // Ambil dari Kolom Anggaran PAGU
        $rawAnggaran  = $rawPagu;    // Samakan Anggaran dengan PAGU sesuai request user
        $rawPerProdi  = $row[9] ?? 0;

        return new ProgramKegiatan([
            'unit_kerja'        => $row[0] ?? 'Fakultas Teknik',
            'sasaran'           => $row[1] ?? null,
            'nama_program'      => $namaProgram,
            'jenis_kegiatan'    => $row[3] ?? null,
            'nama_kegiatan'     => $namaKegiatan,
            'sub_kegiatan'      => $row[5] ?? null,

            'per_mhs'           => $this->cleanNumber($rawPerMhs),
            'anggaran_per_prodi'=> $this->cleanNumber($rawAnggaran),
            'anggaran_pagu'     => $this->cleanNumber($rawPagu),
            'per_prodi'         => $this->cleanNumber($rawPerProdi),

            'semester'          => $row[10] ?? null,
            'indikator'         => $row[11] ?? null,
            'target_capaian'    => $row[12] ?? null,
            'waktu_pelaksanaan' => $row[13] ?? null,

            'tahun_anggaran'    => !empty($row[14]) ? $row[14] : date('Y'),
            'tahun_akademik'    => !empty($row[15]) ? $row[15] : '-',

            'created_by'        => auth()->id()
        ]);
    }

    /**
     * Fungsi Pembersih Angka (Level Industrial)
     */
    private function cleanNumber($value)
    {
        if (empty($value) || trim($value) === '-' || trim($value) === '?') return 0;

        // Jika Excel sudah otomatis meresolusinya sebagai angka murni
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = (string) $value;

        // Hapus karakter alfabet, mata uang 'Rp', dan spasi
        $value = preg_replace('/[a-zA-Z\s]/', '', $value);

        // Analisis letak titik dan koma
        if (str_contains($value, '.') && str_contains($value, ',')) {
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');

            if ($lastComma > $lastDot) {
                // Format Indonesia: 1.500.000,00 -> koma adalah desimal
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Format Inggris: 1,500,000.00 -> titik adalah desimal
                $value = str_replace(',', '', $value);
            }
        }
        elseif (str_contains($value, ',')) {
            // Jika hanya ada koma (misal: 1,500,000 atau 1000,50)
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value); // asumsi desimal
            } else {
                $value = str_replace(',', '', $value); // asumsi ribuan
            }
        }
        elseif (str_contains($value, '.')) {
            // Jika hanya ada titik (misal: 1.500.000 atau 1000.50)
            if (preg_match('/\.\d{3}$/', $value) || preg_match('/\.\d{3}\./', $value)) {
                $value = str_replace('.', '', $value); // titik sebagai pemisah ribuan Indonesia
            }
        }

        // Terakhir, pastikan hanya angka dan titik (untuk desimal) yang tersisa
        $cleanString = preg_replace('/[^0-9\.]/', '', $value);

        return $cleanString === '' ? 0 : (float) $cleanString;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }
}
