@extends('layouts.app')

@section('page-title', 'Detail Usulan Prodi')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
<style>
    .detail-table th {
        background-color: #f9fafb;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .detail-table td {
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: top;
    }
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .mini-table th {
        background-color: #f8fafc;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .mini-table td {
        padding: 1rem;
        border-bottom: 1px solid #f8fafc;
        font-size: 11px;
    }
    /* Hide number spinners globally in this page */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<div class="w-full mx-auto pb-20 px-4 md:px-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6 text-left">
        <div>
            <a href="{{ url()->previous() }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-widest flex items-center gap-2 mb-4 transition-all">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Detail <span class="text-blue-600">Usulan Prodi</span></h2>
            <p class="text-gray-500 text-sm font-bold mt-1 uppercase tracking-widest">Master: {{ $pengajuan->programKegiatan->nama_kegiatan ?? '-' }}</p>
        </div>
        <div class="flex items-center gap-3">
            @php
                $statusClass = match($pengajuan->status_persetujuan) {
                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    'disetujui_dekan' => 'bg-blue-100 text-blue-800 border-blue-200',
                    'disetujui_wadek' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                    'dicairkan' => 'bg-green-100 text-green-800 border-green-200',
                    'selesai' => 'bg-gray-200 text-gray-800 border-gray-300',
                    'ditolak' => 'bg-red-100 text-red-800 border-red-200',
                    default => 'bg-gray-100 text-gray-800 border-gray-200'
                };
            @endphp
            <div class="px-6 py-2.5 rounded-xl border {{ $statusClass }} font-black text-xs uppercase tracking-widest shadow-sm">
                Status: {{ str_replace('_', ' ', $pengajuan->status_persetujuan) }}
            </div>

            {{-- ACTION BUTTONS --}}
            @if(auth()->user()->hasRole('Dekan') && $pengajuan->status_persetujuan === 'pending')
                <button onclick="openAccProdiModal({{ $pengajuan->id }}, {{ $pengajuan->anggaran_usulan_prodi }})"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 text-xs rounded-xl font-black shadow-lg shadow-blue-600/20 transition-all uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Setujui Usulan Program
                </button>
            @endif

            @if(auth()->user()->hasRole('Wadek 2') && $pengajuan->status_persetujuan === 'disetujui_dekan')
                <button onclick="openApprovalModal({{ $pengajuan->id }}, {{ $pengajuan->anggaran_disetujui_prodi }}, {{ $pengajuan->wadek_can_edit ? 'true' : 'false' }})"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 text-xs rounded-xl font-black shadow-lg shadow-indigo-600/20 transition-all uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> ACC Wadek 2
                </button>
            @endif

            @if(auth()->user()->hasRole('Admin Keuangan Fakultas'))
                @if(in_array($pengajuan->status_persetujuan, ['disetujui_wadek', 'dicairkan']))
                    @php
                        $cairSblmnya = method_exists($pengajuan, 'getTotalRealisasi') ? $pengajuan->getTotalRealisasi() : 0;
                        $sisaPencairan = $pengajuan->anggaran_disetujui_prodi - $cairSblmnya;
                    @endphp

                    @if($sisaPencairan > 0)
                        <button onclick="openRealisasiModal({{ $pengajuan->id }}, {{ $sisaPencairan }})"
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 text-xs rounded-xl font-black shadow-lg shadow-green-600/20 transition-all uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-money-bill-wave"></i> Cairkan Dana
                        </button>
                    @endif
                @endif
            @endif
        </div>
    </div>

    {{-- 1. MAIN DETAIL TABLE --}}
    <div class="bg-white border border-gray-200 rounded-[2.5rem] shadow-xl overflow-hidden mb-12">
        <div class="bg-gray-50 border-b border-gray-100 px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-600"></i>
                <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest text-left">Informasi Usulan</h3>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse detail-table">
                <thead>
                    <tr>
                        <th class="w-[180px] border-r border-gray-200">Program Studi</th>
                        <th class="min-w-[250px] border-r border-gray-200 bg-blue-50/30 text-blue-800">Nama Usulan Program</th>
                        <th class="w-[180px] border-r border-gray-200">Tanggal Implementasi</th>
                        <th class="min-w-[300px] border-r border-gray-200">Indikator Keberhasilan</th>
                        <th class="w-[200px] text-right bg-emerald-50/30 text-emerald-800">Usulan Anggaran (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-white">
                        <td class="border-r border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-university text-gray-300"></i>
                                <span class="font-black text-gray-700 text-xs uppercase tracking-tight">{{ $pengajuan->prodi->nama ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="border-r border-gray-100 bg-blue-50/5">
                            <span class="font-bold text-blue-900 text-sm leading-tight block">{{ $pengajuan->nama_kegiatan_prodi }}</span>
                        </td>
                        <td class="border-r border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-blue-400"></i>
                                <span class="font-black text-gray-600 text-xs font-mono">{{ $pengajuan->tanggal_implementasi->format('d F Y') }}</span>
                            </div>
                        </td>
                        <td class="border-r border-gray-100">
                            <div class="text-xs text-gray-600 leading-relaxed font-medium italic">
                                {!! nl2br(e($pengajuan->indikator_keberhasilan)) !!}
                            </div>
                        </td>
                        <td class="bg-emerald-50/10 text-right">
                            <div class="text-xl font-black text-emerald-800 font-mono">
                                Rp {{ number_format($pengajuan->anggaran_usulan_prodi, 0, ',', '.') }}
                            </div>
                            @if($pengajuan->anggaran_disetujui_prodi > 0)
                                <div class="mt-2 pt-2 border-t border-emerald-100">
                                    <span class="text-[8px] font-black text-blue-500 uppercase block tracking-widest mb-1">Disetujui Dekan:</span>
                                    <span class="text-sm font-black text-blue-700 font-mono">Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 2. DOKUMEN PROPOSAL --}}
    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden mb-12">
        <div class="bg-gray-50/50 border-b border-gray-100 px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3 text-left">
                <i class="fas fa-file-alt text-blue-600"></i>
                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Dokumen Proposal</h3>
            </div>
            @if($pengajuan->proposal_type === 'file')
                <a href="{{ route('kegiatan-prodi.download-proposal', $pengajuan->id) }}" class="text-blue-600 hover:text-blue-800 text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            @endif
        </div>
        <div class="p-8 md:p-10 text-left">
            @if($pengajuan->proposal_type === 'manual')
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! $pengajuan->proposal !!}
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <i class="fas fa-file-pdf text-5xl mb-4 text-red-100"></i>
                    <p class="text-[10px] font-black uppercase tracking-widest">Proposal Berbentuk File PDF</p>
                    <p class="text-[9px] font-bold mt-1">Silakan download file melalui tombol di atas</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 text-left">
        {{-- 3. TRACKING PERSETUJUAN TABLE --}}
        <div class="bg-white border border-gray-200 rounded-[2rem] shadow-xl overflow-hidden">
            <div class="bg-gray-900 px-8 py-4 flex items-center gap-3">
                <i class="fas fa-tasks text-blue-400 text-xs"></i>
                <h3 class="text-[10px] font-black text-gray-100 uppercase tracking-widest">Tracking Persetujuan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full mini-table">
                    <thead>
                        <tr>
                            <th>Tahap / Jabatan</th>
                            <th>Nama Pejabat</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Step 1: Dekan --}}
                        <tr>
                            <td class="font-bold text-gray-800">Persetujuan Dekan</td>
                            <td class="text-gray-600 font-semibold">{{ $pengajuan->dekanApprover->name ?? '-' }}</td>
                            <td class="text-center">
                                @if($pengajuan->approved_by_dekan)
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[9px] font-black uppercase">Approved</span>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded-full text-[9px] font-black uppercase">Pending</span>
                                @endif
                            </td>
                            <td class="text-right text-gray-400 font-mono text-[10px]">
                                {{ $pengajuan->approved_by_dekan ? $pengajuan->updated_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                        {{-- Step 2: Wadek 2 --}}
                        <tr>
                            <td class="font-bold text-gray-800">Verifikasi Wadek 2</td>
                            <td class="text-gray-600 font-semibold">{{ $pengajuan->wadekApprover->name ?? '-' }}</td>
                            <td class="text-center">
                                @if($pengajuan->approved_by_wadek)
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[9px] font-black uppercase">Verified</span>
                                @elseif($pengajuan->status_persetujuan === 'disetujui_wadek' && !$pengajuan->approved_by_wadek)
                                    {{-- Case bypassed by dekan --}}
                                    <span class="bg-blue-50 text-blue-500 px-2 py-1 rounded-full text-[9px] font-black uppercase italic">Bypassed</span>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded-full text-[9px] font-black uppercase">Pending</span>
                                @endif
                            </td>
                            <td class="text-right text-gray-400 font-mono text-[10px]">
                                {{ $pengajuan->approved_by_wadek ? $pengajuan->updated_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                        {{-- Step 3: Admin --}}
                        <tr>
                            <td class="font-bold text-gray-800">Finalisasi Admin</td>
                            <td class="text-gray-600 font-semibold">{{ $pengajuan->adminApprover->name ?? '-' }}</td>
                            <td class="text-center">
                                @if($pengajuan->approved_by_admin)
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[9px] font-black uppercase">Finished</span>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded-full text-[9px] font-black uppercase">Waiting</span>
                                @endif
                            </td>
                            <td class="text-right text-gray-400 font-mono text-[10px]">
                                {{ $pengajuan->approved_at ? $pengajuan->approved_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if($pengajuan->catatan)
                <div class="px-8 py-4 bg-gray-50 border-t border-gray-100">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Catatan Terakhir:</label>
                    <p class="text-xs text-gray-600 italic">"{{ $pengajuan->catatan }}"</p>
                </div>
            @endif
        </div>

        {{-- 4. RIWAYAT PENCAIRAN TABLE --}}
        <div class="bg-white border border-gray-200 rounded-[2rem] shadow-xl overflow-hidden">
            <div class="bg-emerald-900 px-8 py-4 flex items-center gap-3">
                <i class="fas fa-receipt text-emerald-400 text-xs"></i>
                <h3 class="text-[10px] font-black text-gray-100 uppercase tracking-widest">Riwayat Pencairan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full mini-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-right">Nominal</th>
                            <th>Keterangan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pengajuan->realisasi as $cair)
                            <tr>
                                <td class="font-mono text-gray-600">{{ $cair->tanggal_pencairan->format('d/m/Y') }}</td>
                                <td class="text-right font-black text-emerald-700 font-mono">
                                    Rp {{ number_format($cair->nominal, 0, ',', '.') }}
                                </td>
                                <td class="text-gray-500 italic">{{ $cair->keterangan ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter">Berhasil</span>
                                </td>
                            </tr>
@empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-400 italic">
                                    Belum ada data pencairan dana.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($pengajuan->realisasi->count() > 0)
                    <tfoot class="bg-emerald-50/50">
                        <tr>
                            <td colspan="2" class="text-right font-black text-emerald-900 text-sm py-4 border-t-2 border-emerald-100">
                                Total Cair: Rp {{ number_format($pengajuan->getTotalRealisasi(), 0, ',', '.') }}
                            </td>
                            <td colspan="2" class="border-t-2 border-emerald-100"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODALS SECTION --}}

{{-- MODAL ACC DEKAN --}}
<div id="accProdiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left transform transition-all animate-fadeIn">
            <h3 class="text-lg font-black text-gray-900 mb-4 uppercase tracking-tight">Persetujuan Usulan Prodi (Dekan)</h3>
            <form id="accForm" method="POST">
                @csrf @method('PATCH')
                <div class="space-y-4">
                    {{-- 1. Tindakan (Pindah ke Atas) --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tindakan</label>
                        <select name="action" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                            <option value="setuju">Setujui Usulan</option>
                            <option value="lanjut_wadek">Lanjutkan Review ke Wadek 2</option>
                            <option value="tolak">Tolak Usulan</option>
                        </select>
                    </div>

                    {{-- 2. Nominal Disetujui --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Disetujui (Pagu Akhir)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                            <input type="number" name="anggaran_disetujui_prodi" id="inputPaguProdi" required
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border-gray-200 rounded-xl font-black text-lg focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                    </div>

                    {{-- 3. Catatan --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan (Opsional)</label>
                        <textarea name="catatan" rows="3" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500" placeholder="Berikan alasan jika ditolak atau arahan tambahan..."></textarea>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('accProdiModal')" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL ACC WADEK 2 --}}
<div id="approvalModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left transform transition-all animate-fadeIn">
            <h3 class="text-lg font-black text-gray-900 mb-4 uppercase tracking-tight font-black">Verifikasi Usulan (Wadek 2)</h3>
            <form id="approvalForm" method="POST">
                @csrf @method('PATCH')
                <div class="space-y-4">
                    {{-- Tindakan --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tindakan</label>
                        <select name="action" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                            <option value="setuju">Verifikasi & Lanjutkan</option>
                            <option value="tolak">Tolak / Kembalikan</option>
                        </select>
                    </div>

                    {{-- Nominal --}}
                    <div id="wadekNominalContainer">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Anggaran</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                            <input type="number" name="anggaran_disetujui_prodi" id="inputWadekPagu" required
                                class="w-full pl-10 pr-4 py-3 border-gray-200 rounded-xl font-black text-lg focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                        <p id="wadekEditHint" class="text-[9px] font-bold text-gray-400 mt-1 uppercase italic hidden">* Nominal dikunci oleh Dekan (Read-only)</p>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan Verifikasi</label>
                        <textarea name="catatan" rows="3" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Tambahkan instruksi atau catatan..."></textarea>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('approvalModal')" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg">Kirim Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REALISASI (CAIRKAN) --}}
<div id="realisasiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Pencairan Dana Usulan</h3>
                <button onclick="closeModal('realisasiModal')" class="text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="mb-6 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest block mb-1">Maksimal Sisa Pagu:</span>
                <span id="textSisaPagu" class="text-2xl font-black text-emerald-800">Rp 0</span>
            </div>

            <form id="realisasiForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Cair</label>
                            <input type="date" name="tanggal_pencairan" value="{{ date('Y-m-d') }}" required
                                class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Cair (Rp)</label>
                            <input type="number" name="nominal" id="inputNominalCair" required readonly
                                class="w-full p-3 bg-gray-100 border-gray-200 rounded-xl font-black text-sm text-gray-500 cursor-not-allowed [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <p class="text-[8px] font-bold text-gray-400 mt-1 uppercase italic">* Nominal dikunci sesuai verifikasi Dekan/Wadek</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Upload Bukti Transfer</label>
                        <input type="file" name="bukti_transfer" accept="image/*,application/pdf"
                            class="w-full p-2 bg-gray-50 border-gray-200 rounded-xl text-xs font-bold focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Keterangan / Berita Acara</label>
                        <textarea name="keterangan" rows="2" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500"></textarea>
                    </div>
                </div>
                <div class="mt-8">
                    <button type="submit" class="w-full px-4 py-4 bg-green-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-green-700 transition shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Konfirmasi Pencairan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function openAccProdiModal(id, usulanAwal) {
        document.getElementById('accForm').action = `/kegiatan-prodi/${id}/approve`;
        document.getElementById('inputPaguProdi').value = usulanAwal;
        document.getElementById('accProdiModal').classList.remove('hidden');
    }

    function openApprovalModal(id, nominal, canEdit) {
        document.getElementById('approvalForm').action = `/kegiatan-prodi/${id}/approve`;
        const input = document.getElementById('inputWadekPagu');
        const hint = document.getElementById('wadekEditHint');
        
        input.value = nominal;
        if (canEdit) {
            input.readOnly = false;
            input.classList.remove('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
            input.classList.add('bg-gray-50');
            hint.classList.add('hidden');
        } else {
            input.readOnly = true;
            input.classList.add('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
            input.classList.remove('bg-gray-50');
            hint.classList.remove('hidden');
        }
        
        document.getElementById('approvalModal').classList.remove('hidden');
    }

    function openRealisasiModal(id, sisaMax) {
        document.getElementById('realisasiForm').action = `/kegiatan-prodi/${id}/cairkan`;
        const inputCair = document.getElementById('inputNominalCair');
        inputCair.max = sisaMax;
        inputCair.value = sisaMax;
        document.getElementById('textSisaPagu').innerText = 'Rp ' + sisaMax.toLocaleString('id-ID');
        document.getElementById('realisasiModal').classList.remove('hidden');
    }
</script>
@endsection
