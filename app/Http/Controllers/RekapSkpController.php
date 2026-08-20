<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User, SkpHeader, PeriodeSkp, SkpTendik, SkpDosen, PenilaianSkpTendik, PenilaianSkpDosen, SkpTugasTambahan, SkpPerilakuIndicator};
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RekapSkpController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $periode = PeriodeSkp::orderBy('tahun', 'desc')->get();
        return view('rekap-skp.index', compact('periode'));
    }

    public function dosen($periode_id)
    {
        $user = Auth::user();
        $periode = PeriodeSkp::findOrFail($periode_id);
        
        $query = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')
              ->where(function($sub) {
                  $sub->where('name', 'like', 'Dosen%')
                    ->orWhere('name', 'like', 'Kaprodi%')
                    ->orWhere('name', 'like', 'Sekprodi%');
              });
        });

        // Kaprodi only sees their own prodi
        if ($user->hasRole('Kaprodi Informatika') || $user->hasRole('Kaprodi Teknik Elektro') || $user->hasRole('Kaprodi Teknik Mesin') || $user->hasRole('Kaprodi Teknik Perkapalan') || $user->hasRole('Kaprodi Teknik Sipil') || $user->hasRole('Kaprodi Teknik Industri') || $user->hasRole('Kaprodi Arsitek')) {
            $prodi = str_replace('Kaprodi ', '', $user->getRoleNames()->first());
            $query->whereHas('roles', function($q) use ($prodi) {
                $q->where('name', 'like', "%$prodi%");
            });
        }

        $users = $query->with(['profile', 'roles'])->get();
        $rekap = $this->calculateRekap($users, $periode_id, 'dosen');

        return view('rekap-skp.dosen', compact('rekap', 'periode'));
    }

    public function dosenKualitatif($periode_id)
    {
        $user = Auth::user();
        $periode = PeriodeSkp::findOrFail($periode_id);
        
        $query = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')
              ->where(function($sub) {
                  $sub->where('name', 'like', 'Dosen%')
                    ->orWhere('name', 'like', 'Kaprodi%')
                    ->orWhere('name', 'like', 'Sekprodi%');
              });
        });

        if ($user->hasRole('Kaprodi Informatika') || $user->hasRole('Kaprodi Teknik Elektro') || $user->hasRole('Kaprodi Teknik Mesin') || $user->hasRole('Kaprodi Teknik Perkapalan') || $user->hasRole('Kaprodi Teknik Sipil') || $user->hasRole('Kaprodi Teknik Industri') || $user->hasRole('Kaprodi Arsitek')) {
            $prodi = str_replace('Kaprodi ', '', $user->getRoleNames()->first());
            $query->whereHas('roles', function($q) use ($prodi) {
                $q->where('name', 'like', "%$prodi%");
            });
        }

        $users = $query->with(['profile', 'roles'])->get();
        $rekap = $this->calculateRekapKualitatif($users, $periode_id);

        return view('rekap-skp.dosen-kualitatif', compact('rekap', 'periode'));
    }

    public function tendik($periode_id)
    {
        $user = Auth::user();
        // Kaprodi cannot access Tendik rekap
        if ($user->hasAnyRole(Role::where('name', 'like', 'Kaprodi%')->pluck('name')->toArray())) {
            abort(403, 'Kaprodi tidak memiliki akses ke rekap SKP Tendik.');
        }

        $periode = PeriodeSkp::findOrFail($periode_id);
        $users = User::whereHas('roles', function($q) {
            $q->where('kategori', 'tendik');
        })->with(['profile', 'roles'])->get();
        $rekap = $this->calculateRekap($users, $periode_id, 'tendik');

        return view('rekap-skp.tendik', compact('rekap', 'periode'));
    }

    private function calculateRekap($users, $periode_id, $type)
    {
        $data = [];
        foreach ($users as $u) {
            $headers = SkpHeader::where('user_id', $u->id)->where('periode_skp_id', $periode_id)->orderBy('created_at', 'asc')->get();
            
            if ($headers->isEmpty()) {
                $data[] = [
                    'user' => $u,
                    'header' => null,
                    'score_skp' => 0,
                    'score_perilaku' => 0,
                    'final_score' => 0,
                    'status' => 'Belum Mulai',
                    'predikat' => '-'
                ];
                continue;
            }

            foreach ($headers as $header) {
                $scoreSkp = 0;
                $scorePerilaku = 0;
                $finalScore = 0;
                $status = $header->status ?? 'Belum Mulai';

                // Calculate SKP Score
                if ($type === 'tendik') {
                    $skpUtama = SkpTendik::where('skp_header_id', $header->id)->get();
                    $totalMaster = 0;
                    foreach ($skpUtama as $skp) {
                        $penilaian = PenilaianSkpTendik::where('skp_tendik_id', $skp->id)->first();
                        $totalMaster += $penilaian ? $penilaian->nilai_capaian : 0;
                    }
                    $avgUtama = $skpUtama->count() > 0 ? ($totalMaster / $skpUtama->count()) : 0;
                    
                    $tambahan = SkpTugasTambahan::where('skp_header_id', $header->id)->sum('penghitungan') ?? 0;
                    $scoreSkp = $avgUtama + $tambahan;
                } else {
                    $skpUtama = SkpDosen::where('skp_header_id', $header->id)->get();
                    $totalMaster = 0;
                    foreach ($skpUtama as $skp) {
                        $penilaian = PenilaianSkpDosen::where('skp_dosen_id', $skp->id)->where('user_id', $u->id)->first();
                        $totalMaster += $penilaian ? $penilaian->nilai_capaian : 0;
                    }
                    $scoreSkp = $skpUtama->count() > 0 ? ($totalMaster / $skpUtama->count()) : 0;
                }

                // Calculate Perilaku Score
                $indicators = SkpPerilakuIndicator::where('skp_header_id', $header->id)->get();
                if ($indicators->count() > 0) {
                    $aspekScores = $indicators->groupBy('aspek')->map(fn($group) => $group->avg('nilai'));
                    $scorePerilaku = $aspekScores->avg() ?? 0;
                }

                $finalScore = ($scoreSkp * 0.6) + ($scorePerilaku * 0.4);

                $data[] = [
                    'user' => $u,
                    'header' => $header,
                    'score_skp' => round($scoreSkp, 2),
                    'score_perilaku' => round($scorePerilaku, 2),
                    'final_score' => round($finalScore, 2),
                    'status' => $status,
                    'predikat' => $this->getPredikat($finalScore)
                ];
            }
        }
        return $data;
    }

    private function getPredikat($score)
    {
        if ($score == 0) return '-';
        if ($score > 90) return 'Sangat Baik';
        if ($score >= 76) return 'Baik';
        if ($score >= 61) return 'Cukup';
        if ($score >= 51) return 'Kurang';
        return 'Sangat Kurang';
    }

    private function calculateRekapKualitatif($users, $periode_id)
    {
        $data = [];
        foreach ($users as $u) {
            $headers = SkpHeader::where('user_id', $u->id)->where('periode_skp_id', $periode_id)->orderBy('created_at', 'asc')->get();
            
            if ($headers->isEmpty()) {
                $data[] = [
                    'user' => $u,
                    'header' => null,
                    'capaian_organisasi' => '-',
                    'predikat' => '-',
                    'status' => 'Belum Mulai',
                ];
                continue;
            }

            foreach ($headers as $header) {
                $status = $header->status ?? 'Belum Mulai';

                $skpUtamas = \App\Models\SkpKualitatif::where('skp_header_id', $header->id)->get();
                $sumH = 0; $countH = 0;
                foreach($skpUtamas as $hk) {
                    if ($hk->hasil_kerja) {
                        $countH++;
                        if ($hk->hasil_kerja == 'Diatas Ekspektasi') $sumH += 3;
                        elseif ($hk->hasil_kerja == 'Sesuai Ekspektasi') $sumH += 2;
                        else $sumH += 1;
                    }
                }
                
                $ratingH = '';
                if ($countH > 0) {
                    $avgH = $sumH / $countH;
                    if ($avgH >= 2.5) $ratingH = 'DIATAS EKSPEKTASI';
                    elseif ($avgH >= 1.5) $ratingH = 'SESUAI EKSPEKTASI';
                    else $ratingH = 'DIBAWAH EKSPEKTASI';
                }

                $skpPerilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $header->id)->get();
                $sumP = 0; $countP = 0;
                foreach($skpPerilakus as $pk) {
                    if ($pk->hasil_kerja) {
                        $countP++;
                        if ($pk->hasil_kerja == 'Diatas Ekspektasi') $sumP += 3;
                        elseif ($pk->hasil_kerja == 'Sesuai Ekspektasi') $sumP += 2;
                        else $sumP += 1;
                    }
                }

                $ratingP = '';
                if ($countP > 0) {
                    $avgP = $sumP / $countP;
                    if ($avgP >= 2.5) $ratingP = 'DIATAS EKSPEKTASI';
                    elseif ($avgP >= 1.5) $ratingP = 'SESUAI EKSPEKTASI';
                    else $ratingP = 'DIBAWAH EKSPEKTASI';
                }

                $predikat = '-';
                if ($ratingH && $ratingP) {
                    if ($ratingH === 'DIATAS EKSPEKTASI') {
                        if ($ratingP === 'DIATAS EKSPEKTASI') $predikat = 'SANGAT BAIK';
                        elseif ($ratingP === 'SESUAI EKSPEKTASI') $predikat = 'BAIK';
                        else $predikat = 'KURANG';
                    } elseif ($ratingH === 'SESUAI EKSPEKTASI') {
                        if ($ratingP === 'DIBAWAH EKSPEKTASI') $predikat = 'BUTUH PERBAIKAN';
                        else $predikat = 'BAIK';
                    } else {
                        if ($ratingP === 'DIATAS EKSPEKTASI') $predikat = 'BUTUH PERBAIKAN';
                        elseif ($ratingP === 'SESUAI EKSPEKTASI') $predikat = 'KURANG';
                        else $predikat = 'SANGAT KURANG';
                    }
                }

                $data[] = [
                    'user' => $u,
                    'header' => $header,
                    'capaian_organisasi' => $header->capaian_kinerja_organisasi ?? '-',
                    'predikat' => ucwords(strtolower($predikat)),
                    'status' => $status
                ];
            }
        }
        return $data;
    }
}
