<?php

namespace App\Http\Controllers;

use App\Models\Lpj;
use App\Models\ProgramKegiatanProdi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LpjController extends Controller
{
    private function hasAksesPengajuan($user)
    {
        return $user->roles->filter(function($role) {
            return str_starts_with($role->name, 'Kaprodi') ||
                   str_starts_with($role->name, 'Sekprodi') ||
                   str_starts_with($role->name, 'Kepala UPF') ||
                   $role->name === 'Administrasi Prodi';
        })->isNotEmpty();
    }

    public function monitoring()
    {
        $user = auth()->user();
        if ($this->hasAksesPengajuan($user)) return redirect()->route('lpj.my-lpj');

        $pengajuanList = ProgramKegiatanProdi::with(['user.profile', 'programKegiatan', 'daftarLpj'])
            ->whereIn('status_persetujuan', ['dicairkan', 'selesai'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('lpj.monitoring', compact('pengajuanList'));
    }

    public function monitorDetail(ProgramKegiatanProdi $pengajuan)
    {
        $user = auth()->user();
        // Cek apakah user memiliki akses (Admin Keuangan, Dekan, Wadek 2, Rektorat)
        if ($this->hasAksesPengajuan($user)) abort(403);
        if (!$user->hasAnyRole(['Admin Keuangan Fakultas', 'Dekan', 'Wadek 2', 'Rektorat'])) abort(403);

        $pengajuan->load(['user.profile', 'programKegiatan', 'daftarLpj' => fn($q) => $q->orderBy('tanggal_kuitansi', 'asc')]);

        return view('lpj.monitor-detail', compact('pengajuan'));
    }

    public function myLpj()
    {
        $user = auth()->user();
        if (!$this->hasAksesPengajuan($user)) abort(403);

        $pengajuanList = ProgramKegiatanProdi::with(['programKegiatan', 'daftarLpj' => fn($q) => $q->orderBy('created_at', 'desc')])
            ->where('user_id', $user->id)
            ->whereIn('status_persetujuan', ['dicairkan', 'selesai'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('lpj.my-lpj', compact('pengajuanList'));
    }

    public function create(ProgramKegiatanProdi $pengajuan)
    {
        $user = auth()->user();
        if (!$this->hasAksesPengajuan($user)) abort(403, 'Unauthorized');
        if ($pengajuan->user_id !== $user->id) abort(403, 'Bukan pengajuan Anda');

        if ($pengajuan->status_persetujuan !== 'dicairkan' && $pengajuan->status_persetujuan !== 'selesai') {
            return back()->with('error', 'Pengajuan belum dicairkan');
        }

        if ($pengajuan->sisa_anggaran <= 0) {
            return back()->with('error', 'Dana anggaran sudah habis digunakan / di-LPJ-kan');
        }

        return view('lpj.create', compact('pengajuan'));
    }

    public function store(Request $request, ProgramKegiatanProdi $pengajuan)
    {
        $user = auth()->user();
        if ($pengajuan->user_id !== $user->id) abort(403);

        $validated = $request->validate([
            'item_pengeluaran'   => 'required|string|max:255',
            'nominal_penggunaan' => 'required|numeric|min:1',
            'tanggal_kuitansi'   => 'required|date',
            'keterangan'         => 'nullable|string',
            'bukti_pengeluaran'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        // LOGIKA BARU: Cek langsung dengan sisa_anggaran di database
        if ($validated['nominal_penggunaan'] > $pengajuan->sisa_anggaran) {
            return back()->with('error', 'Nominal melebihi sisa pagu: Rp ' . number_format($pengajuan->sisa_anggaran, 0, ',', '.'));
        }

        DB::beginTransaction();
        try {
            $buktiPath = $request->hasFile('bukti_pengeluaran') ? $request->file('bukti_pengeluaran')->store('lpj', 'public') : null;

            Lpj::create([
                'program_kegiatan_prodi_id' => $pengajuan->id,
                'item_pengeluaran'          => $validated['item_pengeluaran'],
                'nominal_penggunaan'        => $validated['nominal_penggunaan'],
                'tanggal_kuitansi'          => $validated['tanggal_kuitansi'],
                'keterangan'                => $validated['keterangan'],
                'bukti_pengeluaran'         => $buktiPath,
                'status_verifikasi'         => 'diverifikasi'
            ]);

            // LOGIKA BARU: Saldo langsung dipotong saat Kaprodi submit
            $pengajuan->decrement('sisa_anggaran', $validated['nominal_penggunaan']);

            DB::commit();
            return redirect()->route('lpj.my-lpj')->with('success', 'LPJ berhasil disubmit dan sisa anggaran telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($buktiPath) && Storage::disk('public')->exists($buktiPath)) Storage::disk('public')->delete($buktiPath);
            return back()->with('error', 'Gagal mengajukan LPJ: ' . $e->getMessage());
        }
    }

    public function show(Lpj $lpj)
    {
        $user = auth()->user();
        $lpj->load(['programKegiatanProdi.user.profile', 'programKegiatanProdi.programKegiatan', 'verifier']);

        $canView = ($lpj->programKegiatanProdi->user_id === $user->id) || $user->hasAnyRole(['Admin Keuangan Fakultas', 'Dekan', 'Wadek 2', 'Rektorat']);
        if (!$canView) abort(403, 'Unauthorized');

        return view('lpj.show', compact('lpj'));
    }

    public function verify(Request $request, Lpj $lpj)
    {
        $user = auth()->user();
        if (!$user->hasRole('Admin Keuangan Fakultas')) abort(403, 'Unauthorized');

        $validated = $request->validate([
            'action' => 'required|in:verifikasi,tolak',
            'catatan_verifikasi' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $status = $validated['action'] === 'tolak' ? 'ditolak' : 'diverifikasi';

            $lpj->update([
                'status_verifikasi'  => $status,
                'catatan_verifikasi' => $validated['catatan_verifikasi'],
                'verified_by'        => $user->id,
                'verified_at'        => now()
            ]);

            // LOGIKA BARU: Jika ditolak, kembalikan uangnya ke sisa_anggaran prodi
            if ($status === 'ditolak') {
                $lpj->programKegiatanProdi->increment('sisa_anggaran', $lpj->nominal_penggunaan);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Verifikasi LPJ berhasil diproses');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memverifikasi: ' . $e->getMessage());
        }
    }

    public function downloadBukti(Lpj $lpj)
    {
        $user = auth()->user();
        $canDownload = ($lpj->programKegiatanProdi->user_id === $user->id) || $user->hasAnyRole(['Admin Keuangan Fakultas', 'Dekan', 'Wadek 2', 'Rektorat']);

        if (!$canDownload) abort(403, 'Unauthorized');
        if (!$lpj->bukti_pengeluaran || !Storage::disk('public')->exists($lpj->bukti_pengeluaran)) return back()->with('error', 'File bukti tidak ditemukan');

        return Storage::disk('public')->download($lpj->bukti_pengeluaran, 'Bukti_' . str_replace(' ', '_', $lpj->item_pengeluaran) . '.' . pathinfo($lpj->bukti_pengeluaran, PATHINFO_EXTENSION));
    }

    public function destroy(Lpj $lpj)
    {
        if ($lpj->programKegiatanProdi->user_id !== auth()->id()) abort(403);
        
        // Cek jika status program induk sudah selesai, tidak boleh hapus
        if ($lpj->programKegiatanProdi->status_persetujuan === 'selesai') {
            return back()->with('error', 'Tidak bisa menghapus LPJ dari program yang sudah ditutup/selesai');
        }

        DB::beginTransaction();
        try {
            // LOGIKA BARU: Kembalikan saldo karena LPJ dibatalkan/dihapus oleh pengaju
            $lpj->programKegiatanProdi->increment('sisa_anggaran', $lpj->nominal_penggunaan);

            if ($lpj->bukti_pengeluaran && Storage::disk('public')->exists($lpj->bukti_pengeluaran)) {
                Storage::disk('public')->delete($lpj->bukti_pengeluaran);
            }
            $lpj->delete();

            DB::commit();
            return redirect()->back()->with('success', 'LPJ berhasil dihapus dan saldo telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus LPJ: ' . $e->getMessage());
        }
    }

    // LOGIKA BARU: Fungsi untuk menutup program jika anggaran sudah Rp 0
    public function tutup(ProgramKegiatanProdi $pengajuan)
    {
        if ($pengajuan->user_id !== auth()->id()) abort(403);

        if ($pengajuan->sisa_anggaran > 0) {
            return back()->with('error', 'LPJ belum bisa ditutup! Sisa anggaran harus Rp 0.');
        }

        // Pastikan tidak ada LPJ yang ditolak yang gantung, opsional bisa dihiraukan jika sudah 0.
        $pengajuan->update(['status_persetujuan' => 'selesai']);

        return back()->with('success', 'Selamat! Rangkaian LPJ Kegiatan telah ditutup (Selesai).');
    }
}
