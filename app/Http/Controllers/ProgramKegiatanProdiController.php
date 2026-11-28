<?php

namespace App\Http\Controllers;

use App\Models\ProgramKegiatan;
use App\Models\ProgramKegiatanProdi;
use App\Models\RealisasiAnggaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProgramKegiatanProdiController extends Controller
{
    private function hasAksesPengajuan($user)
    {
        return $user->roles->filter(function($role) {
            return str_starts_with($role->name, 'Kaprodi') ||
                   str_starts_with($role->name, 'Sekprodi') ||
                   str_starts_with($role->name, 'Kepala UPF') ||
                   $role->name === 'Administrasi Prodi' ||
                   $role->name === 'Admin Keuangan Fakultas';
        })->isNotEmpty();
    }

    public function myPengajuan()
    {
        $user = auth()->user();
        if (!$this->hasAksesPengajuan($user)) abort(403);

        // Identifikasi Prodi User
        $detectedProdiBase = '';
        foreach ($user->getRoleNames() as $role) {
            $lowerRole = strtolower($role);
            if (str_contains($lowerRole, 'informatika')) $detectedProdiBase = 'Informatika';
            elseif (str_contains($lowerRole, 'sipil')) $detectedProdiBase = 'Sipil';
            elseif (str_contains($lowerRole, 'mesin')) $detectedProdiBase = 'Mesin';
            elseif (str_contains($lowerRole, 'perkapalan')) $detectedProdiBase = 'Perkapalan';
            elseif (str_contains($lowerRole, 'industri')) $detectedProdiBase = 'Industri';
            elseif (str_contains($lowerRole, 'elektro')) $detectedProdiBase = 'Elektro';
            elseif (str_contains($lowerRole, 'arsitek')) $detectedProdiBase = 'Arsitektur'; 
        }

        if (empty($detectedProdiBase) && $user->profile && $user->profile->unit_kerja) {
            $unitKerja = strtolower($user->profile->unit_kerja);
            if (str_contains($unitKerja, 'informatika')) $detectedProdiBase = 'Informatika';
            elseif (str_contains($unitKerja, 'sipil')) $detectedProdiBase = 'Sipil';
            elseif (str_contains($unitKerja, 'mesin')) $detectedProdiBase = 'Mesin';
            elseif (str_contains($unitKerja, 'perkapalan')) $detectedProdiBase = 'Perkapalan';
            elseif (str_contains($unitKerja, 'industri')) $detectedProdiBase = 'Industri';
            elseif (str_contains($unitKerja, 'elektro')) $detectedProdiBase = 'Elektro';
            elseif (str_contains($unitKerja, 'arsitek')) $detectedProdiBase = 'Arsitektur'; 
        }

        $userProdiId = null;
        if (!empty($detectedProdiBase)) {
            $userProdi = \App\Models\Prodi::where('nama', 'LIKE', '%' . $detectedProdiBase . '%')->first();
            $userProdiId = $userProdi ? $userProdi->id : null;
        }

        $pengajuanList = ProgramKegiatanProdi::with(['programKegiatan', 'dekanApprover', 'wadekApprover', 'adminApprover', 'realisasi'])
            ->where('prodi_id', $userProdiId) // Tampilkan semua usulan dari prodi yang sama
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil SEMUA program kegiatan agar bisa diajukan berkali-kali selama saldo ada
        $programTersedia = ProgramKegiatan::with(['programKegiatanProdi' => function($q) use ($userProdiId) {
                $q->where('prodi_id', $userProdiId);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('program-kegiatan-prodi.my-pengajuan', compact('pengajuanList', 'programTersedia'));
    }

    public function create(ProgramKegiatan $kegiatan)
    {
        $user = auth()->user();
        if (!$this->hasAksesPengajuan($user)) abort(403);

        $prodis = \App\Models\Prodi::orderBy('nama')->get();

        return view('program-kegiatan-prodi.create', compact('kegiatan', 'prodis'));
    }

    public function store(Request $request, ProgramKegiatan $kegiatan)
    {
        $user = auth()->user();
        if (!$this->hasAksesPengajuan($user)) abort(403);

        // 1. Tentukan batas anggaran prodi (Gunakan fuzzy matching untuk Arsitek/Arsiterktur dll)
        $detectedProdiBase = '';
        foreach ($user->getRoleNames() as $role) {
            $lowerRole = strtolower($role);
            if (str_contains($lowerRole, 'informatika')) $detectedProdiBase = 'Informatika';
            elseif (str_contains($lowerRole, 'sipil')) $detectedProdiBase = 'Sipil';
            elseif (str_contains($lowerRole, 'mesin')) $detectedProdiBase = 'Mesin';
            elseif (str_contains($lowerRole, 'perkapalan')) $detectedProdiBase = 'Perkapalan';
            elseif (str_contains($lowerRole, 'industri')) $detectedProdiBase = 'Industri';
            elseif (str_contains($lowerRole, 'elektro')) $detectedProdiBase = 'Elektro';
            elseif (str_contains($lowerRole, 'arsitek')) $detectedProdiBase = 'Arsitektur'; 
        }

        $customAmount = 0;
        if ($detectedProdiBase && $kegiatan->alokasi_custom) {
            foreach($kegiatan->alokasi_custom as $pName => $val) {
                if (str_contains(strtolower($pName), strtolower($detectedProdiBase))) {
                    $customAmount = (int)$val;
                    break;
                }
            }
        }

        $batasAnggaran = ($kegiatan->tipe_alokasi === 'Custom') 
            ? $customAmount
            : ($kegiatan->per_prodi > 0 ? (int)$kegiatan->per_prodi : (int)$kegiatan->anggaran_pagu);

        // Jika batas anggaran tetap 0, beri fallback ke pagu agar tidak stuck (opsional, tergantung kebijakan)
        if ($batasAnggaran <= 0) $batasAnggaran = (int)$kegiatan->anggaran_pagu;

        $validated = $request->validate([
            'prodi_id'               => 'required|exists:prodis,id',
            'nama_kegiatan_prodi'    => 'required|string|max:255',
            'semester'               => 'nullable|string|max:50',
            'tahun_anggaran'         => 'nullable|string|max:20',
            'tahun_akademik'         => 'nullable|string|max:50',
            'anggaran_usulan_prodi'  => 'required|numeric|min:1|max:' . $batasAnggaran,
            'tanggal_implementasi'   => 'required|date',
            'indikator'              => 'nullable|string',
            'target_capaian'         => 'nullable|string|max:255',
            'waktu_pelaksanaan'      => 'nullable|string|max:255',
            'indikator_keberhasilan' => 'required|string',
            'proposal_type'          => 'required|in:file,manual',
            'proposal_file'          => 'required_if:proposal_type,file|nullable|file|mimes:pdf|max:10240',
            'proposal_manual'        => 'required_if:proposal_type,manual|nullable|string',
        ], [
            'anggaran_usulan_prodi.max' => 'Nominal usulan melebihi alokasi yang ditetapkan untuk prodi Anda (Maks: Rp ' . number_format($batasAnggaran, 0, ',', '.') . ')'
        ]);

        $proposalContent = null;
        if ($validated['proposal_type'] === 'file') {
            $proposalContent = $request->hasFile('proposal_file') ? $request->file('proposal_file')->store('proposals', 'public') : null;
        } else {
            $proposalContent = $validated['proposal_manual'];
        }

        // Bersihkan target_capaian jika ada karakter non-numeric agar tidak error di decimal column
        $cleanTargetCapaian = preg_replace('/[^0-9.]/', '', $validated['target_capaian'] ?? '0');
        if ($cleanTargetCapaian === '') $cleanTargetCapaian = 0;

        ProgramKegiatanProdi::create([
            'user_id'                => $user->id,
            'program_kegiatan_id'    => $kegiatan->id,
            'prodi_id'               => $validated['prodi_id'],
            'nama_kegiatan_prodi'    => $validated['nama_kegiatan_prodi'],
            'semester'               => $validated['semester'] ?? $kegiatan->semester,
            'indikator'              => $validated['indikator'] ?? $kegiatan->indikator,
            'target_capaian'         => $cleanTargetCapaian,
            'waktu_pelaksanaan'      => $validated['waktu_pelaksanaan'] ?? $kegiatan->waktu_pelaksanaan,
            'tahun_anggaran'         => $validated['tahun_anggaran'] ?? $kegiatan->tahun_anggaran,
            'tahun_akademik'         => $validated['tahun_akademik'] ?? $kegiatan->tahun_akademik,
            'anggaran_usulan_prodi'  => $validated['anggaran_usulan_prodi'],
            'anggaran_disetujui_prodi'=> 0,
            'sisa_anggaran'          => 0,
            'tanggal_implementasi'   => $validated['tanggal_implementasi'],
            'indikator_keberhasilan' => $validated['indikator_keberhasilan'],
            'proposal_type'          => $validated['proposal_type'],
            'proposal'               => $proposalContent,
            'status_persetujuan'     => 'pending'
        ]);

        return redirect()->route('kegiatan-prodi.my-pengajuan')->with('success', 'Usulan kegiatan prodi berhasil disubmit dan menunggu persetujuan Dekan.');
    }

public function listAdmin()
{
    $user = auth()->user();
    if (!$user->hasAnyRole(['Admin Keuangan Fakultas', 'Dekan', 'Wadek 2', 'Rektorat'])) abort(403);

    $allQuery = ProgramKegiatanProdi::with(['user.profile', 'programKegiatan', 'prodi', 'dekanApprover', 'wadekApprover', 'adminApprover', 'realisasi']);

    $allData = $allQuery->orderBy('created_at', 'desc')->get();

    $waitingList = collect();
    $historyList = collect();

    foreach ($allData as $pengajuan) {
        $isWaiting = false;

        if ($user->hasRole('Dekan')) {
            if ($pengajuan->status_persetujuan === 'pending') $isWaiting = true;
        } elseif ($user->hasRole('Wadek 2')) {
            if ($pengajuan->status_persetujuan === 'disetujui_dekan') $isWaiting = true;
        } elseif ($user->hasRole('Admin Keuangan Fakultas')) {
            // Menunggu Admin: Jika status disetujui_wadek ATAU sedang proses dicairkan (masih ada sisa)
            if ($pengajuan->status_persetujuan === 'disetujui_wadek') {
                $isWaiting = true;
            } elseif ($pengajuan->status_persetujuan === 'dicairkan') {
                $cair = method_exists($pengajuan, 'getTotalRealisasi') ? $pengajuan->getTotalRealisasi() : 0;
                if (($pengajuan->anggaran_disetujui_prodi - $cair) > 0) {
                    $isWaiting = true;
                }
            }
        } elseif ($user->hasRole('Rektorat') || $user->hasRole('Super Admin')) {
            // Rektorat/SuperAdmin mungkin hanya monitoring, masukkan semua ke history atau sesuai filter
            $isWaiting = ($pengajuan->status_persetujuan === 'pending');
        }

        // Masukkan ke koleksi yang sesuai
        if ($isWaiting) {
            $waitingList->push($pengajuan);
        } else {
            // History adalah data yang sudah diproses atau berada di tahap lain
            // Namun filter supaya user hanya melihat history yang relevan (sudah lewat tahap mereka atau ditolak)
            $historyList->push($pengajuan);
        }
    }

    return view('program-kegiatan-prodi.list-admin', compact('waitingList', 'historyList'));
}
public function show(ProgramKegiatanProdi $pengajuan)
{
    $user = auth()->user();
    // Guard otoritas: pemilik pengajuan, reviewer (Dekan/Wadek 2/Admin Keuangan/
    // Rektorat/Super Admin), atau user satu prodi. Cegah IDOR baca antar prodi.
    $isOwner = ($pengajuan->user_id === $user->id);
    $isReviewer = $user->hasAnyRole(['Dekan', 'Wadek 2', 'Admin Keuangan Fakultas', 'Rektorat', 'Super Admin']);
    $isSameProdi = $pengajuan->prodi_id !== null
        && $user->profile !== null
        && (int) $user->profile->prodi_id === (int) $pengajuan->prodi_id;
    if (!$isOwner && !$isReviewer && !$isSameProdi) abort(403);

    $pengajuan->load(['programKegiatan', 'user.profile', 'dekanApprover', 'wadekApprover', 'adminApprover', 'realisasi.creator']);
    return view('program-kegiatan-prodi.show', compact('pengajuan'));
}

public function approve(Request $request, ProgramKegiatanProdi $pengajuan)
{
    $user = auth()->user();
    $validated = $request->validate([
        'action' => 'required|in:setuju,lanjut_wadek,tolak',
        'catatan' => 'nullable|string',
        'anggaran_disetujui_prodi' => 'nullable|numeric|min:0'
    ]);

    if ($validated['action'] === 'tolak') {
        // Guard role: penolakan hanya boleh dilakukan reviewer berwenang
        // (sebelumnya user mana pun yang login bisa menolak pengajuan mana pun).
        if (!$user->hasAnyRole(['Dekan', 'Wadek 2', 'Admin Keuangan Fakultas', 'Rektorat', 'Super Admin'])) {
            abort(403, 'Anda tidak memiliki otoritas untuk menolak pengajuan ini.');
        }
        $pengajuan->update([
            'status_persetujuan' => 'ditolak',
            'catatan' => $validated['catatan'] ?? null
        ]);
    } else {
        if ($user->hasRole('Dekan') && $pengajuan->status_persetujuan === 'pending') {
            $pagu = $request->anggaran_disetujui_prodi ?? $pengajuan->anggaran_usulan_prodi;

            // Pilihan Dekan: 
            // 'setuju' -> Wadek tidak bisa edit (wadek_can_edit = false)
            // 'lanjut_wadek' -> Wadek bisa edit (wadek_can_edit = true)
            $canEdit = ($validated['action'] === 'lanjut_wadek');

            $pengajuan->update([
                'status_persetujuan' => 'disetujui_dekan', // Tetap ke Dekan dulu sesuai alur baru
                'approved_by_dekan' => $user->id,
                'anggaran_disetujui_prodi' => $pagu,
                'sisa_anggaran' => $pagu,
                'wadek_can_edit' => $canEdit,
                'catatan' => $validated['catatan'] ?? null
            ]);
        } elseif ($user->hasRole('Wadek 2') && $pengajuan->status_persetujuan === 'disetujui_dekan') {
            // Jika Wadek boleh edit, ambil nominal dari input, jika tidak pakai yang sudah ada
            $paguFinal = $pengajuan->anggaran_disetujui_prodi;
            if ($pengajuan->wadek_can_edit && $request->has('anggaran_disetujui_prodi')) {
                $paguFinal = $request->anggaran_disetujui_prodi;
            }

            $pengajuan->update([
                'status_persetujuan' => 'disetujui_wadek', 
                'approved_by_wadek' => $user->id,
                'anggaran_disetujui_prodi' => $paguFinal,
                'sisa_anggaran' => $paguFinal,
                'catatan' => $validated['catatan'] ?? null
            ]);
        } elseif ($user->hasRole('Admin Keuangan Fakultas') && $pengajuan->status_persetujuan === 'disetujui_wadek') {
            $pengajuan->update([
                'status_persetujuan' => 'disetujui_admin', 
                'approved_by_admin' => $user->id, 
                'approved_at' => now(),
                'catatan' => $validated['catatan'] ?? null
            ]);
        }
    }
    return redirect()->back()->with('success', 'Persetujuan berhasil diproses');
}    public function cairkan(Request $request, ProgramKegiatanProdi $pengajuan)
    {
        if (!auth()->user()->hasRole('Admin Keuangan Fakultas')) abort(403);

        $validated = $request->validate([
            'tanggal_pencairan' => 'required|date',
            'nominal' => 'required|numeric|min:1',
            'bukti_transfer' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'keterangan' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $buktiPath = $request->hasFile('bukti_transfer') ? $request->file('bukti_transfer')->store('bukti_transfer', 'public') : null;

            RealisasiAnggaran::create([
                'program_kegiatan_prodi_id' => $pengajuan->id,
                'tanggal_pencairan' => $validated['tanggal_pencairan'],
                'nominal' => $validated['nominal'],
                'bukti_transfer' => $buktiPath,
                'status' => 'berhasil',
                'keterangan' => $validated['keterangan'],
                'created_by' => auth()->id()
            ]);

            $pengajuan->update(['status_persetujuan' => 'dicairkan']);
            DB::commit();
            return redirect()->back()->with('success', 'Dana berhasil dicairkan dan dicatat ke Realisasi');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencairkan dana: ' . $e->getMessage());
        }
    }

    public function downloadProposal(ProgramKegiatanProdi $pengajuan)
    {
        if (!$pengajuan->proposal || !Storage::disk('public')->exists($pengajuan->proposal)) {
            return back()->with('error', 'File proposal tidak ditemukan');
        }
        return Storage::disk('public')->download($pengajuan->proposal);
    }
}
