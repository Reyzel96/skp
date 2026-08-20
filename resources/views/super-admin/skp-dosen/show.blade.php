<x-app-layout>
    @section('page-title', 'Detail Target SKP & Perilaku Dosen')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            /* CSS TAMBAHAN UNTUK TABEL PERILAKU MENYERUPAI GAMBAR */
            .table-skp-custom th, .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }
            .bg-grey-custom {
                background-color: #d1d5db !important;
            }

            .display-cell {
                width: 100%;
                min-height: 28px;
                padding: 2px 4px;
                display: flex;
                align-items: center;
                box-sizing: border-box;
                font-size: 9px;
                background: transparent;
                line-height: 1.1;
            }
            .display-cell.center { justify-content: center; text-align: center; }
            .display-cell.right { justify-content: flex-end; text-align: right; }

            .checkbox-cell {
                text-align: center;
                vertical-align: middle !important;
                font-size: 8px;
            }

            .table-input {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                display: block;
                box-sizing: border-box;
                border: none !important;
                padding: 4px 4px;
                font-size: 9px;
                background: transparent;
                resize: none;
                overflow: hidden;
                min-height: 35px;
                white-space: pre-wrap;
                word-break: break-word;
                line-height: 1.1;
            }
        </style>
        <div class="p-6 max-w-full mx-auto font-sans" x-data="{
            skp: {{ $header->nilai_skp ?? 0 }},
            orientasi: {{ $header->orientasi_pelayanan ?? 0 }},
            integritas: {{ $header->integritas ?? 0 }},
            komitmen: {{ $header->komitmen ?? 0 }},
            disiplin: {{ $header->disiplin ?? 0 }},
            kerjasama: {{ $header->kerjasama ?? 0 }},
            kepemimpinan: {{ $header->kepemimpinan ?? 0 }},

            perilakuCategories: [
                { name: 'Orientasi Pelayanan', indicators: [], isOpen: true, key: 'orientasi' },
                { name: 'Integritas', indicators: [], isOpen: true, key: 'integritas' },
                { name: 'Komitmen', indicators: [], isOpen: true, key: 'komitmen' },
                { name: 'Disiplin', indicators: [], isOpen: true, key: 'disiplin' },
                { name: 'Kerjasama', indicators: [], isOpen: true, key: 'kerjasama' },
                { name: 'Kepemimpinan', indicators: [], isOpen: true, key: 'kepemimpinan' }
            ],

            init() {
                const existingPerilaku = @js($indicators);
                this.perilakuCategories.forEach(cat => {
                    if (existingPerilaku[cat.name]) {
                        cat.indicators = existingPerilaku[cat.name].map(p => ({
                            text: p.indikator,
                            bobot: p.bobot_persen
                        }));
                    }
                });
            },

            getPredicateFinal(val) {
                let v = parseFloat(val);
                if (isNaN(v) || v == 0) return '';
                if (v <= 50) return '(Buruk)';
                if (v <= 60) return '(Kurang)';
                if (v <= 75) return '(Cukup)';
                if (v <= 90.99) return '(Baik)';
                return '(Sangat Baik)';
            },
            get countFilled() {
                let count = 0;
                if (this.orientasi > 0) count++;
                if (this.integritas > 0) count++;
                if (this.komitmen > 0) count++;
                if (this.disiplin > 0) count++;
                if (this.kerjasama > 0) count++;
                if (this.kepemimpinan > 0) count++;
                return count === 0 ? 1 : count;
            },
            get sumPerilaku() {
                return parseFloat(this.orientasi) + parseFloat(this.integritas) +
                    parseFloat(this.komitmen) + parseFloat(this.disiplin) +
                    parseFloat(this.kerjasama) + parseFloat(this.kepemimpinan);
            },
            get avgPerilaku() { return this.sumPerilaku > 0 ? (this.sumPerilaku / this.countFilled).toFixed(2) : 0; },
            get calcSkp60() { return (this.skp * 0.6).toFixed(2); },
            get calcPerilaku40() { return (this.avgPerilaku * 0.4).toFixed(2); },
            get finalScore() { return (parseFloat(this.calcSkp60) + parseFloat(this.calcPerilaku40)).toFixed(2); }
        }" x-init="init()">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 w-full print:hidden">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">Detail Target & <span class="text-blue-600">Perilaku Dosen</span></h1>
                    <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest font-bold">Melihat rincian penetapan target tridharma dan perilaku kerja</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                    @if($header->status !== 'Selesai' && $header->status !== 'Disetujui')
                        <a href="{{ route('superadmin.skp-dosen.edit', $header->id) }}"
                            class="w-full md:w-auto text-center inline-flex items-center justify-center bg-amber-500 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-amber-600 transition-all shadow-lg text-sm">
                            <i class="fas fa-edit mr-2"></i> Edit Data
                        </a>
                    @endif
                    <a href="{{ route('superadmin.skp-dosen.index') }}"
                        class="w-full md:w-auto text-center inline-flex items-center justify-center bg-gray-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-gray-700 transition-all shadow-lg text-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>

            {{-- HEADER IDENTITAS UTAMA (BIODATA TABLE) --}}
            <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden">

                {{-- BARIS 1: PEGAWAI & PENILAI UTAMA (SEJAJAR KIRI-KANAN) --}}
                <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">

                    {{-- SISI KIRI: PEGAWAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0 font-black tracking-widest">I</div>
                            <div class="flex-1 px-3 flex items-center text-blue-800 min-w-0">
                                <span class="truncate text-[10px] font-black tracking-tight uppercase">PEGAWAI YANG DINILAI</span>
                            </div>
                        </div>

                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $user->profile->nama_lengkap ?? ($user->name ?? '-') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP / NIDN</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $user->profile->nip ?? ($user->profile->nidn ?? '-') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $user->profile->pangkat_golongan ?? ($user->profile->pangkat ?? '-') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $user->profile->jabatan ?? ($user->getRoleNames()->first() ?? '-') }}
                            </div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $user->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}
                            </div>
                        </div>
                    </div>

                    {{-- SISI KANAN: PEJABAT PENILAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black tracking-widest">II</div>
                            <div class="flex-1 px-3 flex items-center justify-between text-emerald-800 min-w-0">
                                <span class="truncate text-[10px] font-black tracking-tight uppercase">PEJABAT PENILAI</span>
                                @if($header && $header->periode)
                                    <span class="text-white bg-emerald-600 px-2.5 py-1.5 rounded-lg text-[9px] shadow-lg shadow-emerald-100 tracking-wider shrink-0 ml-2 font-black uppercase">
                                        {{ $header->periode->nama_periode }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->pejabatPenilai->profile->nama_lengkap ?? ($header->pejabatPenilai->name ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP / NIDN</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->pejabatPenilai->profile->nip ?? ($header->pejabatPenilai->profile->nidn ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->pejabatPenilai->profile->pangkat_golongan ?? ($header->pejabatPenilai->profile->pangkat ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->pejabatPenilai->profile->jabatan ?? ($header->pejabatPenilai ? ($header->pejabatPenilai->getRoleNames()->first() ?? '......................') : '......................') }}
                            </div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->pejabatPenilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ATASAN PEJABAT PENILAI --}}
                <div class="bg-white flex flex-col min-w-0">
                    @php
                        $atasan = $header->atasanPejabatPenilai ?? $header->penilaiAkhir;
                    @endphp
                    <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                        <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0 font-black tracking-widest">III</div>
                        <div class="flex-1 px-3 flex items-center text-indigo-800 min-w-0">
                            <span class="truncate text-[10px] font-black tracking-tight uppercase">ATASAN PEJABAT PENILAI</span>
                        </div>
                    </div>

                    <div class="flex flex-col w-full">
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $atasan->profile->nama_lengkap ?? ($atasan->name ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP / NIDN</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $atasan->profile->nip ?? ($atasan->profile->nidn ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $atasan->profile->pangkat_golongan ?? ($atasan->profile->pangkat ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $atasan->profile->jabatan ?? ($atasan ? ($atasan->getRoleNames()->first() ?? '......................') : '......................') }}
                            </div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $atasan->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ SECTION BARU: STATUS APPROVAL TARGET (3 LEVEL) --}}
            @if($header)
            <div class="mb-6 bg-white border border-gray-300 rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-black text-gray-800 uppercase mb-4 tracking-tighter flex items-center gap-2">
                    <i class="fas fa-shield-alt text-blue-600"></i> Monitoring Persetujuan Target SKP
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Level 0: Pegawai Yang Dinilai --}}
                    @php
                        $isPegawaiFinalized = $header && in_array($header->status, ['Target Diajukan', 'Disetujui', 'Review Akhir', 'Selesai', 'Diajukan']);
                    @endphp
                    <div class="p-3 rounded-lg border {{ $isPegawaiFinalized ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isPegawaiFinalized ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $isPegawaiFinalized ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Pegawai</span>
                        </div>
                        <p class="text-xs font-black {{ $isPegawaiFinalized ? 'text-emerald-700' : 'text-orange-700' }}">PEGAWAI YANG DINILAI</p>
                        
                        @if($isPegawaiFinalized)
                            <p class="text-[9px] text-emerald-600 font-bold italic mt-1 uppercase">
                                <i class="fas fa-check-double mr-1"></i> Difinalisasi
                            </p>
                        @else
                            <p class="text-[9px] text-orange-500 font-bold italic mt-1 uppercase">
                                <i class="fas fa-hourglass-half mr-1"></i> Belum Finalisasi (Draft)
                            </p>
                            <p class="text-[8px] text-gray-400 font-medium truncate mt-0.5">
                                {{ $user->profile->nama_lengkap ?? ($user->name ?? '-') }}
                            </p>
                        @endif
                    </div>

                    {{-- Level 1: Atasan Pejabat Penilai --}}
                    <div class="p-3 rounded-lg border {{ $header->is_target_approved_atasan ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_atasan ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $header->is_target_approved_atasan ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 1</span>
                        </div>
                        <p class="text-xs font-black {{ $header->is_target_approved_atasan ? 'text-emerald-700' : 'text-orange-700' }}">ATASAN PEJABAT PENILAI</p>
                        
                        @if($header->is_target_approved_atasan)
                            <p class="text-[9px] text-emerald-600 font-bold italic mt-1 uppercase">
                                <i class="fas fa-check-double mr-1"></i> Disetujui
                            </p>
                        @else
                            <p class="text-[9px] text-orange-500 font-bold italic mt-1 uppercase">
                                <i class="fas fa-hourglass-half mr-1"></i> Menunggu Persetujuan
                            </p>
                            <p class="text-[8px] text-gray-400 font-medium truncate mt-0.5">
                                {{ $header->penilaiAkhir->profile->nama_lengkap ?? ($header->penilaiAkhir->name ?? '-') }}
                            </p>
                        @endif
                    </div>

                    {{-- Level 2: Pejabat Penilai --}}
                    <div class="p-3 rounded-lg border {{ $header->is_target_approved_penilai ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_penilai ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $header->is_target_approved_penilai ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 2</span>
                        </div>
                        <p class="text-xs font-black {{ $header->is_target_approved_penilai ? 'text-emerald-700' : 'text-orange-700' }}">PEJABAT PENILAI</p>
                        
                        @if($header->is_target_approved_penilai)
                            <p class="text-[9px] text-emerald-600 font-bold italic mt-1 uppercase">
                                <i class="fas fa-check-double mr-1"></i> Disetujui
                            </p>
                        @else
                            <p class="text-[9px] text-orange-500 font-bold italic mt-1 uppercase">
                                <i class="fas fa-hourglass-half mr-1"></i> Menunggu Persetujuan
                            </p>
                            <p class="text-[8px] text-gray-400 font-medium truncate mt-0.5">
                                {{ $header->pejabatPenilai->profile->nama_lengkap ?? ($header->pejabatPenilai->name ?? '-') }}
                            </p>
                        @endif
                    </div>

                    {{-- Level 3: Penilai Kegiatan --}}
                    <div class="p-3 rounded-lg border {{ $activities->count() > 0 && $activities->where('status_approval', 'approved')->count() === $activities->count() ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        @php
                            $totalActivities = $activities->count();
                            $approvedActivities = $activities->where('status_approval', 'approved')->count();
                            $isAllActivitiesApproved = $totalActivities > 0 && ($totalActivities === $approvedActivities);
                        @endphp
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isAllActivitiesApproved ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $isAllActivitiesApproved ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 3</span>
                        </div>
                        <p class="text-xs font-black {{ $isAllActivitiesApproved ? 'text-emerald-700' : 'text-orange-700' }}">PENILAI KEGIATAN</p>
                        <p class="text-[9px] text-gray-500 font-bold">{{ $approvedActivities }} / {{ $totalActivities }} Kegiatan Disetujui</p>
                    </div>
                </div>

                @if($isPegawaiFinalized && $header->is_target_approved_atasan && $header->is_target_approved_penilai && $isAllActivitiesApproved)
                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3">
                        <i class="fas fa-check-double text-emerald-500"></i>
                        <p class="text-xs font-bold text-emerald-700 leading-tight">
                            Target SKP sudah disetujui sepenuhnya oleh semua pihak. Pegawai sudah dapat melakukan realisasi kinerja.
                        </p>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <p class="text-xs font-bold text-blue-700 leading-tight">
                            Status: Masih menunggu kelengkapan persetujuan target dari pihak-pihak terkait sebelum realisasi dapat dimulai.
                        </p>
                    </div>
                @endif
            </div>
            @endif

            <div class="space-y-8 mb-8">
                {{-- TABLE 1: TUPOKSI & BEBAN KERJA --}}
                <div class="mb-2 ml-1 flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Rincian Tupoksi & Beban Kerja</h3>
                </div>
                <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-8">
                    <table class="w-full table-fixed border-collapse bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 h-9">
                                <th rowspan="2" style="width: 3%;" class="text-[9px] font-black uppercase">No</th>
                                <th rowspan="2" style="width: 15%;" class="text-[9px] font-black uppercase">I. Jabatan/Bidang</th>
                                <th rowspan="2" style="width: 22%;" class="text-[9px] font-black uppercase">II. Kegiatan Tupoksi</th>
                                <th rowspan="2" style="width: 26%;" class="text-[9px] font-black uppercase">III. Jenis Aktivitas</th>
                                <th rowspan="2" style="width: 8%;" class="text-[9px] font-black uppercase">Penilai</th>
                                <th colspan="3" style="width: 9%;" class="text-[9px] font-black uppercase border-b border-slate-300">Kategori</th>
                                <th rowspan="2" style="width: 6%;" class="text-[9px] font-black uppercase">Periode</th>
                                <th rowspan="2" style="width: 4%;" class="text-[9px] font-black uppercase">Freq</th>
                                <th rowspan="2" style="width: 4%;" class="text-[9px] font-black uppercase">Dur</th>
                                <th rowspan="2" style="width: 3%;" class="text-[9px] font-black uppercase">SDM</th>
                                <th rowspan="2" style="width: 8%;" class="text-[9px] font-black uppercase">Vol Kerja</th>
                            </tr>
                            <tr class="bg-gray-100 text-gray-700">
                                <th style="width: 3%;" class="text-[7px] font-bold px-0 uppercase">Utama</th>
                                <th style="width: 3%;" class="text-[7px] font-bold px-0 uppercase">Prd</th>
                                <th style="width: 3%;" class="text-[7px] font-bold px-0 uppercase">Bkn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $index => $item)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="text-center text-[9px] font-bold text-slate-400 pt-3">
                                        <div class="display-cell center">{{ $index + 1 }}</div>
                                    </td>
                                    <td><div class="display-cell font-semibold text-blue-800 uppercase">{{ $item->role_name }}</div></td>
                                    <td><div class="display-cell font-bold text-gray-800">{{ $item->tupoksi }}</div></td>
                                    <td><div class="display-cell text-gray-700 leading-snug">{{ $item->jenis_aktivitas }}</div></td>
                                    <td><div class="display-cell center font-semibold text-emerald-700 uppercase tracking-tighter text-[8px]">{{ $item->penilai_role ?? '-' }}</div></td>
                                    <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black text-center">
                                        {{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? '✓' : '' }}
                                    </td>
                                    <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black text-center">
                                        {{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? '✓' : '' }}
                                    </td>
                                    <td class="checkbox-cell text-emerald-600 font-black text-center">
                                        {{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? '✓' : '' }}
                                    </td>
                                    <td><div class="display-cell center uppercase font-bold text-gray-500">{{ $item->periode }}</div></td>
                                    <td><div class="display-cell center font-bold">{{ $item->frekuensi_per_tahun }}</div></td>
                                    <td><div class="display-cell center font-bold">{{ $item->durasi_menit }}</div></td>
                                    <td><div class="display-cell center font-bold">{{ $item->jumlah_sdm }}</div></td>
                                    <td class="bg-slate-50">
                                        <div class="display-cell right font-black text-indigo-700">
                                            {{ number_format(($item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm), 0, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="p-4 text-center text-gray-400 italic">Belum ada rincian tupoksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 2. TABEL TARGET TUGAS JABATAN (TRIDHARMA) --}}
                <div class="mb-2 ml-1 flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Penetapan Target SKP (Tridharma)</h3>
                </div>
                @if (count($skpDosens) > 0)
                    <div class="bg-white border border-gray-300 rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gray-100 border-b border-gray-300 p-3 font-black text-sm uppercase tracking-tighter flex justify-between items-center">
                            <span>A. KEGIATAN TUGAS JABATAN</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[1000px] border-collapse text-[11px] border-none bg-white">
                                <thead class="text-gray-900 font-bold text-center bg-gray-50 border-b border-gray-300">
                                    <tr>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-8">NO</th>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-auto uppercase">I. Kegiatan Tugas Jabatan</th>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-10">AK</th>
                                        <th colspan="4" class="border border-gray-300 p-2 uppercase text-blue-800">TARGET</th>
                                    </tr>
                                    <tr>
                                        <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-blue-700">Kuant</th>
                                        <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-blue-700">Kual</th>
                                        <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-blue-700">Waktu</th>
                                        <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-blue-700">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800">
                                    @php
                                        $nomor = 1;
                                        $totalAK = 0;

                                        // Pengurutan Kategori Sesuai Excel
                                        $kategoriOrder = [
                                            'Pelaksanaan Pendidikan' => 'PELAKSANAAN PENDIDIKAN',
                                            'Pelaksanaan Penelitian' => 'PELAKSANAAN PENELITIAN',
                                            'Pelaksanaan Pengabdian Kepada Masyarakat' =>
                                                'PELAKSANAAN PENGABDIAN KEPADA MASYARAKAT',
                                            'Menduduki Jabatan Di Perguruan Tinggi' =>
                                                'MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI',
                                            'Unsur Penunjang' => 'UNSUR PENUNJANG',
                                        ];

                                        // Mapping Nama dan Satuan Kuantitas (Sesuai dengan Excel)
                                        $mappingKegiatan = [
                                            'melaksanakan_perkuliahan' => [
                                                'nama' => 'Melaksanakan perkuliahan',
                                                'satuan' => 'mata kuliah',
                                            ],
                                            'membimbing_seminar' => [
                                                'nama' => 'Membimbing mahasiswa seminar',
                                                'satuan' => 'mahasiswa',
                                            ],
                                            'membimbing_kkn_pkl' => [
                                                'nama' =>
                                                    'Membimbing mahasiswa KKN, Praktek Kerja Nyata, Praktek Kerja Lapangan',
                                                'satuan' => 'kegiatan',
                                            ],
                                            'membimbing_skripsi_thesis_disertasi' => [
                                                'nama' =>
                                                    'Membimbing dan ikut membimbing dalam menghasilkan skripsi, thesis, disertasi',
                                                'satuan' => 'kegiatan',
                                            ],
                                            'penguji_ujian_akhir' => [
                                                'nama' =>
                                                    'Menjadi penguji Ujian Akhir baik sebagai ketua maupun anggota',
                                                'satuan' => 'kegiatan',
                                            ],
                                            'membina_kemahasiswaan' => [
                                                'nama' =>
                                                    'Membina kegiatan kemahasiswaan di bidang akademik dan kemahasiswaan',
                                                'satuan' => 'kegiatan',
                                            ],
                                            'mengembangkan_bahan_kuliah' => [
                                                'nama' => 'Mengembangkan bahan kuliah',
                                                'satuan' => 'bahan',
                                            ],
                                            'karya_ilmiah' => [
                                                'nama' => 'Menghasilkan karya ilmiah',
                                                'satuan' => 'karya',
                                            ],
                                            'pengabdian_kepada_masyarakat' => [
                                                'nama' => 'Melaksanakan kegiatan Pengabdian Kepada Masyarakat',
                                                'satuan' => 'kegiatan',
                                            ],
                                            'menduduki_jabatan_pimpinan' => [
                                                'nama' => 'MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI',
                                                'satuan' => 'Jabatan',
                                            ],
                                            'unsur_penunjang' => ['nama' => 'Unsur Penunjang', 'satuan' => 'kegiatan'],
                                        ];

                                        // Mapping Master Jabatan dengan Nilai AK per Semester
                                        $masterJabatan = [
                                            1 => ['nama' => 'Rektor', 'ak' => 6],
                                            2 => ['nama' => 'Pembantu/Wakil Rektor', 'ak' => 5],
                                            3 => ['nama' => 'Dekan', 'ak' => 5],
                                            4 => ['nama' => 'Direktur Pasca Sarjana', 'ak' => 5],
                                            5 => ['nama' => 'Ketua Sekolah Tinggi', 'ak' => 4],
                                            6 => ['nama' => 'Pembantu/ Wakil Ketua Sekolah Tinggi', 'ak' => 4],
                                            7 => ['nama' => 'Direktur Politeknik', 'ak' => 4],
                                            8 => ['nama' => 'Pembantu/Wakil Direktur Politeknik', 'ak' => 4],
                                            9 => ['nama' => 'Direktur Akademi', 'ak' => 4],
                                            10 => ['nama' => 'Pembantu/Wakil Dekan', 'ak' => 4],
                                            11 => ['nama' => 'Asisten Direktur Pascasarjana', 'ak' => 4],
                                            12 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3],
                                            13 => ['nama' => 'Ketua Jurusan/Bagian', 'ak' => 3],
                                            14 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3],
                                            15 => ['nama' => 'Sekretaris Jurusan', 'ak' => 3],
                                            16 => ['nama' => 'Kepala Laboratorium', 'ak' => 3],
                                        ];
                                    @endphp

                                    @foreach ($kategoriOrder as $katDb => $katLabel)
                                        @php
                                            $items = $skpDosens->where('kategori', $katDb);
                                        @endphp

                                        @if ($items->count() > 0)
                                            <tr class="font-bold bg-gray-50 border-b border-gray-300">
                                                <td class="border-r border-gray-300 p-2 text-center"></td>
                                                <td colspan="6" class="p-2 font-black uppercase text-gray-900 italic">{{ $katLabel }}</td>
                                            </tr>

                                            @foreach ($items as $skp)
                                                @php
                                                    $totalAK += $skp->ak;
                                                    $jenis = strtolower(trim($skp->jenis));
                                                    $satuan = isset($mappingKegiatan[$jenis]) ? $mappingKegiatan[$jenis]['satuan'] : 'kegiatan';
                                                @endphp

                                                @if ($skp->jenis === 'menduduki_jabatan_pimpinan')
                                                    @php
                                                        $detail = is_string($skp->detail_kegiatan)
                                                            ? json_decode($skp->detail_kegiatan, true)
                                                            : $skp->detail_kegiatan;
                                                        $hasGenap =
                                                            !empty($detail['kode_genap']) &&
                                                            isset($masterJabatan[$detail['kode_genap']]);
                                                        $hasGasal =
                                                            !empty($detail['kode_gasal']) &&
                                                            isset($masterJabatan[$detail['kode_gasal']]);

                                                        // --- LOGIKA SEMESTER DINAMIS ---
                                                        $tahunSkp = $header->tahun ?? date('Y');
                                                        $labelGenap =
                                                            'Semester Genap ' . ($tahunSkp - 1) . '/' . $tahunSkp;
                                                        $labelGasal =
                                                            'Semester Gasal ' . $tahunSkp . '/' . ($tahunSkp + 1);
                                                    @endphp

                                                    @if ($hasGenap || $hasGasal)
                                                        @php $currentNomor = $nomor++; @endphp

                                                        {{-- Main Title Row --}}
                                                        <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-300">
                                                            <td class="border-r border-gray-300 p-2 text-center align-top relative">
                                                                {{ $currentNomor }}</td>
                                                            <td
                                                                class="border-r border-gray-300 p-2 align-top text-gray-900 uppercase font-black text-left">
                                                                {!! isset($mappingKegiatan[$skp->jenis])
                                                                    ? e($mappingKegiatan[$skp->jenis]['nama'])
                                                                    : 'MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI' !!}
                                                            </td>
                                                            <td colspan="5" class="bg-gray-50"></td>
                                                        </tr>

                                                        @if ($hasGenap)
                                                            @php $jabatanGenap = $masterJabatan[$detail['kode_genap']]; @endphp
                                                            <tr class="bg-blue-50/30 border-b border-gray-300 font-bold italic text-[9px]">
                                                                <td class="border-r border-gray-300"></td>
                                                                <td colspan="6" class="p-2 text-blue-900 pl-6">{{ $labelGenap }}</td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300">
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-[10px] text-gray-400">
                                                                    {{ $currentNomor }}.1</td>
                                                                <td class="border-r border-gray-300 p-2 align-top text-gray-800 pl-8 font-medium">
                                                                    {{ $jabatanGenap['nama'] }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">
                                                                    {{ number_format($jabatanGenap['ak'], 2, ',', '.') }}
                                                                </td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                    1 {{ $satuan }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">
                                                                    {{ round($skp->kualitas_mutu) }}%</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                    6 bln</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">-</td>
                                                            </tr>
                                                        @endif

                                                        @if ($hasGasal)
                                                            @php
                                                                $jabatanGasal = $masterJabatan[$detail['kode_gasal']];
                                                            @endphp
                                                            <tr class="bg-blue-50/30 border-b border-gray-300 font-bold italic text-[9px]">
                                                                <td class="border-r border-gray-300"></td>
                                                                <td colspan="6" class="p-2 text-blue-900 pl-6">{{ $labelGasal }}</td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300">
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-[10px] text-gray-400">
                                                                    {{ $currentNomor }}.2</td>
                                                                <td class="border-r border-gray-300 p-2 align-top text-gray-800 pl-8 font-medium">
                                                                    {{ $jabatanGasal['nama'] }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">
                                                                    {{ number_format($jabatanGasal['ak'], 2, ',', '.') }}
                                                                </td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                    1 {{ $satuan }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">
                                                                    {{ round($skp->kualitas_mutu) }}%</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                    6 bln</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">-</td>
                                                            </tr>
                                                        @endif
                                                    @else
                                                        {{-- Fallback jika JSON belum tersimpan (Format Lama) --}}
                                                        <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-300">
                                                            <td class="border-r border-gray-300 p-2 text-center align-top relative">
                                                                {{ $nomor++ }}</td>
                                                            <td class="border-r border-gray-300 p-2 align-top text-gray-900 uppercase font-black text-left">
                                                                {!! nl2br(e($skp->kegiatan)) !!}
                                                            </td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">
                                                                {{ number_format($skp->ak, 2, ',', '.') }}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                {{ $skp->kuantitas_output }} {{ $satuan }}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">
                                                                {{ round($skp->kualitas_mutu) }}%</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">
                                                                {{ $skp->waktu }} bln</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">
                                                                @if ($skp->biaya && $skp->biaya > 0)
                                                                    Rp {{ number_format($skp->biaya, 0, ',', '.') }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @else
                                                    {{-- Tampilan Normal untuk Jenis Kegiatan Lainnya --}}
                                                    <tr class="hover:bg-gray-50 transition-colors">
                                                        <td class="border border-gray-300 p-2 text-center align-top">
                                                            {{ $nomor++ }}</td>
                                                        <td class="border border-gray-300 p-2 align-top text-gray-800">
                                                            {!! isset($mappingKegiatan[$skp->jenis]) ? e($mappingKegiatan[$skp->jenis]['nama']) : nl2br(e($skp->kegiatan)) !!}
                                                        </td>
                                                        <td class="border border-gray-300 p-2 text-center align-top">
                                                            {{ number_format($skp->ak, 2, ',', '.') }}</td>
                                                        <td
                                                            class="border border-gray-300 p-2 text-center align-top whitespace-nowrap">
                                                            {{ $skp->kuantitas_output }} {{ $satuan }}
                                                        </td>
                                                        <td class="border border-gray-300 p-2 text-center align-top">
                                                            {{ round($skp->kualitas_mutu) }}</td>
                                                        <td
                                                            class="border border-gray-300 p-2 text-center align-top whitespace-nowrap">
                                                            {{ $skp->waktu }} bulan</td>
                                                        <td class="border border-gray-300 p-2 text-center align-top">
                                                            {{ $skp->biaya == 0 ? '-' : number_format($skp->biaya, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>

                                {{-- TUGAS TAMBAHAN BODY --}}
                                <tbody id="tugas-tambahan-body" class="text-gray-800 border-t-2 border-gray-300">
                                    <tr class="font-bold bg-gray-50 border-b border-gray-300" id="tugas-tambahan-header">
                                        <td class="border-r border-gray-300 p-2 text-center"></td>
                                        <td colspan="6" class="p-2 font-black uppercase text-gray-900 italic">Tugas Tambahan & Kreativitas</td>
                                    </tr>
                                    @foreach($tugasTambahan as $item)
                                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-200 tt-row">
                                            <td class="border-r border-gray-300 p-2 text-center align-top tt-num text-[9px] font-bold text-slate-400">{{ $nomor++ }}</td>
                                            <td class="border-r border-gray-300 p-2 align-top text-gray-900 whitespace-normal leading-tight font-medium tt-kegiatan text-left">
                                                <div class="flex flex-col text-left">
                                                    @php
                                                        $ttTitle = ($item->tugas_tambahan && $item->tugas_tambahan !== '...') ? $item->tugas_tambahan : ($item->kegiatan ?: '-');
                                                        $ttDetail = ($item->detail_tupoksi && $item->detail_tupoksi !== '-') ? $item->detail_tupoksi : '';
                                                        $akTugasTambahan = $item->ak > 0 ? $item->ak : 1;
                                                        $totalAK += $akTugasTambahan;
                                                    @endphp
                                                    <span class="text-[10px] font-bold text-gray-800 leading-tight">{{ $ttTitle }}</span>
                                                </div>
                                            </td>
                                            <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold tt-ak text-[9px]">{{ number_format($akTugasTambahan, 2, ',', '.') }}</td>
                                            <td colspan="4" class="border-r border-gray-300 p-2 align-top text-gray-600 italic text-[9px] text-left">
                                                {{ $ttDetail ?: '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    {{-- Baris Total AK --}}
                                    <tr class="font-black bg-indigo-50 border-t-2 border-gray-300">
                                        <td class="border-r border-gray-300 p-2 text-right uppercase text-[10px]" colspan="2">TOTAL ANGKA KREDIT</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-indigo-700 text-sm">{{ number_format($totalAK, 2, ',', '.') }}</td>
                                        <td class="border-gray-300 p-2 bg-gray-50" colspan="4"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- 2. TABEL PERILAKU KERJA (STYLING BARU SEPERTI TENDIK) --}}
                <div class="bg-white mb-8 w-full">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">B. Penyusunan Target Perilaku Kerja</h3>
                    </div>

                    <div class="w-full overflow-hidden rounded-xl border border-black shadow-sm">
                        <table class="w-full border-collapse text-[11px] table-skp-custom bg-white border-none">
                            <thead>
                                <tr>
                                    <th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white">UNSUR YANG DINILAI</th>
                                    <th class="text-center font-bold bg-white w-32">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- A. SKP (60%) --}}
                                <tr>
                                    <td :rowspan="perilakuCategories.length + 4" class="text-center font-bold align-middle w-12 bg-white text-base">4.</td>
                                    <td colspan="2" class="font-bold bg-white">a. Sasaran Kerja Pegawai (SKP)</td>
                                    <td class="text-center bg-white w-12" x-text="skp"></td>
                                    <td class="text-center bg-white w-24">x</td>
                                    <td class="text-center font-bold bg-white w-20">60%</td>
                                    <td class="text-center font-bold bg-white" x-text="calcSkp60"></td>
                                </tr>

                                {{-- B. PERILAKU KERJA (LOOPING CATEGORIES) --}}
                                <template x-for="(cat, catIndex) in perilakuCategories" :key="catIndex">
                                    <tr>
                                        <template x-if="catIndex === 0">
                                            <td :rowspan="perilakuCategories.length + 3" class="font-bold align-middle w-48 bg-white px-4">b. Perilaku Kerja</td>
                                        </template>
                                        <td class="align-top bg-white">
                                            <div class="flex justify-between items-center mb-1 cursor-pointer" @click="cat.isOpen = !cat.isOpen">
                                                <span x-text="(catIndex + 1) + '. ' + cat.name" class="mt-1 text-[11px] font-semibold"></span>
                                                <div class="text-gray-400 transition-transform duration-200" :class="cat.isOpen ? 'rotate-180' : ''">
                                                    <i class="fas fa-chevron-down text-[8px]"></i>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2 mt-2" x-show="cat.isOpen">
                                                <template x-for="(ind, indIndex) in cat.indicators" :key="indIndex">
                                                    <div class="flex items-start gap-2 bg-gray-50 p-2 border border-gray-300 rounded shadow-sm">
                                                        <div class="flex-1 min-w-0">
                                                            <textarea readonly x-model="ind.text"
                                                                class="w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 resize-none p-1.5 bg-gray-50 cursor-default" rows="2"
                                                                placeholder="Unsur kegiatan"></textarea>
                                                        </div>
                                                        <div class="w-20 shrink-0 flex flex-col items-center justify-center">
                                                            <span class="text-[8px] font-bold text-gray-500 uppercase">Persen</span>
                                                            <div class="bg-white text-blue-700 font-bold rounded py-1 border border-blue-200 text-[9px] w-full text-center mt-1"
                                                                x-text="parseFloat(ind.bobot).toFixed(1) + '%'"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="cat.indicators.length === 0">
                                                    <p class="text-[9px] text-gray-400 italic px-2">Belum ada target indikator.</p>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle bg-white" x-text="this[cat.key] || '-'"></td>

                                        <td colspan="2" class="text-center align-middle bg-white">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>
                                </template>

                                {{-- FOOTER 7. JUMLAH --}}
                                <tr>
                                    <td class="bg-white">7. Jumlah</td>
                                    <td class="text-center align-middle bg-white" x-text="sumPerilaku || '-'"></td>
                                    <td colspan="2" class="text-center align-middle bg-white">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>

                                {{-- FOOTER 8. NILAI RATA-RATA --}}
                                <tr>
                                    <td class="bg-white">8. Nilai rata &ndash; rata</td>
                                    <td class="text-center align-middle bg-white" x-text="avgPerilaku || '-'"></td>
                                    <td colspan="2" class="text-center align-middle bg-white">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>

                                {{-- FOOTER 9. NILAI PERILAKU KERJA --}}
                                <tr>
                                    <td class="font-bold bg-white">9. Nilai Perilaku Kerja</td>
                                    <td class="text-center align-middle bg-white" x-text="avgPerilaku || '-'"></td>
                                    <td class="text-center align-middle bg-white">x</td>
                                    <td class="text-center font-bold align-middle bg-white">40%</td>
                                    <td class="text-center font-bold align-middle bg-white" x-text="calcPerilaku40 || '-'"></td>
                                </tr>

                                {{-- KESIMPULAN NILAI PRESTASI KERJA --}}
                                <tr>
                                    <td colspan="6" class="text-center font-bold text-lg p-3 bg-white tracking-widest uppercase">Nilai Prestasi Kerja</td>
                                    <td class="text-center font-bold text-lg p-3 bg-white" x-text="finalScore || '-'"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>

            {{-- REJECT MODAL --}}
            <div x-data="{ rejectModal: false, rejectId: null, rejectType: 'skp' }" 
             @open-reject-modal.window="rejectModal = true; rejectId = $event.detail.id; rejectType = $event.detail.type || 'skp'">
            <div x-show="rejectModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all" @click.away="rejectModal = false">
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter mb-4" x-text="rejectType === 'activity' ? 'Tolak Kegiatan' : 'Kembalikan Untuk Revisi'"></h3>
                    <p class="text-sm text-gray-500 mb-6 font-medium" x-text="rejectType === 'activity' ? 'Berikan alasan penolakan untuk kegiatan ini.' : 'Berikan alasan atau instruksi revisi agar pegawai dapat memperbaiki target SKP mereka.'"></p>

                    <form :action="rejectType === 'activity' ? `/penilai/approval-skp-tendik/${rejectId}/reject` : '#'" method="POST">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Catatan / Alasan</label>
                            <textarea name="catatan" class="w-full border-gray-200 rounded-2xl focus:ring-red-500 font-bold text-sm bg-gray-50 p-4" rows="4" required placeholder="Tuliskan alasan..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="rejectModal = false" class="px-6 py-2.5 rounded-xl font-bold text-gray-500 hover:bg-gray-100 transition uppercase text-xs">Batal</button>
                            <button type="submit" class="px-8 py-2.5 rounded-xl font-bold bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-100 transition uppercase text-xs">Konfirmasi</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
            @endsection
</x-app-layout>
