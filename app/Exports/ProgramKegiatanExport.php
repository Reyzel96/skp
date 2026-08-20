<?php

namespace App\Exports;

use App\Models\ProgramKegiatan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramKegiatanExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    public function collection()
    {
        return ProgramKegiatan::orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Unit Kerja',
            'Sasaran',
            'Nama Program',
            'Jenis Kegiatan',
            'Nama Kegiatan',
            'Sub Kegiatan',
            'Per mhs',
            'Anggaran',
            'Anggaran Pagu',
            'Per Prodi',
            'Semester',
            'Indikator',
            'Target Capaian',
            'Waktu Pelaksanaan',
            'Tahun Anggaran',
            'Tahun Akademik'
        ];
    }

    public function map($kegiatan): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $kegiatan->unit_kerja ?? 'Fakultas Teknik',
            $kegiatan->sasaran ?? '',
            $kegiatan->nama_program ?? '',
            $kegiatan->jenis_kegiatan ?? '',
            $kegiatan->nama_kegiatan ?? '',
            $kegiatan->sub_kegiatan ?? '',
            $kegiatan->per_mhs ?? 0,
            $kegiatan->anggaran_per_prodi ?? 0,
            $kegiatan->anggaran_pagu ?? 0,
            $kegiatan->per_prodi ?? 0,
            $kegiatan->semester ?? '',
            $kegiatan->indikator ?? '',
            $kegiatan->target_capaian ?? '',
            $kegiatan->waktu_pelaksanaan ?? '',
            $kegiatan->tahun_anggaran ?? '',
            $kegiatan->tahun_akademik ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   'B' => 20,  'C' => 25,  'D' => 25,
            'E' => 20,  'F' => 30,  'G' => 30,  'H' => 15,
            'I' => 20,  'J' => 20,  'K' => 15,  'L' => 15,
            'M' => 30,  'N' => 20,  'O' => 20,  'P' => 15,
            'Q' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D3D3D3']
                ]
            ],
        ];
    }
}
