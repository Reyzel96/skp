<?php

namespace App\Http\Controllers;

use App\Models\ProgramKegiatan;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProgramKegiatanController extends Controller
{
    protected $keuanganRoles = [
        'Admin Keuangan Fakultas',
        'Administrasi Prodi',
        'Admin Keuangan'
    ];

    private function getProdis() {
        return Prodi::orderBy('nama')->pluck('nama')->toArray();
    }

    public function index(Request $request)
    {
        $query = ProgramKegiatan::with(['creator']);

        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        $kegiatanList = $query->orderBy('created_at', 'desc')->get();

        return view('program-kegiatan.index', compact('kegiatanList'));
    }

    public function show($id)
    {
        $program = ProgramKegiatan::with(['creator'])->findOrFail($id);
        return view('program-kegiatan.show', compact('program'));
    }

    public function create()
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403, 'Unauthorized');

        $prodis = $this->getProdis();
        $periodeAnggaran = \App\Models\PeriodeAnggaran::where('status', 'aktif')->get();
        return view('program-kegiatan.create', compact('prodis', 'periodeAnggaran'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403, 'Unauthorized');

        $validated = $request->validate([
            'programs'                       => 'required|array|min:1',
            'programs.*.unit_kerja'          => 'required|string|max:255',
            'programs.*.sasaran'             => 'nullable|string|max:255',
            'programs.*.nama_program'        => 'required|string|max:255',
            'programs.*.jenis_kegiatan'      => 'nullable|string|max:255',
            'programs.*.nama_kegiatan'       => 'required|string|max:255',
            'programs.*.sub_kegiatan'        => 'nullable|string|max:255',

            'programs.*.per_mhs'             => 'nullable|numeric|min:0',
            'programs.*.anggaran_per_prodi'  => 'required|numeric|min:0',
            'programs.*.anggaran_pagu'       => 'nullable|numeric|min:0',

            'programs.*.tipe_alokasi'        => 'required|in:Rata,Custom',
            'programs.*.per_prodi'           => 'nullable|integer|min:0',
            'programs.*.alokasi_custom'      => 'nullable|array',
            'programs.*.alokasi_custom.*'    => 'nullable|integer|min:0',

            'programs.*.semester'            => 'nullable|string|max:50',
            'programs.*.indikator'           => 'nullable|string',
            'programs.*.target_capaian'      => 'nullable|string|max:255',
            'programs.*.waktu_pelaksanaan'   => 'nullable|string|max:255',

            'programs.*.tahun_anggaran'      => 'required|string',
            'programs.*.tahun_akademik'      => 'required|string',
            'programs.*.periode_id'          => 'required|exists:periode_anggaran,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['programs'] as $prog) {
                // Pastikan anggaran_pagu terisi dari anggaran_per_prodi jika tidak ada
                if (!isset($prog['anggaran_pagu']) || $prog['anggaran_pagu'] == 0) {
                    $prog['anggaran_pagu'] = $prog['anggaran_per_prodi'];
                }

                // Logika validasi dan perbaikan struktur Tipe Alokasi Custom vs Rata
                if ($prog['tipe_alokasi'] === 'Custom') {
                    $totalCustom = array_sum($prog['alokasi_custom'] ?? []);
                    if ($totalCustom > $prog['anggaran_pagu']) {
                        return back()->withInput()->with('error', "Gagal! Total alokasi Prodi pada kegiatan '{$prog['nama_kegiatan']}' melebihi Anggaran Pagu.");
                    }
                    $prog['per_prodi'] = null; // Kosongkan tipe Rata
                } else {
                    $prog['alokasi_custom'] = null; // Kosongkan tipe Custom
                }

                $prog['created_by'] = auth()->id();
                ProgramKegiatan::create($prog);
            }

            DB::commit();
            return redirect()->route('program-kegiatan.index')->with('success', count($validated['programs']) . ' Program Fakultas berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403, 'Unauthorized Action');

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'file.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv',
            'file.max'      => 'Ukuran file maksimal adalah 5MB.'
        ]);

        $selectedPeriodeId = $request->selected_periode_id;
        $periode = \App\Models\PeriodeAnggaran::find($selectedPeriodeId);

        try {
            // Membaca isi Excel mentah
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return redirect()->route('program-kegiatan.create')->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Ambil header (baris pertama) dan bersihkan
            $headerRow = array_shift($rows);
            $headers = array_map(function($h) {
                $clean = str_replace([' ', '.', '-'], '_', strtolower(trim((string)$h)));
                return preg_replace('/__+/', '_', $clean); // Gabungkan underscore ganda
            }, $headerRow);

            $previewData = [];
            $prodis = $this->getProdis();
            $defaultCustom = array_fill_keys($prodis, 0);

            $stopWords = ['mengetahui', 'menyetujui', 'dekan', 'wakil dekan', 'wadek', 'dibuat oleh'];

            foreach ($rows as $rowData) {
                // Pastikan jumlah kolom data sama dengan jumlah kolom header
                if (count($rowData) < count($headers)) {
                    $rowData = array_pad($rowData, count($headers), null);
                }
                
                // Gabungkan header dengan row data (Ambil kemunculan PERTAMA jika ada duplikasi nama header)
                $row = [];
                foreach ($headers as $idx => $h) {
                    if (!isset($row[$h]) || (isset($row[$h]) && empty($row[$h]) && !empty($rowData[$idx]))) {
                        $row[$h] = $rowData[$idx] ?? null;
                    }
                }

                $namaProgram  = $row['nama_program'] ?? '';
                $namaKegiatan = $row['nama_kegiatan'] ?? '';

                if (empty($namaProgram) && empty($namaKegiatan)) continue;

                $isStopWord = false;
                foreach ($stopWords as $word) {
                    if (str_contains(strtolower((string)$namaProgram), $word) || str_contains(strtolower((string)$namaKegiatan), $word)) {
                        $isStopWord = true; break;
                    }
                }
                if ($isStopWord) continue;
                if (empty($namaKegiatan) || trim((string)$namaKegiatan) === '' || trim((string)$namaKegiatan) === '?') continue;

                // Ambil nilai anggaran sesuai dengan request user: 
                // Kolom "Anggaran" di sistem harus mengambil dari "Anggaran PAGU" di Excel.
                $rawPagu     = $row['anggaran_pagu'] ?? null;
                $rawPerProdi = $row['per_prodi'] ?? ($row['per_program_studi'] ?? null);

                // Normalisasi angka
                $finalPagu     = $this->cleanNumber($rawPagu);
                $finalAnggaran = $finalPagu; // Anggaran sistem disamakan dengan Anggaran PAGU excel
                $finalPerProdi = $this->cleanNumber($rawPerProdi);

                // Format Semester agar sesuai dengan dropdown opsi dari Alpine.js
                $rawSemester = strtolower(trim((string)($row['semester'] ?? ($row['smt'] ?? ($row['semester_ganjil/genap'] ?? '')))));
                $formattedYear = $periode ? $periode->tahun_anggaran . '/' . ((int)$periode->tahun_anggaran + 1) : '';
                $finalSemester = '';

                if (str_contains($rawSemester, 'ganjil')) {
                    $finalSemester = trim('Semester Ganjil ' . $formattedYear);
                } elseif (str_contains($rawSemester, 'genap')) {
                    $finalSemester = trim('Semester Genap ' . $formattedYear);
                } else {
                    $finalSemester = $row['semester'] ?? ($row['smt'] ?? ($row['semester_ganjil/genap'] ?? ''));
                }

                // Membaca Kolom Excel & Menormalisasi Array agar sesuai dengan struktur Alpine.js
                $previewData[] = [
                    'periode_id'        => $selectedPeriodeId,
                    'unit_kerja'        => $row['unit_kerja'] ?? 'Fakultas Teknik',
                    'sasaran'           => $row['sasaran'] ?? '',
                    'nama_program'      => $row['nama_program'] ?? ($row['program'] ?? ''),
                    'jenis_kegiatan'    => $row['jenis_kegiatan'] ?? ($row['jenis'] ?? ''),
                    'nama_kegiatan'     => $namaKegiatan,
                    'sub_kegiatan'      => $row['sub_kegiatan'] ?? ($row['sub'] ?? ''),
                    'semester'          => $finalSemester,
                    'indikator'         => $row['indikator'] ?? '',
                    'target_capaian'    => $this->cleanDecimal($row['target_capaian'] ?? ($row['target'] ?? 0)),
                    'waktu_pelaksanaan' => $row['waktu_pelaksanaan'] ?? ($row['waktu'] ?? ''),
                    'tahun_anggaran'    => $row['tahun_anggaran'] ?? ($periode ? $periode->tahun_anggaran : date('Y')),
                    'tahun_akademik'    => $row['tahun_akademik'] ?? ($periode ? $periode->tahun_akademik : '-'),
                    'per_mhs'           => $this->cleanNumber($row['per_mahasiswa'] ?? ($row['per_mhs'] ?? 0)),
                    'anggaran_per_prodi'=> $finalAnggaran,
                    'anggaran_pagu'     => $finalPagu,
                    'tipe_alokasi'      => 'Rata',
                    'per_prodi'         => $this->cleanNumber($rawPerProdi ?: 0),
                    'alokasi_custom'    => $defaultCustom,
                ];
            }

            if (empty($previewData)) {
                return redirect()->route('program-kegiatan.create')->with('error', 'Tidak ada data valid yang dapat diimpor.');
            }

            // Arahkan ke Create dengan membawa Data Parsing untuk di-inject
            return redirect()->route('program-kegiatan.create')->with('import_data', $previewData)->with('success', 'File Excel berhasil dibaca! Silakan edit rincian data di bawah ini sebelum ditekan simpan.');

        } catch (\Exception $e) {
            return redirect()->route('program-kegiatan.index')->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    private function cleanDecimal($value)
    {
        if (empty($value) || trim((string)$value) === '-' || trim((string)$value) === '?') return 0;
        if (is_numeric($value)) return (float) $value;

        $value = (string) $value;
        $value = preg_replace('/[a-zA-Z\s%]/', '', $value);

        if (str_contains($value, '.') && str_contains($value, ',')) {
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }
        
        return (float) $value;
    }

    private function cleanNumber($value)
    {
        if (empty($value) || trim((string)$value) === '-' || trim((string)$value) === '?') return 0;
        if (is_numeric($value)) return (int) round((float) $value);

        $value = (string) $value;
        $value = preg_replace('/[a-zA-Z\s]/', '', $value);

        if (str_contains($value, '.') && str_contains($value, ',')) {
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, '.')) {
            if (preg_match('/\.\d{3}$/', $value) || preg_match('/\.\d{3}\./', $value)) {
                $value = str_replace('.', '', $value);
            }
        }
        $cleanString = preg_replace('/[^0-9\.]/', '', $value);
        return $cleanString === '' ? 0 : (int) round((float) $cleanString);
    }

    public function edit($id)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403);
        $program = ProgramKegiatan::findOrFail($id);
        $prodis = $this->getProdis();
        $periodeAnggaran = \App\Models\PeriodeAnggaran::all();
        return view('program-kegiatan.edit', compact('program', 'prodis', 'periodeAnggaran'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403);
        $program = ProgramKegiatan::findOrFail($id);

        $validated = $request->validate([
            'unit_kerja'        => 'required|string|max:255',
            'sasaran'           => 'nullable|string|max:255',
            'nama_program'      => 'required|string|max:255',
            'jenis_kegiatan'    => 'nullable|string|max:255',
            'nama_kegiatan'     => 'required|string|max:255',
            'sub_kegiatan'      => 'nullable|string|max:255',

            'per_mhs'           => 'nullable|numeric|min:0',
            'anggaran_per_prodi'=> 'required|numeric|min:0',
            'anggaran_pagu'     => 'nullable|numeric|min:0',

            'tipe_alokasi'      => 'required|in:Rata,Custom',
            'per_prodi'         => 'nullable|integer|min:0',
            'alokasi_custom'    => 'nullable|array',
            'alokasi_custom.*'  => 'nullable|integer|min:0',

            'semester'          => 'nullable|string|max:50',
            'indikator'         => 'nullable|string',
            'target_capaian'    => 'nullable|string|max:255',
            'waktu_pelaksanaan' => 'nullable|string|max:255',

            'tahun_anggaran'    => 'required|string',
            'tahun_akademik'    => 'required|string',
            'periode_id'        => 'required|exists:periode_anggaran,id',
        ]);

        if (!isset($validated['anggaran_pagu']) || $validated['anggaran_pagu'] == 0) {
            $validated['anggaran_pagu'] = $validated['anggaran_per_prodi'];
        }

        if ($validated['tipe_alokasi'] === 'Custom') {
            $totalCustom = array_sum($validated['alokasi_custom'] ?? []);
            if ($totalCustom > $validated['anggaran_pagu']) {
                return back()->withInput()->with('error', 'Gagal! Total alokasi per prodi tidak boleh melebihi Anggaran Pagu.');
            }
            $validated['per_prodi'] = null;
        } else {
            $validated['alokasi_custom'] = null;
        }

        $program->update($validated);
        return redirect()->route('program-kegiatan.index')->with('success', 'Data berhasil diperbarui');
    }

    public function bulkDestroy(Request $request)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403);
        $tableName = (new ProgramKegiatan())->getTable();
        $request->validate(['ids' => 'required|array', 'ids.*' => "exists:{$tableName},id"]);
        ProgramKegiatan::whereIn('id', $request->ids)->delete();
        return redirect()->route('program-kegiatan.index')->with('success', 'Data terpilih berhasil dihapus');
    }

    public function destroy(ProgramKegiatan $kegiatan)
    {
        if (!auth()->user()->hasAnyRole($this->keuanganRoles)) abort(403);
        $kegiatan->delete();
        return redirect()->route('program-kegiatan.index')->with('success', 'Data berhasil dihapus');
    }
}
