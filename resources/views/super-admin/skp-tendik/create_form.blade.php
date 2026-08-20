<x-app-layout>
    @section('page-title', 'Penyusunan SKP Tendik')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            .table-input {
                width: 100%;
                border: 1px solid #d1d5db;
                padding: 6px;
                font-size: 0.75rem;
                background: transparent;
                border-radius: 4px;
                text-align: center;
                transition: all 0.2s ease;
            }

            .table-input:focus {
                outline: none;
                border-color: #3b82f6;
                background-color: #eff6ff;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            }

            /* PERBAIKAN: Menghilangkan panah (spinner) pada input bertipe number */
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            input[type=number] {
                -moz-appearance: textfield;
            }

            [x-cloak] { display: none !important; }
        </style>

        <div class="p-6 max-w-full mx-auto font-sans relative z-0" x-data="setupPage()">

            {{-- 1. HEADER & ACTIONS --}}
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tighter">Penyusunan <span class="text-blue-600">Target SKP</span></h1>
                    <p class="text-sm text-gray-500 font-bold">Periode: <span class="text-blue-700">{{ $selPeriode->tahun ?? '-' }}</span> | Status: <span class="uppercase">{{ $selPeriode->status ?? 'BELUM DIPILIH' }}</span></p>
                </div>
                <div class="flex gap-3">
                    <button @click="openSetupModal = true" class="bg-white border-2 border-blue-600 text-blue-600 px-5 py-2.5 rounded-xl text-xs font-black uppercase hover:bg-blue-50 transition shadow-sm">
                        <i class="fas fa-cog mr-2"></i> Ubah Target / Penilai
                    </button>
                    <a href="{{ route('superadmin.skp-tendik.index') }}" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase hover:bg-gray-200 transition">
                        <i class="fas fa-list mr-2"></i> Daftar SKP
                    </a>
                </div>
            </div>

            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 shadow-sm rounded-r-lg font-bold">
                    <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            {{-- 2. MODAL SETUP (TARGET & PENILAI) --}}
            <template x-teleport="body">
                {{-- PERBAIKAN: z-index ditambahkan !important dan overflow diatur ulang --}}
                <div x-show="openSetupModal" style="z-index: 999999 !important;" class="fixed inset-0 overflow-y-auto bg-gray-900/90 backdrop-blur-sm" x-cloak>
                    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                        {{-- PERBAIKAN: Kontainer Modal disesuaikan agar tidak terpotong (max-h-90vh) --}}
                        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-5xl w-full mx-auto transform transition-all max-h-[90vh] overflow-y-auto animate-in zoom-in duration-300 relative">

                            <div class="text-center mb-4 relative">
                                <h2 class="text-xl font-black text-gray-800">Target SKP Tendik</h2>
                                <p class="text-gray-500 text-xs mt-1">Lengkapi data periode, pegawai, dan atasan penilai.</p>
                                <a href="{{ route('superadmin.skp-tendik.index') }}" class="absolute top-0 right-0 text-gray-400 hover:text-red-600 transition bg-gray-100 hover:bg-red-50 rounded-full w-8 h-8 flex items-center justify-center">
                                    <i class="fas fa-times text-sm"></i>
                                </a>
                            </div>

                            <form action="{{ route('superadmin.skp-tendik.create') }}" method="GET">
                                <div class="space-y-4">
                                    {{-- PILIH PERIODE --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">1. Periode SKP *</label>
                                        <select name="periode_skp_id" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold bg-white text-sm text-gray-700">
                                            <option value="">-- Pilih Periode --</option>
                                            @foreach($periode as $p)
                                                <option value="{{ $p->id }}" {{ ($selPeriode->id ?? $periodeId) == $p->id ? 'selected' : '' }}>
                                                    @if($p->tipe == 'tahunan')
                                                        Tahun {{ $p->tahun }} : {{ $p->semester_genap }} / {{ $p->semester_gasal }}
                                                    @elseif($p->tipe == 'semester')
                                                        Tahun {{ $p->tahun }} : Semester {{ ucfirst($p->semester) }}
                                                    @elseif($p->tipe == 'bulanan')
                                                        Tahun {{ $p->tahun }} : Bulan {{ \Carbon\Carbon::create()->month($p->bulan)->translatedFormat('F') }}{{ $p->bulan_akhir ? ' — ' . \Carbon\Carbon::create()->month($p->bulan_akhir)->translatedFormat('F') : '' }}
                                                    @else
                                                        Tahun {{ $p->tahun }}
                                                    @endif
                                                    ({{ strtoupper($p->status) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- TABEL SETUP JABATAN (POPUP) --}}
                                    <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mt-4 overflow-hidden">
                                        {{-- BARIS 1: PEGAWAI & PENILAI UTAMA (SEJAJAR KIRI-KANAN) --}}
                                        <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">

                                            {{-- SISI KIRI: PEGAWAI YANG DINILAI --}}
                                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                                <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0">I</div>
                                                    <div class="flex-1 px-3 text-blue-800 flex items-center text-xs gap-2 min-w-0 truncate">PEGAWAI YANG DINILAI (PILIH) *</div>
                                                </div>
                                                <div class="p-3 border-b border-gray-200 bg-gray-50">
                                                    <select name="user_id" required x-model="pegawaiId" @change="updatePegawaiInfo($el.value)"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 font-bold bg-white text-xs text-gray-800">
                                                        <option value="">-- Pilih Pegawai --</option>
                                                        @foreach($jsonPegawai as $u)
                                                            <option value="{{ $u['id'] }}">{{ $u['nama'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">1</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Nama</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiName"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">2</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">NIP</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiNIP"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">3</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Pangkat/Golongan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiPangkat"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">4</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Jabatan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiJabatan"></div>
                                                </div>
                                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">5</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Unit Kerja</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                                </div>
                                            </div>

                                            {{-- SISI KANAN: PENILAI UTAMA (WADEK) --}}
                                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                                <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0">II</div>
                                                    <div class="flex-1 px-3 text-emerald-800 flex items-center text-xs gap-2 min-w-0 truncate">PEJABAT PENILAI (PILIH) *</div>
                                                </div>
                                                <div class="p-3 border-b border-gray-200 bg-gray-50">
                                                    <select name="pejabat_penilai_id" required x-model="penilaiId" @change="updatePenilaiInfo($el.value)"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-emerald-500 font-bold bg-white text-xs text-gray-800">
                                                        <option value="">-- Pilih Penilai --</option>
                                                        @foreach($jsonPenilai as $cp)
                                                            <option value="{{ $cp['id'] }}">{{ $cp['nama'] }} ({{ $cp['jabatan'] }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">1</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Nama</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiName"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">2</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">NIP</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiNIP"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">3</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Pangkat/Golongan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiPangkat"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">4</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Jabatan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiJabatan"></div>
                                                </div>
                                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">5</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Unit Kerja</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- BARIS 2: PENILAI AKHIR (DEKAN/REKTOR) VERTIKAL FULL WIDTH --}}
                                        <div class="bg-white flex flex-col min-w-0">
                                            <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                                <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0">III</div>
                                                <div class="flex-1 px-3 text-indigo-800 flex items-center text-xs gap-2 min-w-0 truncate">ATASAN PEJABAT PENILAI (PILIH) *</div>
                                            </div>
                                            <div class="p-3 border-b border-gray-200 bg-gray-50">
                                                <select name="penilai_akhir_id" required x-model="penilaiAkhirId" @change="updatePenilaiAkhirInfo($el.value)"
                                                    class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 font-bold bg-white text-xs text-gray-800">
                                                    <option value="">-- Pilih Atasan Penilai --</option>
                                                    @foreach($jsonPenilaiAkhir as $cpa)
                                                        <option value="{{ $cpa['id'] }}">{{ $cpa['nama'] }} ({{ $cpa['jabatan'] }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex flex-col w-full">
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">1</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Nama</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirName"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">2</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">NIP</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirNIP"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">3</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Pangkat/Golongan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirPangkat"></div>
                                                </div>
                                                <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">4</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Jabatan</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirJabatan"></div>
                                                </div>
                                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">5</div>
                                                    <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Unit Kerja</div>
                                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-col-reverse sm:flex-row gap-3">
                                    @if($user)
                                        <button type="button" @click="openSetupModal = false" class="w-full sm:w-1/3 py-3 rounded-xl font-bold text-center tracking-wider uppercase transition-all border border-gray-300 text-gray-600 hover:bg-gray-100 text-xs">BATAL / KEMBALI</button>
                                    @endif
                                    <button type="submit" class="w-full {{ $user ? 'sm:w-2/3' : '' }} py-3 rounded-xl font-black tracking-wider uppercase transition-all shadow-lg bg-blue-600 hover:bg-blue-700 text-white transform hover:-translate-y-1 flex justify-center items-center gap-2 text-xs">
                                        <span>MUAT FORMULIR KEGIATAN</span> <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

            {{-- 3. FORM UTAMA --}}
            @if($user && $groupedMasters->isNotEmpty())
                <form id="mainForm" action="{{ route('superadmin.skp-tendik.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="periode_skp_id" value="{{ $selPeriode->id }}">
                    <input type="hidden" name="pejabat_penilai_id" value="{{ $penilai->id }}">
                    <input type="hidden" name="penilai_akhir_id" value="{{ $penilaiAkhir->id }}">

                    {{-- PERBAIKAN: MODAL KONFIRMASI (Dibungkus template teleport dan diberi !important) --}}
                    <template x-teleport="body">
                        <div x-show="confirmModal" style="z-index: 999999 !important;" class="fixed inset-0 overflow-y-auto bg-gray-900/80 backdrop-blur-sm" x-cloak>
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="bg-white rounded-3xl p-8 w-full max-w-md mx-auto text-center transform transition-all animate-in zoom-in duration-200">
                                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-yellow-50 shadow-inner">
                                        <i class="fas fa-paper-plane text-3xl text-yellow-600"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-gray-800 mb-3 uppercase tracking-tighter">Kirim SKP?</h3>
                                    <p class="text-sm text-gray-500 mb-8 leading-relaxed">SKP ini akan dikirim ke <b>{{ $penilai->profile->nama_lengkap ?? $penilai->name }}</b> (Wadek) untuk direview tahap pertama.</p>
                                    <div class="flex gap-4">
                                        <button type="button" @click="confirmModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">CEK LAGI</button>
                                        <button type="button" @click="document.getElementById('mainForm').submit()" class="flex-1 px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg transition">YA, KIRIM</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- HEADER IDENTITAS UTAMA (BIODATA TABLE) --}}
                    <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden">

                        {{-- BARIS 1: PEGAWAI & PENILAI UTAMA (SEJAJAR KIRI-KANAN) --}}
                        <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">

                            {{-- SISI KIRI: PEGAWAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0">I</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-blue-800 min-w-0">
                                        <span class="truncate text-xs">PEGAWAI YANG DINILAI</span>
                                        <span class="text-white bg-blue-600 px-2.5 py-1 rounded text-[10px] shadow-sm tracking-wider shrink-0 ml-2">
                                            {{ $selPeriode->tahun ?? '-' }} - {{ $selPeriode->semester_genap ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                                @php
                                    $pegawaiData = [
                                        ['Nama', $user->profile->nama_lengkap ?? $user->name ?? '-'],
                                        ['NIP', $user->profile->nip ?? '-'],
                                        ['Pangkat/Golongan', $user->profile->pangkat_golongan ?? '-'],
                                        ['Jabatan', $user ? $user->getRoleNames()->first() : '-'],
                                        ['Unit Kerja', $user->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']
                                    ];
                                @endphp
                                @foreach ($pegawaiData as $i => $row)
                                    <div class="flex border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition-colors items-center h-12">
                                        <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-bold text-xs shrink-0 h-full">{{ $i + 1 }}</div>
                                        <div class="w-32 px-3 text-[11px] text-gray-600 font-bold shrink-0 flex items-center h-full">{{ $row[0] }}</div>
                                        <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $row[1] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- SISI KANAN: PEJABAT PENILAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0">II</div>
                                    <div class="flex-1 px-3 flex items-center gap-2 min-w-0 text-emerald-800 text-xs">PEJABAT PENILAI</div>
                                </div>
                                @php
                                    $penilaiData = [
                                        ['Nama', $penilai->profile->nama_lengkap ?? $penilai->name ?? '-'],
                                        ['NIP', $penilai->profile->nip ?? '-'],
                                        ['Pangkat/Golongan', $penilai->profile->pangkat_golongan ?? '-'],
                                        ['Jabatan', $penilai ? $penilai->getRoleNames()->first() : '-'],
                                        ['Unit Kerja', $penilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']
                                    ];
                                @endphp
                                @foreach ($penilaiData as $i => $row)
                                    <div class="flex border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition-colors items-center h-12">
                                        <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-bold text-xs shrink-0 h-full">{{ $i + 1 }}</div>
                                        <div class="w-32 px-3 text-[11px] text-gray-600 font-bold shrink-0 flex items-center h-full">{{ $row[0] }}</div>
                                        <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $row[1] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- III. PENILAI AKHIR (DEKAN/REKTOR) VERTIKAL FULL WIDTH --}}
                        <div class="bg-white flex flex-col min-w-0">
                            <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0">III</div>
                                <div class="flex-1 px-3 text-indigo-800 flex items-center justify-between text-xs min-w-0">
                                    <span class="truncate">ATASAN PEJABAT PENILAI</span>
                                </div>
                            </div>
                            <div class="flex flex-col w-full">
                                @php
                                    $penilaiAkhirData = [
                                        ['Nama', $penilaiAkhir->profile->nama_lengkap ?? $penilaiAkhir->name ?? 'BELUM DIATUR'],
                                        ['NIP', $penilaiAkhir->profile->nip ?? '-'],
                                        ['Pangkat/Golongan', $penilaiAkhir->profile->pangkat_golongan ?? '-'],
                                        ['Jabatan', $penilaiAkhir ? $penilaiAkhir->getRoleNames()->first() : '-'],
                                        ['Unit Kerja', $penilaiAkhir->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']
                                    ];
                                @endphp
                                @foreach ($penilaiAkhirData as $i => $row)
                                    <div class="flex border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition-colors items-center h-12">
                                        <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-bold text-xs shrink-0 h-full">{{ $i + 1 }}</div>
                                        <div class="w-32 px-3 text-[11px] text-gray-600 font-bold shrink-0 flex items-center h-full">{{ $row[0] }}</div>
                                        <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $row[1] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- TABEL KEGIATAN --}}
                    <div class="bg-white border border-gray-300 rounded-xl overflow-hidden shadow-sm mb-8">
                        <div class="bg-gray-50 border-b border-gray-300 p-4 font-black text-sm uppercase tracking-wider text-gray-800 flex items-center gap-3">
                            <i class="fas fa-tasks text-blue-600"></i>
                            <span>A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-xs">
                                <thead class="text-gray-700 font-bold text-center bg-gray-100 uppercase tracking-widest border-b border-gray-300">
                                    <tr>
                                        <th rowspan="2" class="border-r border-gray-300 p-3 w-12">NO</th>
                                        <th rowspan="2" class="border-r border-gray-300 p-3 text-center">III. Kegiatan Tugas Jabatan</th>
                                        <th colspan="5" class="p-2 bg-amber-50 text-amber-800 border-b border-gray-300">TARGET</th>
                                        <th rowspan="2" class="p-3 w-12 text-red-500"><i class="fas fa-trash"></i></th>
                                    </tr>
                                    <tr>
                                        <th class="border-r border-gray-300 p-2 w-20 bg-amber-50/50 text-[10px] text-amber-800">AK</th>
                                        <th class="border-r border-gray-300 p-2 w-28 bg-amber-50/50 text-[10px] text-amber-800">Kuantitas/Output</th>
                                        <th class="border-r border-gray-300 p-2 w-24 bg-amber-50/50 text-[10px] text-amber-800">Kualitas/Mutu</th>
                                        <th class="border-r border-gray-300 p-2 w-20 bg-amber-50/50 text-[10px] text-amber-800">Waktu (Bln)</th>
                                        <th class="p-2 w-32 bg-amber-50/50 text-[10px] text-amber-800">Biaya (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @php $rowNumber = 1; @endphp
                                    @foreach ($groupedMasters as $tupoksi => $activities)
                                        <tr class="hover:bg-amber-50/20 transition-colors">
                                            <td class="border-r border-gray-200 p-3 text-center font-bold text-gray-500">{{ $rowNumber++ }}</td>
                                            <td class="border-r border-gray-200 p-3">
                                                <p class="font-bold text-gray-900 leading-snug">{{ $tupoksi }}</p>
                                                <input type="hidden" name="items[{{ $loop->index }}][tupoksi]" value="{{ $tupoksi }}">
                                                <input type="hidden" name="items[{{ $loop->index }}][activities][0][jenis_aktivitas]" value="{{ $tupoksi }}">
                                            </td>
                                            <td class="border-r border-gray-200 p-2"><input type="number" step="0.01" name="items[{{ $loop->index }}][activities][0][ak]" class="table-input font-bold text-blue-700 bg-gray-50/50" placeholder="0" required></td>
                                            <td class="border-r border-gray-200 p-2"><input type="number" step="1" name="items[{{ $loop->index }}][activities][0][kuantitas]" class="table-input bg-gray-50/50" placeholder="0" required></td>
                                            <td class="border-r border-gray-200 p-2"><input type="number" step="1" min="0" max="100" name="items[{{ $loop->index }}][activities][0][kualitas]" class="table-input bg-gray-50/50" placeholder="0" required></td>
                                            <td class="border-r border-gray-200 p-2"><input type="number" step="1" min="0" max="12" name="items[{{ $loop->index }}][activities][0][waktu]" class="table-input bg-gray-50/50" placeholder="0" required></td>
                                            <td class="border-r border-gray-200 p-2"><input type="number" step="1" name="items[{{ $loop->index }}][activities][0][biaya]" class="table-input bg-gray-50/50 text-emerald-700" placeholder="0"></td>
                                            <td class="p-2 text-center">
                                                <button type="button" class="text-gray-300 cursor-not-allowed" title="Master item tidak dapat dihapus di sini"><i class="fas fa-trash-alt text-xs"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TABEL PERILAKU --}}
                    <div class="bg-white border border-gray-300 rounded-xl overflow-hidden shadow-sm mb-12">
                        <div class="bg-gray-50 border-b border-gray-300 p-4 font-black text-sm uppercase tracking-wider text-gray-800">
                            <span>B. Penyusunan Target Perilaku Kerja</span>
                        </div>
                        <div class="p-4 space-y-6" x-data='{
                            perilakuCategories: [
                                { name: "Orientasi Pelayanan", indicators: [] },
                                { name: "Integritas", indicators: [] },
                                { name: "Komitmen", indicators: [] },
                                { name: "Disiplin", indicators: [] },
                                { name: "Kerjasama", indicators: [] },
                                { name: "Kepemimpinan", indicators: [] }
                            ],
                            addIndicator(catIndex) {
                                let cat = this.perilakuCategories[catIndex];
                                cat.indicators.push("");
                            },
                            removeIndicator(catIndex, indIndex) { this.perilakuCategories[catIndex].indicators.splice(indIndex, 1); }
                        }'>
                            <template x-for="(cat, catIndex) in perilakuCategories" :key="catIndex">
                                <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white">
                                    <div class="bg-gray-100 px-4 py-2 flex justify-between items-center border-b border-gray-200">
                                        <span class="font-bold text-gray-700 text-xs uppercase" x-text="(catIndex + 1) + '. ' + cat.name"></span>
                                        <button type="button" @click="addIndicator(catIndex)" class="bg-blue-600 text-white px-3 py-1 rounded text-[10px] font-bold hover:bg-blue-700 transition shadow-sm">
                                            <i class="fas fa-plus mr-1"></i> Tambah Indikator
                                        </button>
                                    </div>
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50 text-[10px] text-gray-500 font-bold uppercase border-b border-gray-200">
                                                <th class="p-2 w-10 text-center">No</th>
                                                <th class="p-2 text-left">Indikator Perilaku / Child Item</th>
                                                <th class="p-2 w-24 text-center">Bobot %</th>
                                                <th class="p-2 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(ind, indIndex) in cat.indicators" :key="indIndex">
                                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                                                    <td class="p-2 text-center text-gray-400 font-bold" x-text="indIndex + 1"></td>
                                                    <td class="p-1">
                                                        <textarea :name="'perilaku[' + cat.name + '][indicators][]'" x-model="cat.indicators[indIndex]"
                                                            class="w-full p-2 text-[11px] border border-gray-200 focus:ring-1 focus:ring-blue-500 bg-white resize-none overflow-hidden rounded"
                                                            rows="1" oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';"
                                                            placeholder="Tuliskan indikator perilaku..."></textarea>
                                                    </td>
                                                    <td class="p-2 text-center align-middle">
                                                        <div class="bg-blue-50 text-blue-700 font-bold rounded py-1 px-2 border border-blue-100"
                                                            x-text="(100 / (cat.indicators.length || 1)).toFixed(1) + '%'"></div>
                                                    </td>
                                                    <td class="p-2 text-center">
                                                        <button type="button" @click="removeIndicator(catIndex, indIndex)" class="text-red-400 hover:text-red-600 transition-colors">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-if="cat.indicators.length === 0">
                                                <tr>
                                                    <td colspan="4" class="p-4 text-center text-gray-400 italic text-[10px]">Klik tombol "Tambah Indikator" untuk mengisi indikator</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- SUBMIT --}}
                    <div class="flex justify-between items-center bg-gray-50 border border-gray-300 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                            <p>Silakan isi semua nilai target</p>
                        </div>
                        <button type="button" @click="if(document.getElementById('mainForm').reportValidity()) { confirmModal = true; }"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-black py-3 px-8 text-sm uppercase tracking-widest transition-all shadow-lg rounded-xl hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-save"></i> SIMPAN & AJUKAN TARGET
                        </button>
                    </div>
                </form>
            @elseif($user && $groupedMasters->isEmpty())
                <div class="bg-red-50 p-16 rounded-3xl border-2 border-dashed border-red-200 text-center animate-pulse">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-6"></i>
                    <h3 class="text-2xl font-black text-red-800 uppercase tracking-tighter mb-2">Master SKP Belum Siap</h3>
                    <p class="text-red-600 font-bold max-w-md mx-auto leading-relaxed">Pegawai <b>{{ $user->profile->nama_lengkap ?? $user->name }}</b> belum memiliki Template Master SKP yang sudah di-approve oleh penilai kegiatan.</p>
                </div>
            @else
                {{-- PLACEHOLDER KOSONG --}}
                <div class="bg-gray-50 p-20 rounded-[3rem] border-4 border-dashed border-gray-200 text-center">
                    <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-8 shadow-sm">
                        <i class="fas fa-mouse-pointer text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-400 uppercase tracking-tighter">Silakan Pilih Pegawai</h3>
                    <p class="text-gray-400 font-medium mt-2">Klik tombol "Ubah Target / Penilai" di pojok kanan atas untuk memulai.</p>
                </div>
            @endif

        </div>
    @endsection

    @push('scripts')
        <script>
            function setupPage() {
                return {
                    openSetupModal: @json($user === null),
                    confirmModal: false,
                    pegawaiId: '{{ $user->id ?? '' }}',
                    pegawaiName: '{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}',
                    pegawaiNIP: '{{ $user->profile->nip ?? '-' }}',
                    pegawaiPangkat: '{{ $user->profile->pangkat_golongan ?? '-' }}',
                    pegawaiJabatan: '{{ $user ? $user->getRoleNames()->first() : '-' }}',
                    penilaiId: '{{ $penilai->id ?? '' }}',
                    penilaiName: '{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}',
                    penilaiNIP: '{{ $penilai->profile->nip ?? '-' }}',
                    penilaiPangkat: '{{ $penilai->profile->pangkat_golongan ?? '-' }}',
                    penilaiJabatan: '{{ $penilai ? $penilai->getRoleNames()->first() : '-' }}',
                    penilaiAkhirId: '{{ $penilaiAkhir->id ?? '' }}',
                    penilaiAkhirName: '{{ $penilaiAkhir->profile->nama_lengkap ?? $penilaiAkhir->name ?? '-' }}',
                    penilaiAkhirNIP: '{{ $penilaiAkhir->profile->nip ?? '-' }}',
                    penilaiAkhirPangkat: '{{ $penilaiAkhir->profile->pangkat_golongan ?? '-' }}',
                    penilaiAkhirJabatan: '{{ $penilaiAkhir ? $penilaiAkhir->getRoleNames()->first() : '-' }}',
                    allPegawai: @json($jsonPegawai),
                    allPenilai: @json($jsonPenilai),
                    allPenilaiAkhir: @json($jsonPenilaiAkhir),

                    updatePegawaiInfo(id) {
                        let u = this.allPegawai.find(x => x.id == id);
                        if(u) {
                            this.pegawaiName = u.nama;
                            this.pegawaiNIP = u.nip;
                            this.pegawaiPangkat = u.pangkat;
                            this.pegawaiJabatan = u.jabatan;
                        } else {
                            this.pegawaiName = this.pegawaiNIP = this.pegawaiPangkat = this.pegawaiJabatan = '-';
                        }
                    },
                    updatePenilaiInfo(id) {
                        let u = this.allPenilai.find(x => x.id == id);
                        if(u) {
                            this.penilaiName = u.nama;
                            this.penilaiNIP = u.nip;
                            this.penilaiPangkat = u.pangkat;
                            this.penilaiJabatan = u.jabatan;
                        } else {
                            this.penilaiName = this.penilaiNIP = this.penilaiPangkat = this.penilaiJabatan = '-';
                        }
                    },
                    updatePenilaiAkhirInfo(id) {
                        let u = this.allPenilaiAkhir.find(x => x.id == id);
                        if(u) {
                            this.penilaiAkhirName = u.nama;
                            this.penilaiAkhirNIP = u.nip;
                            this.penilaiAkhirPangkat = u.pangkat;
                            this.penilaiAkhirJabatan = u.jabatan;
                        } else {
                            this.penilaiAkhirName = this.penilaiAkhirNIP = this.penilaiAkhirPangkat = this.penilaiAkhirJabatan = '-';
                        }
                    }
                }
            }

            document.addEventListener('wheel', function(event) {
                if (document.activeElement.type === 'number') {
                    document.activeElement.blur();
                }
            }, { passive: false });
        </script>
    @endpush
</x-app-layout>
