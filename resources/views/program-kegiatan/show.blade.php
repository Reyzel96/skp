@extends('layouts.app')

@section('page-title', 'Detail Program Fakultas')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
@php
    $user = auth()->user();
    // Roles that can see ALL prodi allocations
    $canSeeAll = $user->hasAnyRole(['Admin Keuangan Fakultas', 'Super Admin', 'Dekan', 'Wadek 1', 'Wadek 2', 'Rektorat']);
    
    // Identify user's prodi if they are from a specific prodi
    $userProdi = null;
    if (!$canSeeAll) {
        foreach ($user->getRoleNames() as $role) {
            $lowerRole = strtolower($role);
            if (str_contains($lowerRole, 'informatika')) $userProdi = 'Informatika';
            elseif (str_contains($lowerRole, 'sipil')) $userProdi = 'Sipil';
            elseif (str_contains($lowerRole, 'mesin')) $userProdi = 'Mesin';
            elseif (str_contains($lowerRole, 'perkapalan')) $userProdi = 'Perkapalan';
            elseif (str_contains($lowerRole, 'industri')) $userProdi = 'Industri';
            elseif (str_contains($lowerRole, 'elektro') || str_contains($lowerRole, 'eletreo')) $userProdi = 'Eletreo';
            elseif (str_contains($lowerRole, 'arsitek')) $userProdi = 'Arsite'; // matches Arsiterktur/Arsitektur
        }
    }
@endphp

<style>
    .detail-value {
        font-size: 11px;
        font-weight: 700;
        line-height: 1.4;
        color: #1e293b;
        padding: 8px;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        min-height: 34px;
        display: block;
        width: 100%;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>

<div class="w-full mx-auto pb-20 px-4 md:px-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Detail <span class="text-blue-600">Program Fakultas</span></h2>
            <p class="text-gray-500 text-sm font-bold mt-1 uppercase tracking-widest">Informasi Katalog Program & Kegiatan Anggaran</p>
        </div>
        <div class="flex items-center gap-3">
            @if(auth()->user()->hasAnyRole(['Admin Keuangan Fakultas']))
            <a href="{{ route('program-kegiatan.edit', $program->id) }}" 
                class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-wider shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Data
            </a>
            @endif
            <a href="{{ route('program-kegiatan.index') }}" class="bg-white text-gray-700 px-6 py-2.5 rounded-xl font-bold border-2 border-gray-200 hover:bg-gray-50 transition shadow-sm text-xs uppercase">Kembali</a>
        </div>
    </div>

    {{-- Main Table Section --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-600"></i>
                <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Rincian Informasi Program</h3>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-100 flex items-center gap-2">
                    <span class="text-[9px] font-black text-indigo-400 uppercase tracking-tighter">TA:</span>
                    <span class="text-[10px] font-black text-indigo-800">{{ $program->tahun_anggaran }}</span>
                </div>
                <div class="bg-blue-50 px-3 py-1 rounded-lg border border-blue-100 flex items-center gap-2">
                    <span class="text-[9px] font-black text-blue-400 uppercase tracking-tighter">Akademik:</span>
                    <span class="text-[10px] font-black text-blue-800">{{ $program->tahun_akademik }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse table-auto">
                <thead>
                    <tr class="bg-gray-50 text-[9px] font-black text-gray-500 uppercase tracking-tight border-b border-gray-200">
                        <th class="px-2 py-3 min-w-[100px] border-r border-gray-200">Unit Kerja</th>
                        <th class="px-2 py-3 min-w-[100px] border-r border-gray-200">Sasaran</th>
                        <th class="px-2 py-3 min-w-[120px] border-r border-gray-200">Nama Program</th>
                        <th class="px-2 py-3 min-w-[80px] border-r border-gray-200">Jenis Kegiatan</th>
                        <th class="px-2 py-3 min-w-[150px] border-r border-gray-200 bg-blue-50/30 text-blue-800">Nama Kegiatan</th>
                        <th class="px-2 py-3 min-w-[100px] border-r border-gray-200">Sub Kegiatan</th>
                        <th class="px-2 py-3 min-w-[70px] text-right border-r border-gray-200">Per Mhs</th>
                        <th class="px-2 py-3 min-w-[100px] text-right border-r border-gray-200 bg-emerald-50/30 text-emerald-800">Anggaran</th>
                        <th class="px-2 py-3 min-w-[120px] text-center">Per Prodi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr class="hover:bg-blue-50/5 transition-colors">
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value">{{ $program->unit_kerja }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value">{{ $program->sasaran ?? '-' }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value">{{ $program->nama_program }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value text-center">{{ $program->jenis_kegiatan ?? '-' }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100 bg-blue-50/5">
                            <div class="detail-value text-blue-900 border-blue-100">{{ $program->nama_kegiatan }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value">{{ $program->sub_kegiatan ?? '-' }}</div>
                        </td>
                        <td class="p-1 border-r border-gray-100">
                            <div class="detail-value text-right font-mono italic text-gray-500">
                                Rp {{ number_format($program->per_mhs, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="p-1 border-r border-gray-100 bg-emerald-50/5">
                            <div class="detail-value text-right text-emerald-700 border-emerald-100 font-mono">
                                Rp {{ number_format($program->anggaran_per_prodi, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="p-1">
                            <div class="flex flex-col gap-1 min-w-[160px]">
                                @if($program->tipe_alokasi === 'Rata')
                                    <div class="bg-blue-50 text-blue-700 border border-blue-100 rounded px-2 py-1.5 text-center">
                                        <span class="text-[8px] font-black uppercase block mb-0.5">Sama Rata</span>
                                        <span class="text-[10px] font-black font-mono text-blue-800">Rp {{ number_format($program->per_prodi, 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div class="bg-orange-50 text-orange-700 border border-orange-100 rounded px-2 py-2 shadow-sm">
                                        <span class="text-[8px] font-black uppercase block mb-1.5 text-center border-b border-orange-200 pb-1">Custom Distribusi</span>
                                        <div class="flex flex-col gap-1 max-h-[200px] overflow-y-auto custom-scrollbar pr-1">
                                            @if($program->alokasi_custom)
                                                @php $foundAllocation = false; @endphp
                                                @foreach($program->alokasi_custom as $prodi => $nilai)
                                                    @if($canSeeAll || ($userProdi && str_contains(strtolower($prodi), strtolower($userProdi))))
                                                        @php $foundAllocation = true; @endphp
                                                        <div class="flex justify-between items-center gap-2 text-[9px]">
                                                            <span class="font-bold truncate" title="{{ $prodi }}">{{ $prodi }}:</span>
                                                            <span class="font-mono whitespace-nowrap">Rp{{ number_format($nilai, 0, ',', '.') }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if(!$foundAllocation)
                                                    <span class="text-[9px] font-bold italic text-center text-orange-400">Bukan Alokasi Prodi Anda</span>
                                                @endif
                                            @else
                                                <span class="text-[9px] font-bold italic text-center">Data Custom Kosong</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Secondary Info: Waktu --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex items-center gap-3">
            <i class="fas fa-clock text-blue-600 text-xs"></i>
            <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Waktu</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Tahun Anggaran</label>
                    <div class="detail-value text-center font-mono">{{ $program->tahun_anggaran }}</div>
                </div>
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Tahun Akademik</label>
                    <div class="detail-value text-center">{{ $program->tahun_akademik }}</div>
                </div>
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Dibuat Oleh</label>
                    <div class="detail-value text-center text-blue-600 truncate" title="{{ $program->creator->name ?? 'Sistem' }}">
                        {{ $program->creator->name ?? 'Sistem' }}
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Tanggal Input</label>
                    <div class="detail-value text-center text-[10px]">{{ $program->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="space-y-2 lg:col-span-1">
                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest ml-1">Update Terakhir</label>
                    <div class="detail-value text-left text-[10px] px-3 flex items-center gap-2">
                        <i class="fas fa-history text-gray-300"></i>
                        {{ $program->updated_at->format('d/m/Y • H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Alokasi Custom (Jika Ada) --}}
    @if($program->tipe_alokasi === 'Custom' && $program->alokasi_custom)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden animate-fadeIn">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex items-center gap-3">
            <i class="fas fa-chart-pie text-orange-600 text-xs"></i>
            <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest">
                {{ $canSeeAll ? 'Rincian Alokasi Per Prodi' : 'Alokasi Prodi Anda' }}
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php $foundAny = false; @endphp
                @foreach($program->alokasi_custom as $prodi => $nilai)
                    @if($canSeeAll || ($userProdi && str_contains(strtolower($prodi), strtolower($userProdi))))
                        @php $foundAny = true; @endphp
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 group hover:border-orange-200 transition-all shadow-sm">
                            <label class="block text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-2 group-hover:text-orange-600 transition-colors leading-tight">{{ $prodi }}</label>
                            <div class="text-right">
                                <span class="text-[10px] font-black text-gray-300">Rp</span>
                                <span class="text-sm font-mono font-black text-gray-800">{{ number_format($nilai, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach
                @if(!$foundAny)
                    <div class="col-span-full py-4 text-center text-gray-400 italic text-sm">
                        Data alokasi khusus untuk prodi Anda tidak ditemukan dalam kegiatan ini.
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.5s ease-out forwards; }
</style>
@endsection
