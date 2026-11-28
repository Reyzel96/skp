<?php

namespace App\Http\Controllers\Penilai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SkpHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ApprovalSkpDosenKualitatifController extends Controller implements HasMiddleware
{
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
        
        $query = SkpHeader::whereHas('skpKualitatifs')
            ->whereIn('status', ['Draft', 'Diajukan', 'Target Diajukan', 'Disetujui', 'Revisi'])
            ->with(['user.profile', 'periode']);

        $query->where(function($q) use ($user) {
            $q->where('pejabat_penilai_id', $user->id)
              ->orWhere('atasan_pejabat_penilai_id', $user->id)
              ->orWhere('penilai_utama_id', $user->id)
              ->orWhere('penilai_akhir_id', $user->id);
        });

        $headers = $query->orderBy('updated_at', 'desc')->get();

        return view('penilai.approval-skp-dosen-kualitatif.index', compact('headers'));
    }

    public function show($header_id)
    {
        $header = SkpHeader::with([
            'user.profile', 
            'user.roles',
            'pejabatPenilai.profile',
            'periode'
        ])->findOrFail($header_id);

        $utamas = \App\Models\SkpKualitatif::where('skp_header_id', $header_id)->where('jenis', 'utama')->get();
        $tambahans = \App\Models\SkpKualitatifTugasTambahan::where('skp_header_id', $header_id)->get();
        $perilakus = \App\Models\SkpKualitatifPerilaku::where('skp_header_id', $header_id)->get();
        $lampirans = \App\Models\SkpKualitatifLampiran::where('skp_header_id', $header_id)->get();
        $perilakuAcuan = \App\Models\SkpKualitatifAcuanParent::where('kategori', 'perilaku')->with('children')->get();

        return view('penilai.approval-skp-dosen-kualitatif.show', compact('header', 'utamas', 'tambahans', 'perilakus', 'lampirans', 'perilakuAcuan'));
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $header = SkpHeader::findOrFail($id);

        // Otoritas: hanya penilai yang ditunjuk di header ini (atau pimpinan
        // bypass) yang boleh menyetujui — sejajarkan dengan guard index().
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2'])
            || $user->roles->contains(fn ($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = in_array($user->id, array_filter([
            $header->penilai_utama_id,
            $header->penilai_akhir_id,
            $header->pejabat_penilai_id,
            $header->atasan_pejabat_penilai_id,
        ]));
        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki otoritas untuk menyetujui SKP ini.');
        }

        $header->status = 'Disetujui';
        $header->is_target_approved_penilai = true;
        $header->target_approved_at = now();
        $header->save();

        return redirect()->route('penilai.approval-skp-dosen-kualitatif.index')->with('success', 'SKP Kualitatif Dosen berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $header = SkpHeader::findOrFail($id);

        // Otoritas: sama dengan approve() — hanya penilai yang ditunjuk
        // (atau pimpinan bypass) yang boleh mengembalikan untuk revisi.
        $isBypassRole = $user->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2'])
            || $user->roles->contains(fn ($r) => str_contains($r->name, 'Kaprodi'));
        $isAssignedEvaluator = in_array($user->id, array_filter([
            $header->penilai_utama_id,
            $header->penilai_akhir_id,
            $header->pejabat_penilai_id,
            $header->atasan_pejabat_penilai_id,
        ]));
        if (!$isAssignedEvaluator && !$isBypassRole) {
            abort(403, 'Anda tidak memiliki otoritas untuk merevisi SKP ini.');
        }

        $header->status = 'Revisi';
        $header->catatan_penilai = $request->catatan ?? 'Terdapat revisi pada target SKP Anda.';
        $header->save();

        return redirect()->route('penilai.approval-skp-dosen-kualitatif.index')->with('success', 'SKP Kualitatif Dosen dikembalikan untuk revisi.');
    }
}
