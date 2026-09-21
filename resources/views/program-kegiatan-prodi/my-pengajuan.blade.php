@extends('layouts.app')

@section('page-title', 'Usulan Kegiatan Prodi')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
    @php
        $user = auth()->user();
        // Identify user's prodi for budget filtering
        $userProdiBase = '';
        foreach ($user->getRoleNames() as $role) {
            $lowerRole = strtolower($role);
            if (str_contains($lowerRole, 'informatika')) $userProdiBase = 'Informatika';
            elseif (str_contains($lowerRole, 'sipil')) $userProdiBase = 'Sipil';
            elseif (str_contains($lowerRole, 'mesin')) $userProdiBase = 'Mesin';
            elseif (str_contains($lowerRole, 'perkapalan')) $userProdiBase = 'Perkapalan';
            elseif (str_contains($lowerRole, 'industri')) $userProdiBase = 'Industri';
            elseif (str_contains($lowerRole, 'elektro') || str_contains($lowerRole, 'eletreo')) $userProdiBase = 'Eletreo';
            elseif (str_contains($lowerRole, 'arsitek')) $userProdiBase = 'Arsite'; 
        }
    @endphp

    {{-- ALERT --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm animate-fadeIn">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm animate-fadeIn">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
        </div>
    @endif

    <div x-data="{ tab: 'tersedia' }">
        {{-- HEADER CONTENT --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
            <div class="w-full md:w-auto">
                <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Usulan Program <span class="text-blue-600">Kegiatan Prodi</span></h2>
                <div class="flex items-center gap-3 mt-2">
                    <p class="text-gray-500 text-sm font-medium">Pilih program dari Katalog Fakultas untuk diajukan oleh Program Studi</p>
                    @php $activePeriod = \App\Models\PeriodeAnggaran::where('status', 'aktif')->first(); @endphp
                    @if($activePeriod)
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">
                            Aktif: Tahun Anggaran {{ $activePeriod->tahun_anggaran }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- NAV TAB --}}
            <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-inner">
                <button @click="tab = 'tersedia'"
                        :class="tab === 'tersedia'
                            ? 'bg-white text-blue-600 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                    <i class="fas fa-university"></i> Program Fakultas
                </button>

                <button @click="tab = 'riwayat'"
                        :class="tab === 'riwayat'
                            ? 'bg-white text-blue-600 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                        class="px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                    <i class="fas fa-history"></i> Riwayat Usulan
                </button>
            </div>
        </div>

        {{-- ==========================================
             TAB 1: PROGRAM FAKULTAS (TERSEDIA)
        ========================================== --}}
        <div x-show="tab === 'tersedia'" x-transition class="animate-fadeIn">
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase w-10">No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Program & Kegiatan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Unit Kerja</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-blue-600 uppercase w-44">Alokasi Prodi</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-emerald-600 uppercase w-44 bg-emerald-50/50">Sisa Saldo</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @forelse($programTersedia as $prog)
                            @php
                                // 1. Hitung Alokasi Khusus Prodi Ini
                                $alokasiProdi = 0;
                                if ($prog->tipe_alokasi === 'Custom' && $prog->alokasi_custom) {
                                    foreach($prog->alokasi_custom as $pName => $val) {
                                        if ($userProdiBase && str_contains(strtolower($pName), strtolower($userProdiBase))) {
                                            $alokasiProdi = (int)$val;
                                            break;
                                        }
                                    }
                                } else {
                                    $alokasiProdi = (int)($prog->per_prodi > 0 ? $prog->per_prodi : $prog->anggaran_pagu);
                                }

                                // 2. Hitung Total yang sudah diajukan (Kecuali yang ditolak)
                                $totalDiajukan = $prog->programKegiatanProdi
                                    ->where('status_persetujuan', '!=', 'ditolak')
                                    ->sum('anggaran_usulan_prodi');
                                
                                $sisaSaldo = $alokasiProdi - $totalDiajukan;
                            @endphp
                            <tr class="hover:bg-blue-50/50 transition-colors cursor-default group {{ $loop->iteration % 2 == 0 ? 'bg-gray-50/50' : '' }}">
                                <td class="px-2 py-4 align-top text-center font-semibold text-gray-600">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors uppercase tracking-tight">{{ $prog->nama_kegiatan }}</div>
                                    <div class="text-xs font-medium text-blue-600 mt-1">{{ $prog->nama_program }}</div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">{{ $prog->tahun_akademik }}</span>
                                        @if($totalDiajukan > 0)
                                            <span class="text-[8px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-black uppercase">Sudah Diajukan</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="text-[10px] font-bold text-gray-500 uppercase leading-tight">
                                        {{ $prog->unit_kerja }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right align-top">
                                    <div class="text-sm font-mono font-bold text-blue-700">
                                        Rp {{ number_format($alokasiProdi, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right align-top bg-emerald-50/20">
                                    <div class="text-sm font-mono font-black {{ $sisaSaldo > 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                        Rp {{ number_format($sisaSaldo, 0, ',', '.') }}
                                    </div>
                                    @if($totalDiajukan > 0)
                                        <div class="text-[8px] font-bold text-gray-400 uppercase mt-1">Terpakai: Rp {{ number_format($totalDiajukan, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center align-middle">
                                    @if($sisaSaldo > 0)
                                        <a href="{{ route('kegiatan-prodi.create', $prog->id) }}"
                                           class="inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-md text-[10px] font-black uppercase tracking-wider w-full">
                                            <i class="fas fa-plus-circle"></i> Buat Usulan
                                        </a>
                                    @else
                                        <span class="text-[9px] font-black text-red-500 uppercase bg-red-50 px-3 py-2 rounded-lg block border border-red-100">Saldo Habis</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-500 italic">
                                    <i class="fas fa-folder-open text-3xl mb-3 text-gray-300 block"></i>
                                    Belum ada program fakultas tersedia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ==========================================
             TAB 2: RIWAYAT USULAN PROGRAM
        ========================================== --}}
        <div x-show="tab === 'riwayat'" x-transition class="animate-fadeIn" style="display:none;">
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Program Usulan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Implementasi</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Nominal Usulan</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-blue-600 uppercase bg-blue-50">Disetujui</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase bg-green-50/30">Realisasi</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @forelse($pengajuanList as $pengajuan)
                            <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer group"
                                data-turbo-row="{{ route('kegiatan-prodi.show', $pengajuan->id) }}">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors">{{ $pengajuan->programKegiatan?->nama_kegiatan ?? '⚠️ Program Master Telah Dihapus' }}</div>
                                    <div class="text-xs text-blue-600 font-medium mt-1">{{ $pengajuan->programKegiatan?->nama_program ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-4 text-center align-top font-medium text-gray-600">
                                    {{ $pengajuan->tanggal_implementasi->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-4 text-right align-top font-mono text-gray-700">
                                    Rp {{ number_format($pengajuan->anggaran_usulan_prodi, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-right align-top font-bold text-blue-700 bg-blue-50/30 font-mono">
                                    Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-right align-top font-bold text-green-700 bg-green-50/10 font-mono">
                                    Rp {{ number_format($pengajuan->getTotalRealisasi(), 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-center align-middle">
                                    @php
                                        $statusClass = match($pengajuan->status_persetujuan) {
                                            'disetujui' => 'bg-green-100 text-green-700 border-green-200',
                                            'ditolak'   => 'bg-red-100 text-red-700 border-red-200',
                                            'direvisi'  => 'bg-orange-100 text-orange-700 border-orange-200',
                                            default     => 'bg-gray-100 text-gray-700 border-gray-200'
                                        };
                                    @endphp
                                    <span class="px-3 py-1 rounded-full border {{ $statusClass }} text-[10px] font-black uppercase tracking-widest shadow-sm">
                                        {{ str_replace('_', ' ', $pengajuan->status_persetujuan) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-gray-500 italic">
                                    <i class="fas fa-folder-open text-3xl mb-3 text-gray-300 block"></i>
                                    Belum ada riwayat pengajuan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
</style>
