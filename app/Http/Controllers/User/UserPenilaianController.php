<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PeriodeSkp, SkpTendik, SkpDosen, SkpTugasTambahan, PenilaianPerilaku};
use Illuminate\Support\Facades\Auth;

class UserPenilaianController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $periode = PeriodeSkp::orderBy('mulai', 'desc')->orderBy('id', 'desc')->get();
        $periodeId = $request->periode_id;
        if (!$periodeId) {
            // Prioritaskan periode aktif yang benar-benar punya SKP milik user.
            $periodeAktif = $periode->where('status', 'aktif');
            $periodeId = $periodeAktif->first(function ($p) use ($user) {
                return \App\Models\SkpHeader::where('user_id', $user->id)->where('periode_skp_id', $p->id)->exists()
                    || SkpTendik::where('user_id', $user->id)->where('periode_skp_id', $p->id)->exists()
                    || SkpDosen::where('user_id', $user->id)->where('periode_skp_id', $p->id)->exists();
            })?->id
                ?? $periodeAktif->first()?->id;
        }

        $nilaiSkp = 0;
        $perilaku = null;
        $isLengkap = false;
        $indicators = collect();

        if ($periodeId) {
            // Cek apakah user ini dinilai menggunakan form SKP Tendik
            $skpTendikCount = SkpTendik::where('user_id', $user->id)->where('periode_skp_id', $periodeId)->count();
            $rataUtama = 0;

            if ($skpTendikCount > 0) {
                // ==========================================
                // 1. LOGIKA SKP TENDIK
                // ==========================================
                $skpUtama = SkpTendik::where('user_id', $user->id)
                                ->where('periode_skp_id', $periodeId)
                                ->with(['details', 'penilaian'])
                                ->get();
                                
                $semuaPenilaian = collect();
                $totalTarget = $skpUtama->count(); 
                $totalRealisasi = 0;

                foreach($skpUtama as $induk) {
                    if($induk->penilaian) {
                        $totalRealisasi++;
                        $semuaPenilaian->push($induk->penilaian);
                    }
                }
                
                $isLengkap = ($totalTarget > 0 && $totalTarget === $totalRealisasi);
                $rataUtama = $isLengkap ? ($semuaPenilaian->avg('nilai_capaian') ?? 0) : 0;

            } else {
                // ==========================================
                // 2. LOGIKA SKP DOSEN
                // ==========================================
                $skpUtama = SkpDosen::where('user_id', $user->id)
                                ->where('periode_skp_id', $periodeId)
                                ->with('penilaian')
                                ->get();
                
                $totalTarget = $skpUtama->count();
                $totalRealisasi = $skpUtama->filter(fn($item) => $item->penilaian !== null)->count();
                
                $isLengkap = ($totalTarget > 0 && $totalTarget === $totalRealisasi);
                $rataUtama = $isLengkap ? ($skpUtama->avg('penilaian.nilai_capaian') ?? 0) : 0;
            }

            // ==========================================
            // 3. KALKULASI AKHIR (BERLAKU UNTUK KEDUANYA)
            // ==========================================
            if ($isLengkap) {
                $totalTambahan = SkpTugasTambahan::where('user_id', $user->id)->where('periode_skp_id', $periodeId)->sum('penghitungan') ?? 0;
                $nilaiSkp = $rataUtama + $totalTambahan;
            } else {
                $nilaiSkp = 0;
            }

            // Ambil data perilaku (Universal)
            $perilaku = PenilaianPerilaku::where('user_id', $user->id)->where('periode_skp_id', $periodeId)->first();

            // Ambil Indikator Perilaku dari Header SKP
            $header = \App\Models\SkpHeader::where('user_id', $user->id)->where('periode_skp_id', $periodeId)->first();
            if ($header) {
                $indicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');
            }
        }

        return view('user.penilaian.index', compact('periode', 'periodeId', 'nilaiSkp', 'perilaku', 'isLengkap', 'indicators'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'orientasi_pelayanan' => 'nullable|numeric|min:0|max:100',
            'integritas' => 'nullable|numeric|min:0|max:100',
            'komitmen' => 'nullable|numeric|min:0|max:100',
            'disiplin' => 'nullable|numeric|min:0|max:100',
            'kerjasama' => 'nullable|numeric|min:0|max:100',
            'kepemimpinan' => 'nullable|numeric|min:0|max:100',
            'realisasi_indicators' => 'nullable|array',
            'new_indicators' => 'nullable|array',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            PenilaianPerilaku::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'periode_skp_id' => $request->periode_skp_id
                ],
                [
                    'orientasi_pelayanan' => $request->orientasi_pelayanan,
                    'integritas' => $request->integritas,
                    'komitmen' => $request->komitmen,
                    'disiplin' => $request->disiplin,
                    'kerjasama' => $request->kerjasama,
                    'kepemimpinan' => $request->kepemimpinan,
                ]
            );

            $header = \App\Models\SkpHeader::where('user_id', Auth::id())->where('periode_skp_id', $request->periode_skp_id)->first();
            if ($header) {
                // Update existing realization
                if ($request->has('realisasi_indicators')) {
                    foreach ($request->realisasi_indicators as $id => $val) {
                        \App\Models\SkpPerilakuIndicator::where('id', $id)->update(['is_realized' => $val == '1']);
                    }
                }

                // Add new indicators from employee
                if ($request->has('new_indicators')) {
                    foreach ($request->new_indicators as $aspek => $items) {
                        foreach ($items as $text) {
                            if (empty($text)) continue;
                            \App\Models\SkpPerilakuIndicator::create([
                                'skp_header_id' => $header->id,
                                'aspek' => $aspek,
                                'indikator' => $text,
                                'bobot_persen' => 0, // Will be recalculated
                                'is_realized' => true,
                                'is_from_admin' => false
                            ]);
                        }
                    }
                }

                // Recalculate weights for all aspects
                $allIndicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');
                foreach ($allIndicators as $aspek => $items) {
                    $count = $items->count();
                    $bobot = 100 / ($count > 0 ? $count : 1);
                    foreach ($items as $item) {
                        $item->update(['bobot_persen' => $bobot]);
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', 'Penilaian Perilaku Kerja & Prestasi berhasil disimpan!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}