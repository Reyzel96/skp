@extends('layouts.app')

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

    {{-- Pesan Notifikasi Success/Error Custom --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
            <span class="block font-bold mb-1"><i class="fas fa-exclamation-triangle mr-2"></i> Terdapat Kesalahan:</span>
            <ul class="list-disc list-inside text-sm ml-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Wrapper untuk Alpine.js Bulk Action --}}
    <div x-data="{
            selected: [],
            allIds: [{{ $kegiatanList->pluck('id')->join(',') }}],
            get allSelected() { return this.selected.length === this.allIds.length && this.allIds.length > 0; },
            toggleAll() {
                if (this.allSelected) {
                    this.selected = [];
                } else {
                    this.selected = [...this.allIds];
                }
            }
        }">

        {{-- HEADER CONTENT --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
            <div class="w-full md:w-auto">
                <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Master Program Fakultas</h2>
                <div class="flex items-center gap-3 mt-2">
                    <p class="text-gray-500 text-sm font-medium">Katalog Program yang tersedia untuk diajukan oleh Program Studi</p>
                    @php $activePeriod = \App\Models\PeriodeAnggaran::where('status', 'aktif')->first(); @endphp
                    @if($activePeriod)
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">
                            Aktif: Tahun Anggaran {{ $activePeriod->tahun_anggaran }}
                        </span>
                    @endif
                </div>
            </div>

            @if(auth()->user()->hasAnyRole(['Admin Keuangan Fakultas', 'Administrasi Prodi', 'Admin Keuangan']))
            <div class="flex flex-wrap md:flex-nowrap items-center gap-3 w-full md:w-auto">
                {{-- Filter Periode --}}
                <div class="flex-grow md:flex-grow-0">
                    <form action="{{ route('program-kegiatan.index') }}" method="GET" class="relative group">
                        <select name="periode_id" onchange="this.form.submit()" 
                            class="appearance-none pl-10 pr-10 py-2.5 w-full md:w-64 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-0 font-bold text-gray-700 bg-white transition-all text-sm cursor-pointer shadow-sm group-hover:border-gray-300">
                            <option value="">Semua Tahun Anggaran</option>
                            @foreach(\App\Models\PeriodeAnggaran::orderBy('tahun_anggaran', 'desc')->get() as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>
                                    Tahun Anggaran {{ $p->tahun_anggaran }} ({{ $p->tahun_akademik }})
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar-alt text-gray-400"></i>
                        </div>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                        </div>
                    </form>
                </div>

                <div class="flex items-center gap-2">
                    {{-- TOMBOL HAPUS MASSAL --}}
                    <form id="bulk-delete-form" action="{{ route('program-kegiatan.bulk-destroy') ?? '#' }}" method="POST" x-show="selected.length > 0" class="m-0" x-transition>
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="submit" onclick="return confirm('Anda yakin ingin menghapus semua data yang dipilih?')" 
                            class="flex items-center justify-center gap-2 bg-red-600 text-white px-5 py-2.5 rounded-xl hover:bg-red-700 transition-all shadow-md text-sm font-black uppercase tracking-wider">
                            <i class="fas fa-trash-alt"></i> Hapus (<span x-text="selected.length"></span>)
                        </button>
                    </form>

                    <a href="{{ route('program-kegiatan.create') }}" 
                        class="flex items-center justify-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all shadow-md text-sm font-black uppercase tracking-wider">
                        <i class="fas fa-plus"></i> Tambah
                    </a>
                </div>
            </div>
            @endif
        </div>

        {{-- TABLE CARD --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(auth()->user()->hasAnyRole(['Admin Keuangan Fakultas']))
                            <th class="px-4 py-3 text-center w-12">
                                <input type="checkbox" @click="toggleAll" :checked="allSelected" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer w-4 h-4">
                            </th>
                            @endif
                            <th class="px-2 py-3 text-center text-xs font-bold text-gray-500 uppercase w-10">No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Unit & Program</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Detail Kegiatan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pelaksanaan & Target</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Anggaran Pagu</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Per Prodi</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($kegiatanList as $kegiatan)
                        <tr class="hover:bg-blue-50/50 transition-colors cursor-pointer group {{ $loop->iteration % 2 == 0 ? 'bg-gray-50/50' : '' }}" 
                            data-turbo-row="{{ route('program-kegiatan.show', $kegiatan->id) }}">

                            {{-- 0. Checkbox Massal --}}
                            @if(auth()->user()->hasAnyRole(['Admin Keuangan Fakultas']))
                            <td class="px-4 py-4 align-top text-center no-click">
                                <input type="checkbox" x-model="selected" value="{{ $kegiatan->id }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer w-4 h-4">
                            </td>
                            @endif

                            {{-- 1. NO Iterasi --}}
                            <td class="px-2 py-4 align-top text-center text-sm font-semibold text-gray-600">
                                {{ $loop->iteration }}
                            </td>

                            {{-- 2. Unit & Program --}}
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">{{ $kegiatan->unit_kerja }}</div>
                                <div class="text-sm font-medium text-blue-600 mt-1">{{ $kegiatan->nama_program }}</div>
                            </td>

                            {{-- 3. Detail Kegiatan --}}
                            <td class="px-4 py-4 align-top">
                                <div class="text-gray-900 font-medium group-hover:text-blue-700 transition-colors">{{ $kegiatan->nama_kegiatan }}</div>
                            </td>

                            {{-- 4. Pelaksanaan & Target --}}
                            <td class="px-4 py-4 align-top">
                                <div class="text-xs text-gray-700 mb-1">
                                    <span class="font-bold text-gray-900 uppercase tracking-tighter text-[9px]">Smt / Waktu:</span>
                                    <span class="ml-1">{{ $kegiatan->semester ?? '-' }} / {{ $kegiatan->waktu_pelaksanaan ?? '-' }}</span>
                                </div>
                                <div class="text-xs text-gray-700 mb-1">
                                    <span class="font-bold text-gray-900 uppercase tracking-tighter text-[9px]">Tahun Anggaran:</span>
                                    <span class="ml-1 text-blue-700 font-bold">{{ $kegiatan->tahun_anggaran }}</span>
                                </div>
                                <div class="text-[10px] text-gray-500 italic leading-tight">
                                    {{ $kegiatan->tahun_akademik }}
                                </div>
                            </td>

                            {{-- 5. Anggaran Pagu --}}
                            <td class="px-4 py-4 text-right align-top">
                                <div class="text-sm font-mono font-bold text-blue-700 bg-blue-50 group-hover:bg-blue-100/50 inline-block px-2 py-1 rounded whitespace-nowrap transition-colors">
                                    Rp {{ number_format($kegiatan->anggaran_pagu, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- 6. Per Prodi --}}
                            <td class="px-4 py-4 text-right align-top whitespace-nowrap">
                                @if($kegiatan->tipe_alokasi === 'Rata' && $kegiatan->per_prodi)
                                    <div class="text-sm font-mono text-gray-700 font-semibold">
                                        Rp {{ number_format($kegiatan->per_prodi, 0, ',', '.') }}
                                    </div>
                                @elseif($kegiatan->tipe_alokasi === 'Custom' && $kegiatan->alokasi_custom)
                                    @php 
                                        $customVal = 0;
                                        if ($userProdi) {
                                            foreach($kegiatan->alokasi_custom as $p => $v) {
                                                if(str_contains(strtolower($p), strtolower($userProdi))) {
                                                    $customVal = $v;
                                                    break;
                                                }
                                            }
                                        }
                                        $totalCustom = array_sum($kegiatan->alokasi_custom);
                                    @endphp
                                    @if($canSeeAll)
                                        <div class="text-[9px] font-black text-orange-600 uppercase tracking-tighter">Custom (Total)</div>
                                        <div class="text-sm font-mono text-gray-700 font-semibold">
                                            Rp {{ number_format($totalCustom, 0, ',', '.') }}
                                        </div>
                                    @elseif($userProdi)
                                        <div class="text-[9px] font-black text-orange-600 uppercase tracking-tighter">Alokasi Prodi</div>
                                        <div class="text-sm font-mono text-gray-700 font-semibold">
                                            Rp {{ number_format($customVal, 0, ',', '.') }}
                                        </div>
                                    @else
                                         <span class="text-gray-400 italic text-xs">Custom</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 italic text-xs">-</span>
                                @endif
                            </td>

                            {{-- 7. Aksi (Edit & Hapus) --}}
                            <td class="px-4 py-4 text-center align-middle no-click">
                                @if(auth()->user()->hasAnyRole(['Admin Keuangan Fakultas']))
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Tombol Edit --}}
                                        <a href="{{ route('program-kegiatan.edit', $kegiatan->id) }}" class="text-blue-500 hover:text-blue-700 transition-colors p-2 bg-blue-50 rounded hover:bg-blue-100" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        {{-- Tombol Hapus Tunggal --}}
                                        <form action="{{ route('program-kegiatan.destroy', $kegiatan->id) }}" method="POST" class="inline m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus program master ini?')" class="text-red-500 hover:text-red-700 transition-colors p-2 bg-red-50 rounded hover:bg-red-100" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-[10px] bg-gray-100 px-2 py-1 rounded text-gray-400 italic">No Action</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500 italic">
                                <i class="fas fa-folder-open text-3xl mb-3 text-gray-300 block"></i>
                                Belum ada Katalog Program Fakultas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div> {{-- Tutup div x-data --}}

@endsection
