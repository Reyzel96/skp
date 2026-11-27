<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MasterSkpTendik;
use App\Models\PeriodeSkp;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\DaftarTupoksi;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MasterSkpTendikController extends Controller
{
    public function index(Request $request)
    {
        // Ambil Header SKP yang dimiliki oleh pegawai dengan role 'tendik'
        $query = \App\Models\SkpHeader::whereHas('user.roles', function($q) {
            $q->where('kategori', 'tendik');
        })->with(['user.profile', 'periode', 'masterActivities']);

        if ($request->filled('periode_id')) {
            $query->where('periode_skp_id', $request->periode_id);
        }

        $headers = $query->latest()->get();

        $roles = Role::where('kategori', 'tendik')->get();
        $periodes = PeriodeSkp::orderBy('mulai', 'desc')->get();

        return view('super-admin.skp-tendik.index', compact('headers', 'roles', 'periodes'));
    }

    public function create()
    {
        $roles = Role::where('kategori', 'tendik')->get();

        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = Role::where('kategori', 'utama')
                            ->where(function($q) use ($requestedRoles) {
                                foreach($requestedRoles as $r) {
                                    $q->orWhere('name', 'like', $r . '%');
                                }
                            })
                            ->get();

        $usersTendik = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'tendik');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        // 1. PEJABAT PENILAI (Dekan, Wadek 1, Wadek 2)
        $pejabatPenilaiList = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Wadek 1', 'Wadek 2']);
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        // 2. ATASAN PEJABAT PENILAI (Rektorat, Rektor, Dekan)
        $atasanPenilaiList = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Rektorat', 'Rektor', 'Dekan']);
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')
              ->where('name', '!=', 'Super Admin');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        $periode = PeriodeSkp::orderBy('mulai', 'desc')->get();

        // ✅ DATA JSON UNTUK POPUP PERSIS SKP-TENDIK/CREATE
        $jsonPegawai = $usersTendik->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        // ✅ Gabungkan Role Tendik/Utama & Bidang yang sudah ada di Kamus, lalu Urutkan
        $roleBidang = Role::whereIn('kategori', ['tendik', 'utama'])->pluck('name')->toArray();
        $kamusBidang = DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))
            ->map(function($b) {
                return str_starts_with(strtolower($b), 'dosen') ? 'Dosen' : $b;
            })
            ->unique()->sort()->values();

        $perilakuCategories = [
            ['name' => "Orientasi Pelayanan", 'indicators' => []],
            ['name' => "Integritas", 'indicators' => []],
            ['name' => "Komitmen", 'indicators' => []],
            ['name' => "Disiplin", 'indicators' => []],
            ['name' => "Kerjasama", 'indicators' => []],
            ['name' => "Kepemimpinan", 'indicators' => []]
        ];

        return view('super-admin.skp-tendik.create', compact(
            'roles',
            'penilaiRoles',
            'usersTendik',
            'pejabatPenilaiList',
            'atasanPenilaiList',
            'calonPenilai',
            'periode',
            'bidangList',
            'perilakuCategories',
            'jsonPegawai',
            'jsonPenilai'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
            'atasan_pejabat_penilai_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.role_name' => 'required|string',
            'items.*.tupoksi' => 'required|string',
            'items.*.jenis_aktivitas' => 'required|string',
            'items.*.penilai_role' => 'nullable|string',
            'items.*.kategori' => 'nullable|array',
            'items.*.periode' => 'nullable|string',
            'items.*.frekuensi_per_tahun' => 'required|numeric|min:1',
            'items.*.durasi_menit' => 'required|numeric|min:1',
            // ✅ VALIDASI TARGET SKP (BARU)
            'items.*.ak' => 'nullable|numeric',
            'items.*.kuantitas' => 'nullable|numeric',
            'items.*.kualitas' => 'nullable|numeric|min:0|max:100',
            'items.*.waktu' => 'nullable|numeric|min:1|max:12',
            'items.*.biaya' => 'nullable|numeric',
            // ✅ VALIDASI PERILAKU (BARU)
            'perilaku' => 'nullable|array',
        ]);

        $user = User::find($request->user_id);

        // Guard duplikat: satu pegawai hanya boleh punya satu header per periode
        $duplikat = \App\Models\SkpHeader::where('periode_skp_id', $request->periode_skp_id)
            ->where('user_id', $user->id)
            ->exists();
        if ($duplikat) {
            return back()->withInput()->with('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
        }

        DB::beginTransaction();
        try {
            // ✅ 1. SELALU BUAT HEADER BARU (Agar tidak merge jika periode sama)
            // Unik per (periode, pegawai) — guard duplikat & constraint DB di atas.
            $header = \App\Models\SkpHeader::create([
                'user_id' => $user->id,
                'periode_skp_id' => $request->periode_skp_id,
                'pejabat_penilai_id' => $request->pejabat_penilai_id,
                'atasan_pejabat_penilai_id' => $request->atasan_pejabat_penilai_id,
                'is_target_approved_penilai' => false,
                'is_target_approved_atasan'  => false,
                'status' => 'Pending'
            ]);

            // ✅ 2. SIMPAN MASTER & OPERASIONAL SKP TENDIK (Linked ke Header)
            foreach ($request->items as $item) {
                // Simpan ke Master (Template) - Sekarang linked ke skp_header_id
                MasterSkpTendik::create([
                    'user_id'             => $user->id,
                    'skp_header_id'       => $header->id,
                    'role_name'           => $item['role_name'],
                    'tupoksi'             => trim($item['tupoksi']),
                    'jenis_aktivitas'     => trim($item['jenis_aktivitas']),
                    'penilai_role'        => $item['penilai_role'] ?? null,
                    'kategori'            => $item['kategori'] ?? ['Utama'],
                    'periode'             => $item['periode'] ?? 'bulanan',
                    'frekuensi_per_tahun' => $item['frekuensi_per_tahun'],
                    'durasi_menit'        => $item['durasi_menit'],
                    'jumlah_sdm'          => 1,
                    'status_approval'     => 'pending',
                ]);

                // Simpan ke Operasional (SKP Target) - Linked ke skp_header_id
                $skpTendik = \App\Models\SkpTendik::create([
                    'periode_skp_id' => $request->periode_skp_id,
                    'user_id'        => $user->id,
                    'skp_header_id'  => $header->id,
                    'role_name'      => $item['role_name'],
                    'tupoksi'        => trim($item['tupoksi']),
                ]);

                \App\Models\SkpTendikDetail::create([
                    'skp_tendik_id'   => $skpTendik->id,
                    'jenis_aktivitas' => trim($item['jenis_aktivitas']),
                    'ak'              => $item['ak'] ?? 0,
                    'kuantitas_output'=> $item['kuantitas'] ?? 0,
                    'kualitas_mutu'   => $item['kualitas'] ?? 0,
                    'waktu'           => $item['waktu'] ?? 0,
                    'biaya'           => $item['biaya'] ?? 0,
                ]);
            }

            // ✅ 3. SIMPAN PERILAKU KERJA (Linked ke Header)
            if ($request->filled('perilaku')) {
                foreach ($request->perilaku as $aspek => $data) {
                    if (isset($data['indicators'])) {
                        $count = count($data['indicators']);
                        
                        foreach ($data['indicators'] as $indData) {
                            $indText = is_array($indData) ? ($indData['text'] ?? '') : $indData;
                            $bobot = is_array($indData) ? ($indData['persentase'] ?? ($count > 0 ? (100 / $count) : 0)) : ($count > 0 ? (100 / $count) : 0);
                            
                            if (!empty(trim($indText))) {
                                \App\Models\SkpPerilakuIndicator::create([
                                    'skp_header_id' => $header->id,
                                    'aspek'         => $aspek,
                                    'indikator'     => trim($indText),
                                    'bobot_persen'  => $bobot,
                                    'is_from_admin' => true,
                                ]);
                            }
                        }
                    }
                }
            }

            // ✅ 4. SIMPAN TUGAS TAMBAHAN (Linked ke Header)
            if ($request->filled('tugas_tambahan')) {
                foreach ($request->tugas_tambahan as $tt) {
                    if (!empty(trim($tt['tugas_tambahan']))) {
                        \App\Models\SkpTugasTambahan::create([
                            'user_id' => $user->id,
                            'skp_header_id' => $header->id,
                            'periode_skp_id' => $request->periode_skp_id,
                            'jenis' => trim($tt['tugas_tambahan']),
                            'kegiatan' => trim($tt['detail'] ?? ''),
                            'ak' => $tt['ak'] ?? 1,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('superadmin.master-skp-tendik.index')->with('success', 'Master Tupoksi, Target SKP, dan Perilaku berhasil disimpan!');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            // Jaring pengaman race condition: constraint unik (periode_skp_id, user_id)
            if ($this->adalahPelanggaranUnikHeader($e)) {
                return back()->withInput()->with('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
            }
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Deteksi pelanggaran unique constraint skp_headers_periode_user_unique.
     */
    private function adalahPelanggaranUnikHeader(\Illuminate\Database\QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'skp_headers_periode_user_unique')
            || in_array((int) ($e->errorInfo[1] ?? 0), [1062, 19, 23505], true);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pejabat_penilai_id' => 'required|exists:users,id',
            'atasan_pejabat_penilai_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.role_name' => 'required|string',
            'items.*.tupoksi' => 'required|string',
            'items.*.jenis_aktivitas' => 'required|string',
            'items.*.penilai_role' => 'nullable|string',
            'items.*.periode' => 'nullable|string',
            'items.*.frekuensi_per_tahun' => 'required|numeric|min:1',
            'items.*.durasi_menit' => 'required|numeric|min:1',
            'perilaku' => 'nullable|array'
        ]);

        // $id di sini adalah header_id
        $header = \App\Models\SkpHeader::findOrFail($id);
        $user = $header->user;

        DB::beginTransaction();
        try {
            // ✅ 1. UPDATE HEADER SKP
            $header->update([
                'pejabat_penilai_id' => $request->pejabat_penilai_id,
                'atasan_pejabat_penilai_id' => $request->atasan_pejabat_penilai_id,
                'is_target_approved_penilai' => false,
                'is_target_approved_atasan'  => false,
                'target_approved_at'         => null
            ]);

            // ✅ 2. UPDATE MASTER TUKPOSI (Hapus dan Re-create yang spesifik header ini)
            MasterSkpTendik::where('skp_header_id', $header->id)->delete();

            foreach ($request->items as $item) {
                MasterSkpTendik::create([
                    'user_id'             => $user->id,
                    'skp_header_id'       => $header->id,
                    'role_name'           => $item['role_name'],
                    'tupoksi'             => trim($item['tupoksi']),
                    'jenis_aktivitas'     => trim($item['jenis_aktivitas']),
                    'penilai_role'        => $item['penilai_role'] ?? null,
                    'kategori'            => $item['kategori'] ?? ['Utama'],
                    'periode'             => $item['periode'] ?? 'bulanan',
                    'frekuensi_per_tahun' => $item['frekuensi_per_tahun'],
                    'durasi_menit'        => $item['durasi_menit'],
                    'jumlah_sdm'          => 1,
                    'status_approval'     => 'pending',
                ]);
            }

            // ✅ 3. UPDATE DETAIL TARGET SKP (Operasional)
            $skpTendik = \App\Models\SkpTendik::where('skp_header_id', $header->id)->first();
            if (!$skpTendik) {
                $skpTendik = \App\Models\SkpTendik::create([
                    'user_id' => $user->id, 
                    'periode_skp_id' => $header->periode_skp_id,
                    'skp_header_id' => $header->id,
                    'role_name' => $user->getRoleNames()->first() ?? 'Tendik', 
                    'tupoksi' => '-'
                ]);
            }

            \App\Models\SkpTendikDetail::where('skp_tendik_id', $skpTendik->id)->delete();
            foreach ($request->items as $item) {
                \App\Models\SkpTendikDetail::create([
                    'skp_tendik_id' => $skpTendik->id,
                    'jenis_aktivitas' => trim($item['jenis_aktivitas']),
                    'ak' => $item['ak'] ?? 0,
                    'kuantitas_output' => $item['kuantitas'] ?? 0,
                    'kualitas_mutu' => $item['kualitas'] ?? 0,
                    'waktu' => $item['waktu'] ?? 0,
                    'biaya' => $item['biaya'] ?? 0,
                ]);
            }

            // ✅ 4. UPDATE PERILAKU
            if ($request->perilaku) {
                \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
                foreach ($request->perilaku as $aspek => $data) {
                    if (isset($data['indicators'])) {
                        $count = count($data['indicators']);
                        foreach ($data['indicators'] as $indData) {
                            $indText = is_array($indData) ? ($indData['text'] ?? '') : $indData;
                            $bobot = is_array($indData) ? ($indData['persentase'] ?? ($count > 0 ? (100 / $count) : 0)) : ($count > 0 ? (100 / $count) : 0);
                            if (!empty(trim($indText))) {
                                \App\Models\SkpPerilakuIndicator::create([
                                    'skp_header_id' => $header->id,
                                    'aspek' => $aspek,
                                    'indikator' => trim($indText),
                                    'bobot_persen' => $bobot,
                                    'is_from_admin' => true
                                ]);
                            }
                        }
                    }
                }
            }

            // ✅ 5. UPDATE TUGAS TAMBAHAN
            \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->delete();
            if ($request->filled('tugas_tambahan')) {
                foreach ($request->tugas_tambahan as $tt) {
                    if (!empty(trim($tt['tugas_tambahan']))) {
                        \App\Models\SkpTugasTambahan::create([
                            'user_id' => $user->id,
                            'skp_header_id' => $header->id,
                            'periode_skp_id' => $header->periode_skp_id,
                            'jenis' => trim($tt['tugas_tambahan']),
                            'kegiatan' => trim($tt['detail'] ?? ''),
                            'ak' => $tt['ak'] ?? 1,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('superadmin.master-skp-tendik.index')->with('success', 'Master & Target SKP pegawai berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        // $id di sini sekarang adalah header_id
        $header = \App\Models\SkpHeader::with(['user.profile', 'periode'])->findOrFail($id);
        $user = $header->user;

        // Ambil Master Activities yang spesifik untuk header ini
        $activities = MasterSkpTendik::where('skp_header_id', $header->id)->get();

        $roles = Role::where('kategori', 'tendik')->get();
        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = Role::where('kategori', 'utama')
                            ->where(function($q) use ($requestedRoles) {
                                foreach($requestedRoles as $r) {
                                    $q->orWhere('name', 'like', $r . '%');
                                }
                            })
                            ->get();

        // 1. PEJABAT PENILAI (Dekan, Wadek 1, Wadek 2)
        $pejabatPenilaiList = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Wadek 1', 'Wadek 2']);
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        // 2. ATASAN PEJABAT PENILAI (Rektorat, Rektor, Dekan)
        $atasanPenilaiList = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Rektorat', 'Rektor', 'Dekan']);
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')
              ->where('name', '!=', 'Super Admin');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        $periode = PeriodeSkp::orderBy('mulai', 'desc')->get();

        // Ambil Detail Target SKP (Operasional) yang sesuai dengan Header
        $skpDetails = collect();
        $skpDetails = \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($header) {
            $q->where('skp_header_id', $header->id);
        })->get()->groupBy(function($item) {
            return trim($item->jenis_aktivitas);
        });

        // Ambil Perilaku
        $perilaku = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');

        // Ambil Tugas Tambahan
        $tugasTambahan = \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->get();
        $tugasTambahanData = $tugasTambahan->map(function($tt) {
            return [
                'tugas_tambahan' => $tt->jenis,
                'detail' => $tt->kegiatan,
                'ak' => $tt->ak,
            ];
        })->toArray();

        $perilakuCategories = [
            ['name' => "Orientasi Pelayanan", 'indicators' => []],
            ['name' => "Integritas", 'indicators' => []],
            ['name' => "Komitmen", 'indicators' => []],
            ['name' => "Disiplin", 'indicators' => []],
            ['name' => "Kerjasama", 'indicators' => []],
            ['name' => "Kepemimpinan", 'indicators' => []]
        ];

        // ✅ DATA JSON UNTUK POPUP
        $jsonPegawai = collect([$user])->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        // ✅ DATA UNTUK FORM EDIT (MERGE MASTER & TARGET)
        $existingItems = $activities->map(function($act) use ($skpDetails) {
            $detail = $skpDetails->get(trim($act->jenis_aktivitas));
            $detail = $detail ? $detail->first() : null;
            
            return [
                'role_name'           => $act->role_name,
                'tupoksi'             => $act->tupoksi,
                'jenis_aktivitas'     => $act->jenis_aktivitas,
                'penilai_role'        => $act->penilai_role,
                'kategori'            => $act->kategori,
                'periode'             => $act->periode,
                'frekuensi_per_tahun' => $act->frekuensi_per_tahun,
                'durasi_menit'        => $act->durasi_menit,
                'ak'                  => $detail->ak ?? 0,
                'kuantitas'           => $detail->kuantitas_output ?? 0,
                'kualitas'            => $detail->kualitas_mutu ?? 0,
                'waktu'               => $detail->waktu ?? 0,
                'biaya'               => $detail->biaya ?? 0,
            ];
        });

        // ✅ Gabungkan Role Tendik/Utama & Bidang yang sudah ada di Kamus, lalu Urutkan
        $roleBidang = Role::whereIn('kategori', ['tendik', 'utama'])->pluck('name')->toArray();
        $kamusBidang = DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))
            ->map(function($b) {
                return str_starts_with(strtolower($b), 'dosen') ? 'Dosen' : $b;
            })
            ->unique()->sort()->values();

        return view('super-admin.skp-tendik.edit', compact(
            'user', 
            'activities', 
            'roles', 
            'penilaiRoles', 
            'bidangList', 
            'pejabatPenilaiList',
            'atasanPenilaiList',
            'calonPenilai', 
            'periode',
            'header',
            'skpDetails',
            'perilaku',
            'perilakuCategories',
            'jsonPegawai',
            'jsonPenilai',
            'existingItems',
            'tugasTambahanData'
        ));
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=Template_Import_Master_SKP_Tendik.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $row1 = ['', '', '', '', 'Kategori', '', '', '', '', '', '', ''];
        $row2 = ['No', 'Bidang', 'Tupoksi', 'Jenis Aktivitas Unit Kerja', 'Utama', 'Periodik', 'Bukan Tupoksi', 'Periode', 'Frekuensi/Tahun', 'Durasi (menit)', 'Jumlah SDM yang Mengerjakan', 'Volume Kerja'];

        $callback = function() use($row1, $row2) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $row1);
            fputcsv($file, $row2);
            fputcsv($file, ['1', 'Tata Usaha', 'Maintenance Server', 'Melakukan backup database', 'V', '', '', 'Tahunan', '52', '60', '1', '3120']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show($id)
    {
        // $id di sini sekarang adalah header_id
        $header = \App\Models\SkpHeader::with(['user.profile', 'pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'periode'])
            ->findOrFail($id);
            
        $user = $header->user;
        
        // Ambil Master Activities yang spesifik untuk header ini
        $activities = MasterSkpTendik::where('skp_header_id', $header->id)->get();

        // Ambil Detail Target SKP (Operasional) yang sesuai dengan Header
        $skpDetails = collect();
        $skpDetails = \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($header) {
            $q->where('skp_header_id', $header->id);
        })->get()->groupBy(function($item) {
            return trim($item->jenis_aktivitas);
        });

        // Ambil Perilaku
        $perilaku = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');

        // Ambil Tugas Tambahan
        $tugasTambahan = \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->get();

        return view('super-admin.skp-tendik.show', compact('user', 'activities', 'header', 'skpDetails', 'perilaku', 'tugasTambahan'));
    }

    public function destroy($id)
    {
        // $id di sini adalah header_id
        DB::beginTransaction();
        try {
            $header = \App\Models\SkpHeader::findOrFail($id);
            
            // 1. Hapus Master Activities spesifik header ini
            MasterSkpTendik::where('skp_header_id', $header->id)->delete();

            // 2. Hapus Detail Operasional & Penilaian spesifik header ini
            $skpTendikIds = \App\Models\SkpTendik::where('skp_header_id', $header->id)->pluck('id');
            \App\Models\SkpTendikDetail::whereIn('skp_tendik_id', $skpTendikIds)->delete();
            \App\Models\PenilaianSkpTendik::whereIn('skp_tendik_id', $skpTendikIds)->delete();
            \App\Models\SkpTendik::where('skp_header_id', $header->id)->delete();

            // 3. Hapus Perilaku & Indikatornya spesifik header ini
            \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
            
            // Hapus headernya
            $header->delete();

            DB::commit();
            return redirect()->route('superadmin.master-skp-tendik.index')->with('success', 'Data SKP berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
