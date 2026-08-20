<?php

namespace App\Http\Controllers\Penilai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterSkpTendik;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MasterSkpApprovalController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                $user = Auth::user();
                if (!$user) return redirect()->route('login');

                $userRoles = $user->getRoleNames();
                
                $isDekan = $user->hasRole('Dekan');
                $isWadek1 = $user->hasRole('Wadek 1');
                $isWadek2 = $user->hasRole('Wadek 2');
                $isRektorat = $user->hasAnyRole(['Rektorat', 'Rektor']);
                $isPimpinanFakultas = ($isDekan || $isWadek1 || $isWadek2 || $isRektorat);
                
                $isKaprodi = $userRoles->contains(fn($n) => str_contains($n, 'Kaprodi'));
                $isSekprodi = $userRoles->contains(fn($n) => str_contains($n, 'Sekprodi'));
                $isKepalaLab = $userRoles->contains(fn($n) => str_contains($n, 'Kepala Lab'));
                $isKepalaUPF = $userRoles->contains(fn($n) => str_contains($n, 'Kepala UPF'));
                
                $isReviewer = ($isPimpinanFakultas || $isKaprodi || $isSekprodi || $isKepalaLab || $isKepalaUPF);

                if (!$isReviewer) {
                    abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
                }

                return $next($request);
            }),
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->getRoleNames();
        
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']);

        // Base query: Header SKP yang kegiatannya saya (reviewer) bertanggung jawab atasnya
        $myHeadersQuery = \App\Models\SkpHeader::whereHas('user.roles', function($q) {
            $q->where('kategori', 'tendik');
        })->whereHas('masterActivities', function($q) use ($roles, $isBypassRole) {
            if (!$isBypassRole) {
                $q->whereIn('penilai_role', $roles);
            }
        });

        // 1. Tugas Review Anda: Header yang belum mendapat persetujuan penuh
        $pendingHeaders = (clone $myHeadersQuery)->where(function($query) {
            $query->whereHas('masterActivities', function($q) {
                $q->where('status_approval', 'pending');
            })->orWhere('is_target_approved_penilai', false)
              ->orWhere('is_target_approved_atasan', false);
        })->with(['user.profile', 'periode', 'masterActivities' => function($q) use ($roles, $isBypassRole) {
            if (!$isBypassRole) {
                $q->whereIn('penilai_role', $roles);
            }
        }])->latest()->get();

        // 2. Riwayat Penilaian: Header yang SEMUA persetujuan sudah selesai
        $historyHeaders = (clone $myHeadersQuery)->whereDoesntHave('masterActivities', function($q) {
            $q->where('status_approval', 'pending');
        })->where('is_target_approved_penilai', true)
          ->where('is_target_approved_atasan', true)
          ->with(['user.profile', 'periode', 'masterActivities' => function($q) use ($roles, $isBypassRole) {
            if (!$isBypassRole) {
                $q->whereIn('penilai_role', $roles);
            }
            $q->with('approver.profile');
        }])->latest()->get();

        return view('penilai.approval-skp-tendik.index', compact('pendingHeaders', 'historyHeaders', 'isBypassRole'));
    }

    public function show($headerId)
    {
        $user = Auth::user();
        
        $header = \App\Models\SkpHeader::with(['pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'periode', 'user.profile', 'user.roles'])
            ->findOrFail($headerId);
            
        $pegawai = $header->user;
        $userId = $pegawai->id;
        
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);

        // Tampilkan SELURUH kegiatan user tersebut HANYA untuk header ini
        $activities = MasterSkpTendik::where('skp_header_id', $headerId)
            ->with('approver.profile')
            ->get();

        // Ambil Detail Target SKP (Operasional) yang sesuai dengan Master
        $skpDetails = [];
        if ($header) {
            $skpDetails = \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($userId, $header) {
                $q->where('user_id', $userId)->where('periode_skp_id', $header->periode_skp_id);
            })->get()->groupBy(function($item) {
                return strtolower(preg_replace('/\s+/', ' ', trim($item->jenis_aktivitas)));
            });
        }

        // Ambil Perilaku dan pastikan semua aspek ada
        $p_db = $header ? \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get() : collect();
        $perilaku = collect([
            'Orientasi Pelayanan' => collect(),
            'Integritas' => collect(),
            'Komitmen' => collect(),
            'Disiplin' => collect(),
            'Kerjasama' => collect(),
            'Kepemimpinan' => collect()
        ]);
        foreach($p_db->groupBy('aspek') as $aspek => $items) {
            $perilaku[$aspek] = $items;
        }

        // Ambil Tugas Tambahan
        $tugasTambahan = $header ? \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->get() : collect();

        // ✅ Hanya tampilkan Jabatan/Bidang yang relevan dengan pegawai yang dinilai
        $pegawaiRoles = $pegawai->getRoleNames()->toArray();
        $activityBidang = $activities->pluck('role_name')->unique()->toArray();
        $bidangList = collect(array_merge($pegawaiRoles, $activityBidang))->unique()->sort()->values();

        // Ambil Daftar Role Penilai yang mungkin
        $requestedRoles = ['Dekan', 'Wadek 1', 'Wadek 2', 'Kaprodi', 'Sekprodi', 'Kepala Lab', 'Kepala UPF'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
                            ->where(function($q) use ($requestedRoles) {
                                foreach($requestedRoles as $r) {
                                    $q->orWhere('name', 'like', $r . '%');
                                }
                            })
                            ->get();

        // ✅ Variabel Tambahan untuk View
        $myRoles = $user->getRoleNames();
        $canApproveAny = $isBypassRole;
        $isLevel1Or2 = $isBypassRole || ($header && ($header->pejabat_penilai_id == $user->id || $header->atasan_pejabat_penilai_id == $user->id));

        return view('penilai.approval-skp-tendik.show', compact(
            'pegawai', 'activities', 'isBypassRole', 'header', 
            'skpDetails', 'perilaku', 'tugasTambahan', 
            'penilaiRoles', 'bidangList', 'myRoles', 'canApproveAny', 'isLevel1Or2'
        ));
    }

    public function storeActivity(Request $request, $headerId)
    {
        $user = Auth::user();
        
        $header = \App\Models\SkpHeader::findOrFail($headerId);
        $userId = $header->user_id;
        $pegawai = User::findOrFail($userId);

        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);
        $isAuthorized = $isBypassRole || ($header->pejabat_penilai_id == $user->id) || ($header->atasan_pejabat_penilai_id == $user->id);

        if (!$isAuthorized) {
            abort(403, 'Hanya Pejabat Penilai atau Atasan yang dapat menambah tupoksi.');
        }

        $request->validate([
            'role_name' => 'required|string',
            'tupoksi' => 'required|string',
            'jenis_aktivitas' => 'required|string',
            'penilai_role' => 'required|string',
            'periode' => 'required|string',
            'frekuensi_per_tahun' => 'required|numeric|min:1',
            'durasi_menit' => 'required|numeric|min:1',
            'jumlah_sdm' => 'required|numeric|min:1',
            'ak' => 'nullable|numeric',
            'kuantitas' => 'nullable|numeric',
            'kualitas' => 'nullable|numeric|min:0|max:100',
            'waktu' => 'nullable|numeric|min:0|max:12',
            'biaya' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Master
            MasterSkpTendik::create([
                'user_id' => $userId,
                'skp_header_id' => $headerId,
                'role_name' => $request->role_name,
                'tupoksi' => trim($request->tupoksi),
                'jenis_aktivitas' => trim($request->jenis_aktivitas),
                'penilai_role' => $request->penilai_role,
                'kategori' => $request->kategori ?? ['Utama'],
                'periode' => $request->periode,
                'frekuensi_per_tahun' => $request->frekuensi_per_tahun,
                'durasi_menit' => $request->durasi_menit,
                'jumlah_sdm' => $request->jumlah_sdm,
                'status_approval' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // 2. Create Operational
            $skpTendik = \App\Models\SkpTendik::firstOrCreate(
                ['user_id' => $userId, 'periode_skp_id' => $header->periode_skp_id],
                ['role_name' => $pegawai->getRoleNames()->first() ?? 'Tendik', 'tupoksi' => '-']
            );

            \App\Models\SkpTendikDetail::create([
                'skp_tendik_id'   => $skpTendik->id,
                'jenis_aktivitas' => trim($request->jenis_aktivitas),
                'ak'              => $request->ak ?? 0,
                'kuantitas_output'=> $request->kuantitas ?? 0,
                'kualitas_mutu'   => $request->kualitas ?? 0,
                'waktu'           => $request->waktu ?? 0,
                'biaya'           => $request->biaya ?? 0,
            ]);

            DB::commit();
            return back()->with('success', 'Tupoksi baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $activity = MasterSkpTendik::findOrFail($id);
        $user = Auth::user();
        $header = \App\Models\SkpHeader::find($activity->skp_header_id);
        
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);
        $isAuthorized = $isBypassRole || ($header && ($header->pejabat_penilai_id == $user->id || $header->atasan_pejabat_penilai_id == $user->id));

        if (!$isAuthorized) {
            abort(403, 'Hanya Pejabat Penilai atau Atasan yang dapat menghapus tupoksi.');
        }

        DB::beginTransaction();
        try {
            if ($header) {
                \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($activity, $header) {
                    $q->where('user_id', $activity->user_id)
                      ->where('periode_skp_id', $header->periode_skp_id);
                })->where('jenis_aktivitas', $activity->jenis_aktivitas)->delete();

                \App\Models\SkpTendik::where('user_id', $activity->user_id)
                    ->where('periode_skp_id', $header->periode_skp_id)
                    ->whereDoesntHave('details')
                    ->delete();
            }

            $activity->delete();

            DB::commit();
            return back()->with('success', 'Tupoksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        $activity = MasterSkpTendik::findOrFail($id);
        $user = Auth::user();

        $header = \App\Models\SkpHeader::find($activity->skp_header_id);
        if ($header && $header->status === 'Draft') {
            return back()->with('error', 'Gagal! Pegawai belum melakukan finalisasi terhadap rencana SKP ini.');
        }

        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);
        
        // Pastikan penilai memiliki role yang sesuai ATAU bypass role
        if (!$isBypassRole && !$user->hasRole($activity->penilai_role)) {
            return back()->with('error', 'Anda tidak memiliki otoritas untuk menyetujui kegiatan ini.');
        }

        DB::beginTransaction();
        try {
            $activity->update([
                'role_name'           => $request->role_name ?? $activity->role_name,
                'tupoksi'             => $request->tupoksi ?? $activity->tupoksi,
                'jenis_aktivitas'     => $request->jenis_aktivitas ?? $activity->jenis_aktivitas,
                'penilai_role'        => $request->penilai_role ?? $activity->penilai_role,
                'kategori'            => $request->kategori ?? $activity->kategori,
                'periode'             => $request->periode ?? $activity->periode,
                'frekuensi_per_tahun' => $request->frekuensi_per_tahun ?? $activity->frekuensi_per_tahun,
                'durasi_menit'        => $request->durasi_menit ?? $activity->durasi_menit,
                'status_approval'     => 'approved',
                'approved_by'         => $user->id,
                'approved_at'         => now(),
            ]);

            // ✅ SINKRONISASI KE TABEL OPERASIONAL (SkpTendikDetail)
            $header = \App\Models\SkpHeader::find($activity->skp_header_id);
            if ($header) {
                $oldActivityName = trim($activity->getOriginal('jenis_aktivitas'));
                $newActivityName = trim($activity->jenis_aktivitas);

                \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($activity, $header) {
                    $q->where('user_id', $activity->user_id)
                      ->where('periode_skp_id', $header->periode_skp_id);
                })
                ->where('jenis_aktivitas', $oldActivityName)
                ->update([
                    'jenis_aktivitas' => $newActivityName,
                    'ak'              => $request->ak ?? 0,
                    'kuantitas_output'=> $request->kuantitas ?? 0,
                    'kualitas_mutu'   => $request->kualitas ?? 0,
                    'waktu'           => $request->waktu ?? 0,
                    'biaya'           => $request->biaya ?? 0,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Kegiatan berhasil diperbarui dan disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $activity = MasterSkpTendik::findOrFail($id);
        $user = Auth::user();
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);
        
        if (!$isBypassRole && !$user->hasRole($activity->penilai_role)) {
            return back()->with('error', 'Anda tidak memiliki otoritas untuk menolak kegiatan ini.');
        }

        $header = \App\Models\SkpHeader::find($activity->skp_header_id);

        DB::beginTransaction();
        try {
            if ($header) {
                \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($activity, $header) {
                    $q->where('user_id', $activity->user_id)
                      ->where('periode_skp_id', $header->periode_skp_id);
                })->where('jenis_aktivitas', $activity->jenis_aktivitas)->delete();

                \App\Models\SkpTendik::where('user_id', $activity->user_id)
                    ->where('periode_skp_id', $header->periode_skp_id)
                    ->whereDoesntHave('details')
                    ->delete();
            }

            $activity->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus kegiatan: ' . $e->getMessage());
        }

        return back()->with('success', 'Kegiatan telah ditolak.');
    }

    public function bulkApprove(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) return back()->with('error', 'Tidak ada kegiatan yang dipilih.');

        $user = Auth::user();
        $roles = $user->getRoleNames();
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor']);

        $query = MasterSkpTendik::whereIn('id', $ids);
        if (!$isBypassRole) {
            $query->whereIn('penilai_role', $roles);
        }

        $query->update([
            'status_approval' => 'approved',
            'approved_by'     => $user->id,
            'approved_at'     => now(),
        ]);

        return back()->with('success', 'Kegiatan yang dipilih berhasil disetujui.');
    }

    public function updateAll(Request $request, $headerId)
    {
        $user = Auth::user();
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']);

        DB::beginTransaction();
        try {
            // 1. Update Existing Activities
            if ($request->has('items')) {
                foreach ($request->items as $id => $data) {
                    if (!is_numeric($id)) continue; 

                    $activity = MasterSkpTendik::findOrFail($id);
                    
                    if (!$isBypassRole && !$user->hasRole($activity->penilai_role)) {
                        continue;
                    }

                    $activity->update([
                        'role_name'           => $data['role_name'] ?? $activity->role_name,
                        'tupoksi'             => $data['tupoksi'] ?? $activity->tupoksi,
                        'jenis_aktivitas'     => $data['jenis_aktivitas'] ?? $activity->jenis_aktivitas,
                        'penilai_role'        => $data['penilai_role'] ?? $activity->penilai_role,
                        'periode'             => $data['periode'] ?? $activity->periode,
                        'frekuensi_per_tahun' => $data['frekuensi_per_tahun'] ?? $activity->frekuensi_per_tahun,
                        'durasi_menit'        => $data['durasi_menit'] ?? $activity->durasi_menit,
                        'jumlah_sdm'          => $data['jumlah_sdm'] ?? $activity->jumlah_sdm,
                    ]);

                    $header = \App\Models\SkpHeader::find($activity->skp_header_id);
                    if ($header) {
                        $newActivityName = trim($activity->jenis_aktivitas);
                        
                        \App\Models\SkpTendikDetail::whereHas('skpTendik', function($q) use ($activity, $header) {
                            $q->where('user_id', $activity->user_id)
                              ->where('periode_skp_id', $header->periode_skp_id);
                        })
                        ->where('jenis_aktivitas', $newActivityName)
                        ->update([
                            'ak'              => (isset($data['ak']) && $data['ak'] !== '') ? (float)$data['ak'] : 0,
                            'kuantitas_output'=> (isset($data['kuantitas']) && $data['kuantitas'] !== '') ? (int)$data['kuantitas'] : 0,
                            'kualitas_mutu'   => (isset($data['kualitas']) && $data['kualitas'] !== '') ? (float)$data['kualitas'] : 100,
                            'waktu'           => (isset($data['waktu']) && $data['waktu'] !== '') ? (int)$data['waktu'] : 12,
                            'biaya'           => (isset($data['biaya']) && $data['biaya'] !== '') ? (float)$data['biaya'] : 0,
                        ]);
                    }
                }
            }

            // 2. Simpan Tupoksi & Operasional Baru (jika ada)
            if ($request->has('new_activities')) {
                $header = \App\Models\SkpHeader::where('user_id', $userId)->latest()->first();
                if (!$header) throw new \Exception("Header SKP tidak ditemukan.");

                foreach ($request->new_activities as $act) {
                    if (!empty($act['jenis_aktivitas'])) {
                        // a. Simpan ke Master (Template)
                        \App\Models\MasterSkpTendik::create([
                            'user_id'             => $userId,
                            'skp_header_id'       => $header->id,
                            'role_name'           => $act['role_name'],
                            'tupoksi'             => trim($act['tupoksi'] ?? '-'),
                            'jenis_aktivitas'     => trim($act['jenis_aktivitas']),
                            'penilai_role'        => $act['penilai_role'],
                            'kategori'            => $act['kategori'] ?? ['Utama'],
                            'periode'             => $act['periode'],
                            'frekuensi_per_tahun' => $act['frekuensi_per_tahun'] ?? 1,
                            'durasi_menit'        => $act['durasi_menit'] ?? 60,
                            'jumlah_sdm'          => $act['jumlah_sdm'] ?? 1,
                            'status_approval'     => 'pending',
                            'approved_by'         => null
                        ]);

                        // b. Simpan ke Operasional (SkpTendik & Detail)
                        $tupoksiName = trim($act['tupoksi'] ?? '-');
                        if ($tupoksiName === '') $tupoksiName = '-';

                        $skpTendik = \App\Models\SkpTendik::firstOrCreate(
                            ['user_id' => $userId, 'periode_skp_id' => $header->periode_skp_id, 'skp_header_id' => $header->id, 'tupoksi' => $tupoksiName],
                            ['role_name' => $act['role_name']]
                        );

                        \App\Models\SkpTendikDetail::create([
                            'skp_tendik_id'   => $skpTendik->id,
                            'jenis_aktivitas' => trim($act['jenis_aktivitas']),
                            'ak'              => !empty($act['target_ak']) ? (float)$act['target_ak'] : 0,
                            'kuantitas_output'=> !empty($act['target_kuantitas']) ? (int)$act['target_kuantitas'] : 0,
                            'kualitas_mutu'   => !empty($act['target_kualitas']) ? (float)$act['target_kualitas'] : 100,
                            'waktu'           => !empty($act['target_waktu']) ? (int)$act['target_waktu'] : 12,
                            'biaya'           => !empty($act['target_biaya']) ? (float)$act['target_biaya'] : 0,
                        ]);
                    }
                }
            }

            // 3. Simpan Tugas Tambahan Baru (jika ada)
            if ($request->has('new_tugas')) {
                $header = \App\Models\SkpHeader::where('user_id', $userId)->latest()->first();
                if ($header) {
                    foreach ($request->new_tugas as $tugasData) {
                        if (!empty($tugasData['kegiatan'])) {
                            $p_val = 0;
                            if (!empty($tugasData['realisasi_kegiatan'])) {
                                $p_val = $tugasData['ak_realisasi'] ?? $tugasData['ak'] ?? 1.00;
                            }

                            \App\Models\SkpTugasTambahan::create([
                                'user_id' => $userId,
                                'periode_skp_id' => $header->periode_skp_id,
                                'jenis' => $tugasData['jenis'],
                                'kegiatan' => $tugasData['kegiatan'],
                                'ak' => $tugasData['ak'] ?? 1.00,
                                'ak_realisasi' => $tugasData['ak_realisasi'] ?? 1.00,
                                'realisasi_kegiatan' => $tugasData['realisasi_kegiatan'] ?? null,
                                'penghitungan' => $p_val,
                                'nilai_capaian' => $p_val
                            ]);
                        }
                    }
                }
            }

            // 4. Simpan Perilaku Kerja (jika ada data perilaku di request)
            if ($request->has('perilaku')) {
                $header = \App\Models\SkpHeader::where('user_id', $userId)->latest()->first();
                if ($header) {
                    \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
                    
                    foreach ($request->perilaku as $aspek => $items) {
                        $count = count($items);
                        if ($count > 0) {
                            $defaultBobot = 100 / $count;
                            foreach ($items as $item) {
                                $indikatorText = trim($item['indikator'] ?? '');
                                if (empty($indikatorText) && ($item['nilai'] ?? 0) == 0) continue;

                                \App\Models\SkpPerilakuIndicator::create([
                                    'skp_header_id' => $header->id,
                                    'aspek'         => $aspek,
                                    'indikator'     => $indikatorText,
                                    'nilai'         => $item['nilai'] ?? 0,
                                    'bobot_persen'  => $item['bobot_persen'] ?? $defaultBobot,
                                    'is_realized'   => false,
                                    'is_from_admin' => filter_var($item['is_from_admin'] ?? 1, FILTER_VALIDATE_BOOLEAN) ? 1 : 0
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Seluruh perubahan berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}
