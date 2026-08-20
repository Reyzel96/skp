<?php

namespace App\Exports;

use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class UserTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UserDataSheet(),
            new InstructionsSheet(),
        ];
    }
}

class UserDataSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    public function array(): array
    {
        return [
            [
                'budi@example.com',
                'password123',
                'Dr. Budi Santoso, M.T.',
                '198001012005011001',
                'Penata / IIIc',
                'Dosen Informatika',
                'Lektor',
                'Dosen Informatika',
                '',
                'Teknik Informatika'
            ],
            [
                'siti@example.com',
                'nip_sebagai_password',
                'Siti Aminah, S.Kom.',
                '199205102020012002',
                'Penata Muda / IIIa',
                'Administrasi Akademik',
                '',
                '',
                'IT, Tata Usaha',
                'Biro Akademik'
            ]
        ];
    }

    public function headings(): array
    {
        // Header disesuaikan dengan UI superadmin/users/create
        return [
            'Email Kampus *',
            'Password *',
            'Nama Lengkap & Gelar *',
            'NIP *',
            'Pangkat / Golongan',
            'Jabatan Pekerjaan',
            'Jabatan Fungsional',
            '🎯 Jabatan Utama',
            'Peran Tambahan / Tendik (Opsional)',
            'Unit Kerja / Penempatan'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD']
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Data User';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Jabatan Fungsional Validation (Column G)
                $validationG = $sheet->getCell('G2')->getDataValidation();
                $validationG->setType(DataValidation::TYPE_LIST);
                $validationG->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validationG->setAllowBlank(true);
                $validationG->setShowDropDown(true);
                $validationG->setFormula1('\'Instruksi Pengisian\'!$M$2:$M$5');
                $sheet->setDataValidation('G2:G1000', $validationG);

                // Jabatan Utama Validation (Column H)
                $utamaCount = Role::where('kategori', 'utama')->count();
                $validationH = $sheet->getCell('H2')->getDataValidation();
                $validationH->setType(DataValidation::TYPE_LIST);
                $validationH->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validationH->setAllowBlank(true);
                $validationH->setShowDropDown(true);
                $validationH->setFormula1('\'Instruksi Pengisian\'!$N$2:$N$' . ($utamaCount + 1));
                $sheet->setDataValidation('H2:H1000', $validationH);

                // Peran Tambahan / Tendik Validation (Column I)
                $tendikCount = Role::where('kategori', 'tendik')->count();
                $validationI = $sheet->getCell('I2')->getDataValidation();
                $validationI->setType(DataValidation::TYPE_LIST);
                $validationI->setErrorStyle(DataValidation::STYLE_WARNING); // WARNING allows typing multiple
                $validationI->setErrorTitle('Input Manual');
                $validationI->setError('Gunakan dropdown untuk pilih satu, atau ketik manual dipisah koma untuk pilih banyak.');
                $validationI->setAllowBlank(true);
                $validationI->setShowDropDown(true);
                $validationI->setFormula1('\'Instruksi Pengisian\'!$O$2:$O$' . ($tendikCount + 1));
                $sheet->setDataValidation('I2:I1000', $validationI);
            }
        ];
    }
}

class InstructionsSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        $jabatanUtama = Role::where('kategori', 'utama')->pluck('name')->toArray();
        $peranTendik = Role::where('kategori', 'tendik')->pluck('name')->toArray();
        $jabatanFungsional = ['Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Guru Besar / Profesor'];

        $maxRows = max(count($jabatanUtama), count($peranTendik), count($jabatanFungsional), 16);
        $rows = [];

        // Static Instructions
        $instructions = [
            ['Email Kampus *', 'Wajib', 'Alamat email aktif dan unik. Contoh: budi@um-surabaya.ac.id'],
            ['Password *', 'Opsional', 'Jika dikosongkan, password otomatis menggunakan NIP.'],
            ['Nama Lengkap & Gelar *', 'Wajib', 'Nama lengkap beserta gelar.'],
            ['NIP *', 'Wajib', 'NIP atau nomor identitas pegawai (harus unik).'],
            ['Pangkat / Golongan', 'Opsional', 'Contoh: Penata / IIIc'],
            ['Jabatan Pekerjaan', 'Opsional', 'Contoh: Dosen, Staff Administrasi, Kabag'],
            ['Jabatan Fungsional', 'Opsional', 'Pilih dari dropdown'],
            ['🎯 Jabatan Utama', 'Wajib', 'Pilih SATU dari dropdown'],
            ['Peran Tambahan / Tendik (Opsional)', 'Opsional', 'Pilih dari dropdown (ketik manual pisah koma jika lebih dari satu)'],
            ['Unit Kerja / Penempatan', 'Opsional', 'Contoh: Prodi Teknik Informatika, Biro Keuangan'],
            ['', '', ''],
            ['CATATAN PENTING:', '', '1. Gunakan dropdown pada sheet Data User untuk mempermudah.'],
            ['', '', '2. Sesuai aturan sistem, disarankan memilih HANYA SATU Jabatan Utama ATAU beberapa Peran Tambahan.'],
        ];

        for ($i = 0; $i < $maxRows; $i++) {
            $row = array_fill(0, 15, ''); // Create empty row up to column O

            if (isset($instructions[$i])) {
                $row[0] = $instructions[$i][0];
                $row[1] = $instructions[$i][1];
                $row[2] = $instructions[$i][2];
            }

            // Columns for validation list (M, N, O which are indexes 12, 13, 14)
            if (isset($jabatanFungsional[$i])) {
                $row[12] = $jabatanFungsional[$i];
            }
            if (isset($jabatanUtama[$i])) {
                $row[13] = $jabatanUtama[$i];
            }
            if (isset($peranTendik[$i])) {
                $row[14] = $peranTendik[$i];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function headings(): array
    {
        $headings = array_fill(0, 15, '');
        $headings[0] = 'KOLOM';
        $headings[1] = 'STATUS';
        $headings[2] = 'KETERANGAN / FORMAT';
        $headings[12] = 'LIST_FUNGSIONAL';
        $headings[13] = 'LIST_UTAMA';
        $headings[14] = 'LIST_TENDIK';
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('M')->setVisible(false);
        $sheet->getColumnDimension('N')->setVisible(false);
        $sheet->getColumnDimension('O')->setVisible(false);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C0504D']
                ],
            ],
            'A' => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Instruksi Pengisian';
    }
}