<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SkpKualitatif;
use App\Models\SkpKualitatifRumus;
use App\Models\SkpKualitatifAcuanParent;
use App\Models\SkpKualitatifAcuanChild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SkpDosenKualitatifController extends Controller
{
    public function index(Request $request)
    {
        $periode = \App\Models\PeriodeSkp::orderBy('tahun', 'desc')->get();
        
        $query = SkpKualitatif::with(['skpHeader.user', 'skpHeader.periode', 'skpHeader.pejabatPenilai.roles']);

        if ($request->filled('periode_id')) {
            $query->whereHas('skpHeader', function ($q) use ($request) {
                $q->where('periode_skp_id', $request->periode_id);
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
                    $utamaParentId = $request->utama_acuan_parent_id[$index] ?? null;
                    \App\Models\SkpKualitatif::create([
                        'skp_header_id' => $header->id,
                        'jenis' => 'utama',
                        'rencana_hasil_kerja_pimpinan' => $request->utama_rhk_pimpinan[$index] ?? '',
                        'rencana_hasil_kerja' => $rhk,
                        'indikator_target' => $request->utama_indikator[$index] ?? '',
                        'acuan_parent_id' => $utamaParentId,
                        'lampiran_snapshot' => $utamaParentId
                            ? \App\Models\SkpKualitatif::buildLampiranSnapshot($header->id, (int) $utamaParentId)
                            : [],
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
        foreach ($perilakuAcuans as $parent) {
            $indikatorText = '';
            foreach ($parent->children as $child) {
                $indikatorText .= "- " . trim($child->indikator_kinerja) . "\n";
            }
            $indikatorMap[$parent->sasaran_kinerja] = trim($indikatorText);
        }

        if ($request->has('ekspektasi') && is_array($request->ekspektasi)) {
            foreach ($request->ekspektasi as $aspek => $ekspektasiText) {
                \App\Models\SkpKualitatifPerilaku::create([
                    'skp_header_id' => $header->id,
                    'aspek' => $aspek,
                    'indikator' => $indikatorMap[$aspek] ?? '-',
                    'ekspektasi_pimpinan' => $ekspektasiText,
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

    public function previewSync($header_id)
    {
        $preview = SkpKualitatif::computeSyncPreview((int) $header_id);
        return response()->json(['success' => true, 'preview' => $preview]);
    }

    public function applySync(Request $request, $header_id)
    {
        // Guard: SKP final tidak boleh diubah strukturnya
        $status = strtolower(\App\Models\SkpHeader::find($header_id)->status ?? '');
        if (in_array($status, ['review akhir', 'selesai'])) {
            return response()->json(['success' => false, 'message' => 'SKP sudah final (status: ' . $status . ') — struktur kotak upload tidak boleh diubah lagi.'], 422);
        }

        DB::beginTransaction();
        try {
            // Recompute fresh dari DB saat ini — JANGAN percaya payload dari modal preview
            $preview = SkpKualitatif::computeSyncPreview((int) $header_id);

            // Group ITEMS per row id, apply dengan PHP (aman untuk pola kolom snapshot)
            $rows = \App\Models\SkpKualitatif::lockForUpdate()
                ->where('skp_header_id', $header_id)->where('jenis', 'utama')
                ->whereNotNull('acuan_parent_id')->get()->keyBy('id');

            $removeByRow = [];
            foreach ($preview['remove'] as $item) {
                $removeByRow[$item['skp_kualitatif_id']] = ($removeByRow[$item['skp_kualitatif_id']] ?? []);
                $removeByRow[$item['skp_kualitatif_id']][] = (int) $item['child_id'];
            }
            $addByRow = [];
            foreach ($preview['add'] as $item) {
                $addByRow[$item['skp_kualitatif_id']] = ($addByRow[$item['skp_kualitatif_id']] ?? []);
                $addByRow[$item['skp_kualitatif_id']][] = ['child_id' => $item['child_id'], 'label' => $item['label']];
            }

            foreach ($rows as $rowId => $row) {
                $snap = collect($row->lampiran_snapshot ?? []);
                $dirty = false;

                // REMOVE dulu
                if (isset($removeByRow[$rowId])) {
                    $snap = $snap->reject(fn ($item) => in_array((int) $item['child_id'], $removeByRow[$rowId], true));
                    $dirty = true;
                }
                // ADD berikutnya (skip kalau duplikat — idempotent)
                if (isset($addByRow[$rowId])) {
                    foreach ($addByRow[$rowId] as $newItem) {
                        if (!$snap->contains('child_id', $newItem['child_id'])) {
                            $snap->push($newItem);
                            $dirty = true;
                        }
                    }
                }

                if ($dirty) {
                    $row->update(['lampiran_snapshot' => $snap->values()->all()]);
                }
            }

            // Audit trail — hanya jika ada perubahan nyata
            if (count($preview['add']) > 0 || count($preview['remove']) > 0) {
                DB::table('skp_lampiran_sync_logs')->insert([
                    'skp_header_id' => $header_id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'preview_json' => json_encode([
                        'add' => $preview['add'],
                        'remove' => $preview['remove'],
                        'skip_has_realisasi' => $preview['skip_has_realisasi'],
                    ]),
                    'applied_at' => now(),
                ]);
            }
            DB::commit();

            return response()->json(['success' => true, 'applied' => $preview]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::warning('applySync: gagal sinkron lampiran', ['header_id' => $header_id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Gagal sinkron: ' . $e->getMessage()], 500);
        }
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
            'utama_rhk' => 'required|array',
            'utama_indikator' => 'required|array',
        ]);

        $skpKualitatif = SkpKualitatif::findOrFail($id);
        $header = $skpKualitatif->skpHeader;

        $header->update([
            'user_id' => $request->user_id,
            'periode_skp_id' => $request->periode_skp_id,
            'pejabat_penilai_id' => $request->pejabat_penilai_id,
            'atasan_pejabat_penilai_id' => $request->atasan_pejabat_penilai_id,
        ]);

        DB::beginTransaction();
        try {
            // UPSERT baris utama by acuan_parent_id (id baris dipertahankan agar
            // nilai penilai [hasil_kerja/umpan_balik] di baris sama tidak hilang).
            $parentIds = $request->input('utama_acuan_parent_id', []);
            $rhks      = $request->input('utama_rhk', []);
            $pimpinans = $request->input('utama_rhk_pimpinan', []);
            $indikators = $request->input('utama_indikator', []);

            $keptParentIds = [];

            foreach ($parentIds as $i => $parentId) {
                if (!isset($rhks[$i])) {
                    continue;
                }
                $keptParentIds[] = (int) $parentId;
            }

            // Blokir penghapusan baris yang Sudah bernilai penilai:
            // baris utama lama yang acuan_parent_id-nya TIDAK dikirim, tapi kolom
            // hasil_kerja/umpan_balik terisi = data penilai yang akan hilang permanen.
            $existingRows = SkpKualitatif::where('skp_header_id', $header->id)
                ->where('jenis', 'utama')
                ->get();

            $blockedRows = $existingRows->filter(function ($row) use ($keptParentIds) {
                $isRemoved = !in_array((int) $row->acuan_parent_id, $keptParentIds, true);
                $hasNilai = !is_null($row->hasil_kerja) || !is_null($row->umpan_balik);
                return $isRemoved && $hasNilai;
            });

            if ($blockedRows->isNotEmpty()) {
                DB::rollBack();
                $labels = $blockedRows
                    ->map(fn($row) => '"' . mb_substr(trim((string) $row->rencana_hasil_kerja), 0, 60) . '..."')
                    ->implode(', ');
                return redirect()
                    ->route('superadmin.skp-dosen-kualitatif.edit', $id)
                    ->with('error', 'Perubahan GAGAL disimpan (tidak ada data yang berubah). Baris utama berikut tidak boleh dihapus karena sudah berisi nilai penilai: ' . $labels . '. Hapus nilai penilai terlebih dahulu di Evaluasi Bawahan bila baris ini memang tidak dipakai.');
            }

            foreach ($parentIds as $i => $parentId) {
                if (!isset($rhks[$i])) {
                    continue;
                }

                $rowTexts = [
                    'rencana_hasil_kerja_pimpinan' => $pimpinans[$i] ?? '',
                    'rencana_hasil_kerja' => $rhks[$i],
                    'indikator_target' => $indikators[$i] ?? '',
                ];

                $existing = $existingRows->firstWhere('acuan_parent_id', (int) $parentId);
                if ($existing) {
                    $existing->update($rowTexts + [
                        'lampiran_snapshot' => \App\Models\SkpKualitatif::buildLampiranSnapshot($header->id, (int) $parentId),
                    ]);
                } else {
                    SkpKualitatif::create($rowTexts + [
                        'skp_header_id' => $header->id,
                        'jenis' => 'utama',
                        'acuan_parent_id' => (int) $parentId,
                        'lampiran_snapshot' => \App\Models\SkpKualitatif::buildLampiranSnapshot($header->id, (int) $parentId),
                    ]);
                }
            }

            // Hapus baris utama yang tidak dikirim DAN tidak bernilai penilai
            // (baris placeholder / yang memang dimatikan form-nya).
            foreach ($existingRows as $row) {
                $isRemoved = !in_array((int) $row->acuan_parent_id, $keptParentIds, true);
                $hasNilai = !is_null($row->hasil_kerja) || !is_null($row->umpan_balik);
                if ($isRemoved && !$hasNilai) {
                    $row->delete();
                }
            }
            DB::commit();

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
            foreach ($perilakuAcuans as $parent) {
                $indikatorText = '';
                foreach ($parent->children as $child) {
                    $indikatorText .= "- " . trim($child->indikator_kinerja) . "\n";
                }
                $indikatorMap[$parent->sasaran_kinerja] = trim($indikatorText);
            }

            if ($request->has('ekspektasi') && is_array($request->ekspektasi)) {
                foreach ($request->ekspektasi as $aspek => $ekspektasiText) {
                    \App\Models\SkpKualitatifPerilaku::create([
                        'skp_header_id' => $header->id,
                        'aspek' => $aspek,
                        'indikator' => $indikatorMap[$aspek] ?? '-',
                        'ekspektasi_pimpinan' => $ekspektasiText,
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
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('superadmin.skp-dosen-kualitatif.edit', $id)
                ->with('error', 'Gagal menyimpan perubahan: ' . $e->getMessage());
        }
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

        $allowedFields = $type === 'parent'
            ? ['sasaran_kinerja']
            : ['indikator_kinerja', 'definisi_operasional', 'bukti_utama', 'target_ekspektasi', 'acuan', 'butuh_lampiran', 'label_lampiran'];

        if (! in_array($field, $allowedFields, true)) {
            // Mode log dulu: update tetap diizinkan, hanya mencatat kemungkinan
            // field yang belum tercakup whitelist di sisi UI.
            Log::warning('updateAcuan: field di luar whitelist tetap diupdate', [
                'type' => $type,
                'id' => $id,
                'field' => $field,
                'value' => $value,
            ]);
        } elseif ($field === 'label_lampiran' && is_string($value) && mb_strlen($value) > 255) {
            return response()->json(['success' => false, 'message' => 'Nama lampiran maksimal 255 karakter'], 422);
        }

        if ($type === 'parent') {
            $model = SkpKualitatifAcuanParent::findOrFail($id);
        } else {
            $model = SkpKualitatifAcuanChild::findOrFail($id);
        }
        $model->update([$field => $value]);
        
        return response()->json(['success' => true, 'message' => 'Data acuan berhasil diperbarui']);
    }

    // TODO (2026-09-09): Endpoint ini dinonaktifkan karena menunjuk model
    // SkpKualitatifAcuan (tabel skp_kualitatif_acuans) yang sudah tidak ada.
    // Aman dihapus permanen setelah dipastikan tidak ada pemanggil lagi
    // (cek storage/logs/laravel.log untuk hit dengan HTTP 410 pada rute ini).
    public function storeAcuan(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Endpoint tidak lagi digunakan'], 410);
    }

    // TODO (2026-09-09): Lihat komentar storeAcuan di atas — sama, nonaktif 410.
    public function destroyAcuan($id)
    {
        return response()->json(['success' => false, 'message' => 'Endpoint tidak lagi digunakan'], 410);
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
        $validated = $request->validate([
            'kategori' => 'required|in:utama,tambahan,perilaku,jabatan',
        ]);

        $parent = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $kategori = $validated['kategori'];
            $maxUrutan = SkpKualitatifAcuanParent::where('kategori', $kategori)->max('urutan') ?? 0;

            $parent = SkpKualitatifAcuanParent::create([
                'kategori' => $kategori,
                'urutan' => $maxUrutan + 1,
                'sasaran_kinerja' => 'Sasaran Kinerja Baru'
            ]);

            SkpKualitatifAcuanChild::create([
                'parent_id' => $parent->id,
                'urutan' => 1,
                'indikator_kinerja' => 'Indikator Baru',
                'definisi_operasional' => '',
                'bukti_utama' => '',
                'target_ekspektasi' => '',
                'acuan' => ''
            ]);

            return $parent;
        });

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
        $child = \App\Models\SkpKualitatifAcuanChild::findOrFail($id);

        if (\App\Models\SkpKualitatifAcuanChild::where('parent_id', $child->parent_id)->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menghapus baris terakhir dari kategori ini'], 422);
        }

        $child->delete();
        return response()->json(['success' => true]);
    }
}
