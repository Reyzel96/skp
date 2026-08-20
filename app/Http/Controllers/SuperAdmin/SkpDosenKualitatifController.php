<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SkpKualitatif;
use App\Models\SkpKualitatifRumus;
use App\Models\SkpKualitatifAcuanParent;
use App\Models\SkpKualitatifAcuanChild;
use Illuminate\Http\Request;

class SkpDosenKualitatifController extends Controller
{
    public function index(Request $request)
    {
        $periode = \App\Models\PeriodeSkp::orderBy('tahun', 'desc')->get();
        
        $query = SkpKualitatif::with(['skpHeader.user', 'skpHeader.periode', 'skpHeader.pejabatPenilai.roles']);

        if ($request->filled('periode_id')) {
            $query->whereHas('skpHeader', function ($q) use ($request) {
                $q->where('periode_id', $request->periode_id);
            });
        }

        $skpKualitatifs = $query->get()->unique('skp_header_id');

        return view('super-admin.skp-dosen-kualitatif.index', compact('skpKualitatifs', 'periode'));
    }

    public function create()
    {
        $periode = \App\Models\PeriodeSkp::where('status', 'aktif')->orWhere('status', 'draft')->get();
        $utamaAcuan = \App\Models\SkpKualitatifAcuanParent::whereIn('kategori', ['utama', 'jabatan'])->with('children')->get();
        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();

        $getRank = function ($user) {
            $role = strtolower($user->getRoleNames()->first() ?? '');
            if (strpos($role, 'rektor') !== false) return 1;
            if (strpos($role, 'dekan') !== false) return 2;
            if (strpos($role, 'kaprodi') !== false || strpos($role, 'kepala') !== false) return 3;
            if (strpos($role, 'sekprodi') !== false) return 4;
            return 5;
        };

        $usersDosen = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        })->sortBy($getRank);

        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            $roleNames = strtolower($user->getRoleNames()->implode(','));
            if (strpos($roleNames, 'dekan') !== false && strpos($roleNames, 'wakil') === false) return 1;
            if (strpos($roleNames, 'wakil dekan i') !== false || strpos($roleNames, 'wakil dekan 1') !== false) return 2;
            if (strpos($roleNames, 'wakil dekan ii') !== false || strpos($roleNames, 'wakil dekan 2') !== false) return 3;
            return 10;
        })->values();

        $jsonPegawai = $usersDosen->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? 'Dosen'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? 'Dosen',
            'rank' => $getRank($cp)
        ])->values();

        return view('super-admin.skp-dosen-kualitatif.create', compact('periode', 'usersDosen', 'calonPenilai', 'jsonPegawai', 'jsonPenilai', 'getRank', 'utamaAcuan', 'perilakuAcuan'));
    }

    public function store(Request $request)
    {
                $request->validate([
            'user_id' => 'required',
            'periode_skp_id' => 'required',
            'pejabat_penilai_id' => 'required',
            'atasan_pejabat_penilai_id' => 'required',
            'utama_rhk' => 'required|array',
            'utama_indikator' => 'required|array',
        ]);

        // Check if SKP Header already exists for this user and period
        $existingHeader = \App\Models\SkpHeader::where('user_id', $request->user_id)
            ->where('periode_skp_id', $request->periode_skp_id)
            ->first();

        if ($existingHeader) {
            return redirect()->back()->withInput()->with('error', 'SKP Kualitatif untuk pegawai dan periode tersebut sudah ada. Silahkan edit SKP yang sudah ada atau pilih periode/pegawai lain.');
        }

        $header = \App\Models\SkpHeader::create([
            'user_id' => $request->user_id,
            'periode_skp_id' => $request->periode_skp_id,
            'pejabat_penilai_id' => $request->pejabat_penilai_id,
            'atasan_pejabat_penilai_id' => $request->atasan_pejabat_penilai_id,
            'status' => 'Disetujui',
            'is_target_approved_penilai' => true,
            'target_approved_at' => now(),
        ]);

        $request->merge(['skp_header_id' => $header->id]);
        // Simpan A. UTAMA
        if ($request->has('utama_rhk') && is_array($request->utama_rhk)) {
            foreach ($request->utama_rhk as $index => $rhk) {
                if (!empty(trim($rhk))) {
                    \App\Models\SkpKualitatif::create([
                        'skp_header_id' => $header->id,
                        'jenis' => 'utama',
                        'rencana_hasil_kerja_pimpinan' => $request->utama_rhk_pimpinan[$index] ?? '',
                        'rencana_hasil_kerja' => $rhk,
                        'indikator_target' => $request->utama_indikator[$index] ?? '',
                        'acuan_parent_id' => $request->utama_acuan_parent_id[$index] ?? null,
                    ]);
                }
            }
        }

        // Simpan B. TAMBAHAN
        if ($request->has('tambahan') && is_array($request->tambahan)) {
            foreach ($request->tambahan as $tambahanText) {
                if (!empty(trim($tambahanText))) {
                    \App\Models\SkpKualitatifTugasTambahan::create([
                        'skp_header_id' => $header->id,
                        'rencana_hasil_kerja' => $tambahanText,
                    ]);
                }
            }
        }

        // Simpan PERILAKU KERJA
        $perilakuAcuans = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();
        $indikatorMap = [];
        $butuhLampiranMap = [];
        foreach ($perilakuAcuans as $parent) {
            $indikatorText = '';
            foreach ($parent->children as $child) {
                $indikatorText .= "- " . trim($child->indikator_kinerja) . "\n";
            }
            $indikatorMap[$parent->sasaran_kinerja] = trim($indikatorText);
            $butuhLampiranMap[$parent->sasaran_kinerja] = (bool) ($parent->butuh_lampiran ?? false);
        }

        if ($request->has('ekspektasi') && is_array($request->ekspektasi)) {
            foreach ($request->ekspektasi as $aspek => $ekspektasiText) {
                \App\Models\SkpKualitatifPerilaku::create([
                    'skp_header_id' => $header->id,
                    'aspek' => $aspek,
                    'indikator' => $indikatorMap[$aspek] ?? '-',
                    'ekspektasi_pimpinan' => $ekspektasiText,
                    'butuh_lampiran' => $butuhLampiranMap[$aspek] ?? false,
                ]);
            }
        }

        // Simpan LAMPIRAN
        if ($request->has('dukungan') && is_array($request->dukungan)) {
            foreach ($request->dukungan as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'dukungan_sumber_daya', 'deskripsi' => $deskripsi]);
                }
            }
        }
        if ($request->has('skema') && is_array($request->skema)) {
            foreach ($request->skema as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'skema_pertanggungjawaban', 'deskripsi' => $deskripsi]);
                }
            }
        }
        if ($request->has('konsekuensi') && is_array($request->konsekuensi)) {
            foreach ($request->konsekuensi as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'konsekuensi', 'deskripsi' => $deskripsi]);
                }
            }
        }

        return redirect()->route('superadmin.skp-dosen-kualitatif.index')->with('success', 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $skpKualitatif = SkpKualitatif::with(['skpHeader.user.profile', 'skpHeader.user.roles', 'skpHeader.periode', 'skpHeader.pejabatPenilai.profile', 'skpHeader.pejabatPenilai.roles'])->findOrFail($id);
        
        $skpHeaderId = $skpKualitatif->skp_header_id;
        $utamas = \App\Models\SkpKualitatif::where('skp_header_id', $skpHeaderId)->where('jenis', 'utama')->get();
        $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $skpHeaderId)->get();
        $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $skpHeaderId)->get();
        $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $skpHeaderId)->get();
        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();

        return view('super-admin.skp-dosen-kualitatif.show', compact('skpKualitatif', 'utamas', 'tambahans', 'perilakus', 'lampirans', 'perilakuAcuan'));
    }

    public function edit($id)
    {
        $skpKualitatif = SkpKualitatif::with(['skpHeader.user', 'skpHeader.pejabatPenilai'])->findOrFail($id);
        
        $skpHeaderId = $skpKualitatif->skp_header_id;
        $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $skpHeaderId)->where('jenis', 'utama')->get();
        $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $skpHeaderId)->get();
        $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $skpHeaderId)->get();
        $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $skpHeaderId)->get();

        $periode = \App\Models\PeriodeSkp::where('status', 'aktif')->orWhere('status', 'draft')->get();
        $utamaAcuan = \App\Models\SkpKualitatifAcuanParent::whereIn('kategori', ['utama', 'jabatan'])->with('children')->get();
        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();

        $getRank = function ($user) {
            $role = strtolower($user->getRoleNames()->first() ?? '');
            if (strpos($role, 'rektor') !== false) return 1;
            if (strpos($role, 'dekan') !== false) return 2;
            if (strpos($role, 'kaprodi') !== false || strpos($role, 'kepala') !== false) return 3;
            if (strpos($role, 'sekprodi') !== false) return 4;
            return 5;
        };

        $usersDosen = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        })->sortBy($getRank);

        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            $roleNames = strtolower($user->getRoleNames()->implode(','));
            if (strpos($roleNames, 'dekan') !== false && strpos($roleNames, 'wakil') === false) return 1;
            if (strpos($roleNames, 'wakil dekan i') !== false || strpos($roleNames, 'wakil dekan 1') !== false) return 2;
            if (strpos($roleNames, 'wakil dekan ii') !== false || strpos($roleNames, 'wakil dekan 2') !== false) return 3;
            return 10;
        })->values();

        $jsonPegawai = $usersDosen->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? 'Dosen'
        ]);

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? 'Dosen',
            'rank' => $getRank($cp)
        ]);

        return view('super-admin.skp-dosen-kualitatif.edit', compact('skpKualitatif', 'skpUtamas', 'tambahans', 'perilakus', 'lampirans', 'periode', 'usersDosen', 'calonPenilai', 'jsonPegawai', 'jsonPenilai', 'getRank', 'utamaAcuan', 'perilakuAcuan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required',
            'periode_skp_id' => 'required',
            'pejabat_penilai_id' => 'required',
            'atasan_pejabat_penilai_id' => 'required',
            'jenis' => 'required|in:utama,tambahan',
            'rencana_hasil_kerja' => 'required',
            'indikator_target' => 'required',
        ]);

        $skpKualitatif = SkpKualitatif::findOrFail($id);
        $header = $skpKualitatif->skpHeader;

        $header->update([
            'user_id' => $request->user_id,
            'periode_skp_id' => $request->periode_skp_id,
            'pejabat_penilai_id' => $request->pejabat_penilai_id,
            'atasan_pejabat_penilai_id' => $request->atasan_pejabat_penilai_id,
        ]);

        $skpKualitatif->update($request->except(['tambahan', 'ekspektasi', 'dukungan', 'skema', 'konsekuensi']));

        // Hapus tambahan lama lalu buat baru (sync)
        \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $header->id)->delete();
        if ($request->has('tambahan') && is_array($request->tambahan)) {
            foreach ($request->tambahan as $tambahanText) {
                if (!empty(trim($tambahanText))) {
                    \App\Models\SkpKualitatifTugasTambahan::create([
                        'skp_header_id' => $header->id,
                        'rencana_hasil_kerja' => $tambahanText,
                    ]);
                }
            }
        }

        // Hapus perilaku lama lalu buat baru (sync)
        \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $header->id)->delete();
        $perilakuAcuans = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();
        $indikatorMap = [];
        $butuhLampiranMap = [];
        foreach ($perilakuAcuans as $parent) {
            $indikatorText = '';
            foreach ($parent->children as $child) {
                $indikatorText .= "- " . trim($child->indikator_kinerja) . "\n";
            }
            $indikatorMap[$parent->sasaran_kinerja] = trim($indikatorText);
            $butuhLampiranMap[$parent->sasaran_kinerja] = (bool) ($parent->butuh_lampiran ?? false);
        }

        if ($request->has('ekspektasi') && is_array($request->ekspektasi)) {
            foreach ($request->ekspektasi as $aspek => $ekspektasiText) {
                \App\Models\SkpKualitatifPerilaku::create([
                    'skp_header_id' => $header->id,
                    'aspek' => $aspek,
                    'indikator' => $indikatorMap[$aspek] ?? '-',
                    'ekspektasi_pimpinan' => $ekspektasiText,
                    'butuh_lampiran' => $butuhLampiranMap[$aspek] ?? false,
                ]);
            }
        }

        // Hapus lampiran lama lalu buat baru (sync)
        \App\Models\SkpKualitatifLampiran::where('skp_header_id', $header->id)->delete();
        if ($request->has('dukungan') && is_array($request->dukungan)) {
            foreach ($request->dukungan as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'dukungan_sumber_daya', 'deskripsi' => $deskripsi]);
                }
            }
        }
        if ($request->has('skema') && is_array($request->skema)) {
            foreach ($request->skema as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'skema_pertanggungjawaban', 'deskripsi' => $deskripsi]);
                }
            }
        }
        if ($request->has('konsekuensi') && is_array($request->konsekuensi)) {
            foreach ($request->konsekuensi as $deskripsi) {
                if (!empty(trim($deskripsi))) {
                    \App\Models\SkpKualitatifLampiran::create(['skp_header_id' => $header->id, 'jenis' => 'konsekuensi', 'deskripsi' => $deskripsi]);
                }
            }
        }

        return redirect()->route('superadmin.skp-dosen-kualitatif.index')->with('success', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        $skpKualitatif = SkpKualitatif::findOrFail($id);
        $headerId = $skpKualitatif->skp_header_id;

        // Hapus data tugas tambahan, perilaku pekerja & lampiran yang terkait
        \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $headerId)->delete();
        \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $headerId)->delete();
        \App\Models\SkpKualitatifLampiran::where('skp_header_id', $headerId)->delete();

        // Hapus data utama
        $skpKualitatif->delete();

        // Cek jika header tidak memiliki skp lain, hapus header (opsional, tapi aman)
        $hasOther = \App\Models\SkpKualitatif::where('skp_header_id', $headerId)->exists();
        if (!$hasOther) {
            \App\Models\SkpHeader::where('id', $headerId)->delete();
        }

        return redirect()->route('superadmin.skp-dosen-kualitatif.index')->with('success', 'Data beserta tugas tambahan dan perilaku pekerja berhasil dihapus sepenuhnya');
    }

    public function acuanSkp()
    {
        $utama = SkpKualitatifAcuanParent::with('children')->where('kategori', 'utama')->orderBy('urutan')->get();
        $tambahan = SkpKualitatifAcuanParent::with('children')->where('kategori', 'tambahan')->orderBy('urutan')->get();
        $perilaku = SkpKualitatifAcuanParent::with('children')->where('kategori', 'perilaku')->orderBy('urutan')->get();
        $jabatan = SkpKualitatifAcuanParent::with('children')->where('kategori', 'jabatan')->orderBy('urutan')->get();
        return view('super-admin.skp-dosen-kualitatif.acuan', compact('utama', 'tambahan', 'perilaku', 'jabatan'));
    }

    public function updateAcuan(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        $type = $request->input('type'); // 'parent' or 'child'
        
        if ($type === 'parent') {
            $model = SkpKualitatifAcuanParent::findOrFail($id);
        } else {
            $model = SkpKualitatifAcuanChild::findOrFail($id);
        }
        $model->update([$field => $value]);
        
        return response()->json(['success' => true, 'message' => 'Data acuan berhasil diperbarui']);
    }

    public function storeAcuan(Request $request)
    {
        $kategori = $request->input('kategori');
        $urutan = SkpKualitatifAcuan::where('kategori', $kategori)->max('urutan') + 1;
        $acuan = SkpKualitatifAcuan::create([
            'kategori' => $kategori,
            'urutan' => $urutan,
            'sasaran_kinerja' => '',
            'indikator_kinerja' => '',
            'definisi_operasional' => '',
            'bukti_utama' => '',
            'target_ekspektasi' => '',
            'acuan' => ''
        ]);
        return response()->json(['success' => true, 'acuan' => $acuan]);
    }

    public function destroyAcuan($id)
    {
        $acuan = SkpKualitatifAcuan::findOrFail($id);
        $acuan->delete();
        return response()->json(['success' => true]);
    }

    public function rumusHasil()
    {
        $periodeAktif = \App\Models\PeriodeSkp::where('status', 'aktif')->first();

        $jumlahPegawai = 0;
        $dataReal = [
            'Istimewa' => 0,
            'Baik' => 0,
            'Butuh Perbaikan' => 0,
            'Kurang/Misconduct' => 0,
            'Sangat Kurang' => 0,
        ];

        if ($periodeAktif) {
            $jumlahPegawai = \App\Models\SkpHeader::where('periode_skp_id', $periodeAktif->id)
                ->distinct('user_id')
                ->count('user_id');

            $predikatStats = \App\Models\SkpHeader::where('periode_skp_id', $periodeAktif->id)
                ->whereNotNull('predikat_kinerja')
                ->select('predikat_kinerja', \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT user_id) as total'))
                ->groupBy('predikat_kinerja')
                ->get();

            $predikatToChartMap = config('skp.predikat_to_chart_map', []);

            foreach ($predikatStats as $stat) {
                $chartCategory = $predikatToChartMap[$stat->predikat_kinerja] ?? null;
                if ($chartCategory && array_key_exists($chartCategory, $dataReal)) {
                    $dataReal[$chartCategory] = (int) $stat->total;
                }
            }
        }

        $defaultDistribusiNilai = [
            'Istimewa' => [0, 1, 20, 158, 542],
            'Baik' => [83, 125, 250, 458, 83],
            'Butuh Perbaikan' => [125, 167, 417, 167, 125],
            'Kurang/Misconduct' => [83, 458, 250, 125, 83],
            'Sangat Kurang' => [542, 292, 125, 42, 0]
        ];

        $defaultDistribusiPersen = [
            'Istimewa' => [0, 4, 13, 29, 54],
            'Baik' => [8, 13, 25, 46, 8],
            'Butuh Perbaikan' => [13, 17, 42, 17, 13],
            'Kurang/Misconduct' => [8, 46, 25, 13, 8],
            'Sangat Kurang' => [54, 29, 13, 4, 0]
        ];

        $distribusiNilai = \Illuminate\Support\Facades\Cache::rememberForever('distribusi_nilai_skp', function () use ($defaultDistribusiNilai) {
            return $defaultDistribusiNilai;
        });

        $distribusiPersen = \Illuminate\Support\Facades\Cache::get('distribusi_persen_skp', $defaultDistribusiPersen);
        $hasilKerja = \Illuminate\Support\Facades\Cache::get('hasil_kerja_skp', [3, 2, 1]); // Diatas (0), Sesuai (1), Dibawah (2)

        return view('super-admin.skp-dosen-kualitatif.rumus', compact('jumlahPegawai', 'dataReal', 'distribusiNilai', 'distribusiPersen', 'hasilKerja'));
    }

    public function updateRumusPegawai(Request $request)
    {
        $request->validate([
            'distribusi_persen' => 'required|array',
            'hasil_kerja' => 'required|array'
        ]);

        $periodeAktif = \App\Models\PeriodeSkp::where('status', 'aktif')->first();
        $jumlah = $periodeAktif
            ? \App\Models\SkpHeader::where('periode_skp_id', $periodeAktif->id)->distinct('user_id')->count('user_id')
            : 0;

        $persen = $request->distribusi_persen;
        $hasilKerja = $request->hasil_kerja;
        $nilai = [];

        foreach ($persen as $kategori => $persentaseArray) {
            $nilai[$kategori] = [];

            // Sangat Baik (index 4)
            $sangatBaik = $jumlah > 0 ? round(($jumlah * $persentaseArray[4]) / 100) : 0;

            // Baik (index 3)
            if ($kategori === 'Istimewa') {
                $baik = round(($sangatBaik * $persentaseArray[3]) / 100);
            } else {
                $baik = $jumlah > 0 ? round(($jumlah * $persentaseArray[3]) / 100) : 0;
            }

            // Butuh Perbaikan (index 2)
            if ($kategori === 'Istimewa') {
                $butuhPerbaikan = round(($baik * $persentaseArray[2]) / 100);
            } else {
                $butuhPerbaikan = $jumlah > 0 ? round(($jumlah * $persentaseArray[2]) / 100) : 0;
            }

            // Kurang (index 1)
            if ($kategori === 'Istimewa') {
                $kurang = round(($butuhPerbaikan * $persentaseArray[1]) / 100);
            } else {
                $kurang = $jumlah > 0 ? round(($jumlah * $persentaseArray[1]) / 100) : 0;
            }

            foreach ($persentaseArray as $index => $p) {
                if ($index == 4) {
                    $nilai[$kategori][$index] = $sangatBaik;
                } elseif ($index == 3) { // Baik
                    $nilai[$kategori][$index] = $baik;
                } elseif ($index == 2) { // Butuh Perbaikan
                    $nilai[$kategori][$index] = $butuhPerbaikan;
                } elseif ($index == 1) { // Kurang
                    $nilai[$kategori][$index] = $kurang;
                } elseif ($index == 0) { // Sangat Kurang
                    if ($kategori === 'Istimewa') {
                        $nilai[$kategori][$index] = round(($kurang * $p) / 100);
                    } else {
                        $nilai[$kategori][$index] = $jumlah > 0 ? round(($jumlah * $p) / 100) : 0;
                    }
                }
            }
        }

        \Illuminate\Support\Facades\Cache::forever('distribusi_nilai_skp', $nilai);
        \Illuminate\Support\Facades\Cache::forever('distribusi_persen_skp', $persen);
        \Illuminate\Support\Facades\Cache::forever('hasil_kerja_skp', $hasilKerja);

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function addParent(Request $request)
    {
        $kategori = $request->kategori;
        $maxUrutan = \App\Models\SkpKualitatifAcuanParent::where('kategori', $kategori)->max('urutan') ?? 0;
        
        $parent = \App\Models\SkpKualitatifAcuanParent::create([
            'kategori' => $kategori,
            'urutan' => $maxUrutan + 1,
            'sasaran_kinerja' => 'Sasaran Kinerja Baru'
        ]);

        \App\Models\SkpKualitatifAcuanChild::create([
            'parent_id' => $parent->id,
            'urutan' => 1,
            'indikator_kinerja' => 'Indikator Baru',
            'definisi_operasional' => '',
            'bukti_utama' => '',
            'target_ekspektasi' => '',
            'acuan' => ''
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteParent($id)
    {
        \App\Models\SkpKualitatifAcuanParent::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function addChild(Request $request)
    {
        $parent = \App\Models\SkpKualitatifAcuanParent::findOrFail($request->parent_id);
        $maxUrutan = \App\Models\SkpKualitatifAcuanChild::where('parent_id', $parent->id)->max('urutan') ?? 0;
        
        \App\Models\SkpKualitatifAcuanChild::create([
            'parent_id' => $parent->id,
            'urutan' => $maxUrutan + 1,
            'indikator_kinerja' => 'Sub-Indikator Baru',
            'definisi_operasional' => '',
            'bukti_utama' => '',
            'target_ekspektasi' => '',
            'acuan' => ''
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteChild($id)
    {
        \App\Models\SkpKualitatifAcuanChild::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
