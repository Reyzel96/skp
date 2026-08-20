<x-app-layout>
    @section('page-title', 'Detail Master & Target SKP')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            .display-cell {
                width: 100%;
                min-height: 40px;
                padding: 6px 4px;
                display: flex;
                align-items: center;
                box-sizing: border-box;
                font-size: 10px;
                background: transparent;
                line-height: 1.2;
            }

            .display-cell.center { justify-content: center; text-align: center; }
            .display-cell.right { justify-content: flex-end; text-align: right; }

            th {
                background-color: #f8fafc;
                border: 1px solid #cbd5e1 !important;
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.025em;
                text-align: center;
                vertical-align: middle;
                padding: 4px 2px !important;
                color: #475569;
                line-height: 1.1;
                word-wrap: break-word;
            }

            td {
                border: 1px solid #cbd5e1 !important;
                padding: 0 !important;
                vertical-align: middle !important;
            }

            .checkbox-cell {
                text-align: center;
                vertical-align: middle !important;
            }

            .table-input {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                display: block;
                box-sizing: border-box;
                border: none !important;
                padding: 6px 4px;
                font-size: 10px;
                background: transparent;
                resize: none;
                overflow: hidden;
                min-height: 40px;
                white-space: pre-wrap;
                word-break: break-word;
                line-height: 1.2;
            }

            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* CSS TAMBAHAN UNTUK TABEL PERILAKU MENYERUPAI GAMBAR */
            .table-skp-custom th, .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }
            .bg-grey-custom {
                background-color: #d1d5db !important;
            }
        </style>

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans"
            x-data="{
                perilakuCategories: [
                    { name: 'Orientasi Pelayanan', indicators: [], isOpen: true },
                    { name: 'Integritas', indicators: [], isOpen: true },
                    { name: 'Komitmen', indicators: [], isOpen: true },
                    { name: 'Disiplin', indicators: [], isOpen: true },
                    { name: 'Kerjasama', indicators: [], isOpen: true },
                    { name: 'Kepemimpinan', indicators: [], isOpen: true }
                ],

                init() {
                    const existingPerilaku = @js($perilaku);
                    this.perilakuCategories.forEach(cat => {
                        if (existingPerilaku[cat.name]) {
                            cat.indicators = existingPerilaku[cat.name].map(p => ({ text: p.indikator, persentase: p.bobot_persen }));
                        }
                    });
                }
            }" x-init="init()">
        <script>
            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }
        </script>
            {{-- HEADER NAVIGASI --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 w-full">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">Detail Master & <span class="text-blue-600">Target SKP</span></h1>
                    <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest font-bold">Melihat rincian master tupoksi dan penetapan target operasional</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                    @if($header && $header->status !== 'Selesai' && $header->status !== 'Disetujui')
                        <a href="{{ route('superadmin.master-skp-tendik.edit', $header->id) }}"
                            class="w-full md:w-auto text-center inline-flex items-center justify-center bg-amber-500 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-amber-600 transition-all shadow-lg text-sm">
                            <i class="fas fa-edit mr-2"></i> Edit Data
                        </a>
                    @endif
                    <a href="{{ route('superadmin.master-skp-tendik.index') }}"
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
                                {{ $header->atasanPejabatPenilai->profile->nama_lengkap ?? ($header->atasanPejabatPenilai->name ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP / NIDN</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->atasanPejabatPenilai->profile->nip ?? ($header->atasanPejabatPenilai->profile->nidn ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->atasanPejabatPenilai->profile->pangkat_golongan ?? ($header->atasanPejabatPenilai->profile->pangkat ?? '......................') }}
                            </div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->atasanPejabatPenilai->profile->jabatan ?? ($header->atasanPejabatPenilai ? ($header->atasanPejabatPenilai->getRoleNames()->first() ?? '......................') : '......................') }}
                            </div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">
                                {{ $header->atasanPejabatPenilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ SECTION BARU: STATUS APPROVAL TARGET (3 LEVEL) --}}
            @if($header)
            @php
                $isFinalized = (isset($header) && in_array($header->status, ['Target Diajukan', 'Disetujui', 'Review Akhir', 'Selesai', 'Diajukan', 'Pending Utama', 'Pending Akhir']));
            @endphp
            <div class="mb-6 bg-white border border-gray-300 rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-black text-gray-800 uppercase mb-4 tracking-tighter flex items-center gap-2">
                    <i class="fas fa-shield-alt text-blue-600"></i> Monitoring Persetujuan Target SKP
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Level 0: Pegawai (Finalisasi) --}}
                    <div class="p-3 rounded-lg border {{ $isFinalized ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isFinalized ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $isFinalized ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 0</span>
                        </div>
                        <p class="text-xs font-black {{ $isFinalized ? 'text-emerald-700' : 'text-orange-700' }}">PEGAWAI</p>
                        <p class="text-[9px] font-bold text-gray-500 truncate">
                            @if($isFinalized)
                                <span class="text-emerald-600 italic uppercase"><i class="fas fa-check-double mr-1"></i> Target Difinalisasi</span>
                            @else
                                <span class="text-orange-600 italic uppercase">Menunggu Finalisasi</span>
                            @endif
                        </p>
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
                        <p class="text-[9px] text-gray-500 font-bold truncate">
                            @if(!$isFinalized)
                                Menunggu Finalisasi
                            @else
                                {{ $header->is_target_approved_atasan ? 'Disetujui' : 'Menunggu Persetujuan' }}
                            @endif
                        </p>
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
                        <p class="text-[9px] text-gray-500 font-bold truncate">
                            @if(!$isFinalized)
                                Menunggu Finalisasi
                            @else
                                {{ $header->is_target_approved_penilai ? 'Disetujui' : 'Menunggu Persetujuan' }}
                            @endif
                        </p>
                    </div>

                    {{-- Level 3: Penilai Kegiatan --}}
                    @php
                        $totalActivities = $activities->count();
                        $approvedActivities = $activities->where('status_approval', 'approved')->count();
                        $isAllActivitiesApproved = $totalActivities > 0 && ($totalActivities === $approvedActivities);
                    @endphp
                    <div class="p-3 rounded-lg border {{ $isAllActivitiesApproved ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isAllActivitiesApproved ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $isAllActivitiesApproved ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 3</span>
                        </div>
                        <p class="text-xs font-black {{ $isAllActivitiesApproved ? 'text-emerald-700' : 'text-orange-700' }}">PENILAI KEGIATAN</p>
                        <p class="text-[9px] text-gray-500 font-bold">
                            @if(!$isFinalized)
                                Menunggu Finalisasi
                            @else
                                {{ $approvedActivities }} / {{ $totalActivities }} Kegiatan Disetujui
                            @endif
                        </p>
                    </div>
                </div>

                @if($header->is_target_approved_atasan && $header->is_target_approved_penilai && $isAllActivitiesApproved)
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

            <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter mb-4">📝 A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</h2>

            {{-- TABLE 1: TUPOKSI & OPERASIONAL --}}
            <div class="mb-2 ml-1 flex items-center gap-2">
                <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Tupoksi & Operasional SKP</h3>
            </div>
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-8">
                <table class="w-full table-fixed border-collapse bg-white">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 h-10">
                            <th rowspan="2" style="width: 3%;">No</th>
                            <th rowspan="2" style="width: 13%;">I. Jabatan/Bidang</th>
                            <th rowspan="2" style="width: 20%;">II. Kegiatan Tupoksi</th>
                            <th rowspan="2" style="width: 22%;">III. Jenis Aktivitas Unit Kerja</th>
                            <th rowspan="2" style="width: 8%;">Penilai</th>
                            <th colspan="3" style="width: 9%;" class="border-b border-slate-300">Kategori</th>
                            <th rowspan="2" style="width: 6%;">Periode</th>
                            <th rowspan="2" style="width: 4%;">Freq</th>
                            <th rowspan="2" style="width: 4%;">Dur</th>
                            <th rowspan="2" style="width: 3%;">SDM</th>
                            <th rowspan="2" style="width: 8%;">Vol Kerja</th>
                        </tr>
                        <tr class="bg-gray-100 text-gray-700">
                            <th style="width: 3%;" class="text-[8px] px-0">Utama</th>
                            <th style="width: 3%;" class="text-[8px] px-0">Prd</th>
                            <th style="width: 3%;" class="text-[8px] px-0">Bkn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $index => $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="text-center text-[9px] font-bold text-slate-400 pt-3">
                                    <div class="display-cell center">{{ $index + 1 }}</div>
                                </td>
                                <td><div class="display-cell font-semibold text-blue-800 uppercase">{{ $item->role_name }}</div></td>
                                <td><div class="display-cell font-bold text-gray-800">{{ $item->tupoksi }}</div></td>
                                <td><div class="display-cell text-gray-700 leading-snug">{{ $item->jenis_aktivitas }}</div></td>
                                <td><div class="display-cell center font-semibold text-emerald-700">{{ $item->penilai_role ?? '-' }}</div></td>
                                <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black">
                                    {{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? '✓' : '' }}
                                </td>
                                <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black">
                                    {{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? '✓' : '' }}
                                </td>
                                <td class="checkbox-cell text-emerald-600 font-black">
                                    {{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? '✓' : '' }}
                                </td>
                                <td><div class="display-cell center uppercase font-bold text-gray-500">{{ $item->periode }}</div></td>
                                <td><div class="display-cell center font-bold">{{ $item->frekuensi_per_tahun }}</div></td>
                                <td><div class="display-cell center font-bold">{{ $item->durasi_menit }}</div></td>
                                <td><div class="display-cell center font-bold">{{ $item->jumlah_sdm }}</div></td>
                                <td class="bg-slate-50">
                                    <div class="display-cell right font-black text-indigo-700">
                                        {{ number_format($item->volume_kerja ?? ($item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm), 0, ',', '.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- TABLE 2: TARGET SKP --}}
            <div class="mb-2 ml-1 flex items-center gap-2">
                <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Penetapan Target SKP</h3>
            </div>
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-8">
                <table class="w-full table-fixed border-collapse bg-white">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 h-10">
                            <th rowspan="2" style="width: 3%;">No</th>
                            <th rowspan="2" style="width: 43%;" class="text-center">III. Kegiatan Tugas Jabatan (Berdasarkan Kegiatan Tupoksi)</th>
                            <th colspan="5" class="bg-gray text-amber-800 border-b border-slate-300">Target</th>
                        </tr>
                        <tr class="bg-gray-100 text-gray-700">
                            <th style="width: 6%;" class="bg-amber-50 text-amber-800">AK</th>
                            <th style="width: 10%;" class="bg-amber-50 text-amber-800">Kuantitas</th>
                            <th style="width: 10%;" class="bg-amber-50 text-amber-800">Kualitas</th>
                            <th style="width: 8%;" class="bg-amber-50 text-amber-800">Waktu</th>
                            <th style="width: 20%;" class="bg-amber-50 text-amber-800 font-bold">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $index => $item)
                            @php
                                $detail = $skpDetails->get(trim($item->jenis_aktivitas));
                                $detail = $detail ? $detail->first() : null;
                            @endphp
                            <tr class="hover:bg-amber-50/10 transition-colors">
                                <td class="text-center text-[9px] font-bold text-slate-400 pt-3">
                                    <div class="display-cell center">{{ $index + 1 }}</div>
                                </td>
                                <td class="p-0">
                                    <textarea readonly class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'">{{ $item->jenis_aktivitas }}</textarea>
                                </td>
                                <td class="bg-amber-50/20">
                                    <div class="display-cell center font-bold text-blue-700">{{ $detail->ak ?? '0' }}</div>
                                </td>
                                <td class="bg-amber-50/20">
                                    <div class="display-cell center font-bold">{{ $detail->kuantitas_output ?? '0' }}</div>
                                </td>
                                <td class="bg-amber-50/20">
                                    <div class="display-cell center font-bold">{{ $detail->kualitas_mutu ?? '0' }}%</div>
                                </td>
                                <td class="bg-amber-50/20">
                                    <div class="display-cell center font-bold">{{ $detail->waktu ?? '0' }}</div>
                                </td>
                                <td class="bg-amber-50/20">
                                    <div class="display-cell right font-bold text-emerald-700">Rp {{ number_format($detail->biaya ?? 0, 0, ',', '.') }}</div>
                                </td>
                            </tr>
                        @endforeach

                        {{-- TUGAS TAMBAHAN SECTION --}}
                        <tr class="font-bold bg-gray-100 border-b border-gray-800">
                            <td class="border-r border-gray-800 p-2 text-center font-bold text-gray-800">IV</td>
                            <td colspan="6" class="p-2">
                                <div class="flex justify-between items-center w-full">
                                    <span class="font-black text-gray-900 uppercase">Tugas tambahan dan kreatifitas</span>
                                </div>
                            </td>
                        </tr>

                        @if(isset($tugasTambahan) && $tugasTambahan->count() > 0)
                            @foreach($tugasTambahan as $tidx => $tugas)
                                <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300">
                                    <td class="text-center font-bold text-slate-400 pt-3">
                                        <div class="display-cell center text-[9px]">{{ $tidx + 1 }}</div>
                                    </td>
                                    <td class="p-2 font-black text-indigo-600 align-top uppercase text-[8px]">
                                        {{ $tugas->jenis }}
                                    </td>
                                    <td class="bg-amber-50/10 text-center font-bold text-indigo-700">
                                        {{ number_format($tugas->ak, 2, ',', '.') }}
                                    </td>
                                    <td colspan="3" class="p-2 font-medium text-gray-700 align-top text-[11px] leading-snug">
                                        {{ $tugas->kegiatan }}
                                    </td>
                                    <td class="p-2 text-center align-middle bg-gray-50">
                                        @if($header && !in_array($header->status, ['Disetujui', 'Selesai']))
                                            <form action="{{ route('pegawai.skp.tugas-tambahan.destroy', $tugas->id) }}" method="POST" onsubmit="return confirm('Hapus tugas tambahan ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full bg-red-50 text-red-600 py-1 rounded text-[8px] font-black uppercase hover:bg-red-100 transition shadow-sm border border-red-200">
                                                    <i class="fas fa-trash mr-1"></i> Hapus
                                                </button>
                                            </form>
                                        @elseif($header && in_array($header->status, ['Disetujui', 'Selesai']))
                                            <span class="text-[8px] font-black text-emerald-600 uppercase bg-emerald-50 px-2 py-1 rounded border border-emerald-200 shadow-sm">
                                                <i class="fas fa-check-double mr-1"></i> Approved
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-400 italic font-semibold text-[10px]">
                                    Belum ada tugas tambahan yang diinputkan.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- TABEL PERILAKU --}}
            <div class="bg-white mb-8 w-full">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">B. Penyusunan Target Perilaku Kerja</h3>
                    </div>

                    <div class="w-full overflow-hidden rounded-xl border border-black shadow-sm">
                        <table class="w-full border-collapse text-sm table-skp-custom bg-white border-none">
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
                                    <td class="text-center bg-white w-12">-</td>
                                    <td class="text-center bg-white w-24">x</td>
                                    <td class="text-center font-bold bg-white w-20">60%</td>
                                    <td class="text-center font-bold bg-white">-</td>
                                </tr>

                                {{-- B. PERILAKU KERJA (LOOPING CATEGORIES) --}}
                                <template x-for="(cat, catIndex) in perilakuCategories" :key="catIndex">
                                    <tr>
                                        <template x-if="catIndex === 0">
                                            <td :rowspan="perilakuCategories.length + 3" class="font-bold align-middle w-48 bg-white px-4">b. Perilaku Kerja</td>
                                        </template>
                                        <td class="align-top bg-white">
                                            <div class="flex justify-between items-center mb-1 cursor-pointer" @click="cat.isOpen = !cat.isOpen">
                                                <span x-text="(catIndex + 1) + '. ' + cat.name" class="mt-1 text-[13px] font-semibold"></span>
                                                <div class="text-gray-400 transition-transform duration-200" :class="cat.isOpen ? 'rotate-180' : ''">
                                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2 mt-2" x-show="cat.isOpen">
                                                <template x-for="(ind, indIndex) in cat.indicators" :key="indIndex">
                                                    <div class="flex items-start gap-2 bg-gray-50 p-2 border border-gray-300 rounded shadow-sm">
                                                        <div class="flex-1 min-w-0">
                                                            <textarea readonly :name="'perilaku[' + cat.name + '][indicators][]'" x-model="ind.text"
                                                                x-init="$nextTick(() => autoResize($el))"
                                                                class="w-full text-xs border-gray-300 rounded focus:ring-blue-500 resize-none p-1.5 bg-gray-50 cursor-default" rows="2"
                                                                oninput="autoResize(this)" placeholder="Form Input unsur kegiatan"></textarea>
                                                        </div>
                                                        <div class="w-20 shrink-0 flex flex-col items-center justify-center">
                                                            <span class="text-[9px] font-bold text-gray-500 uppercase">Persentase</span>
                                                            <div class="bg-white text-blue-700 font-bold rounded py-1 border border-blue-200 text-[10px] w-full text-center mt-1"
                                                                x-text="ind.persentase + '%'"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle bg-white">-</td>

                                        <td colspan="2" class="text-center align-middle bg-white">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>
                                </template>

                                {{-- FOOTER 7. JUMLAH --}}
                                <tr>
                                    <td class="bg-white">7. Jumlah</td>
                                    <td class="text-center align-middle bg-white">-</td>
                                    <td colspan="2" class="text-center align-middle bg-white">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>

                                {{-- FOOTER 8. NILAI RATA-RATA --}}
                                <tr>
                                    <td class="bg-white">8. Nilai rata &ndash; rata</td>
                                    <td class="text-center align-middle bg-white">-</td>
                                    <td colspan="2" class="text-center align-middle bg-white">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>

                                {{-- FOOTER 9. NILAI PERILAKU KERJA --}}
                                <tr>
                                    <td class="font-bold bg-white">9. Nilai Perilaku Kerja</td>
                                    <td class="text-center align-middle bg-white">-</td>
                                    <td class="text-center align-middle bg-white">x</td>
                                    <td class="text-center font-bold align-middle bg-white">40%</td>
                                    <td class="text-center font-bold align-middle bg-white">-</td>
                                </tr>

                                {{-- KESIMPULAN NILAI PRESTASI KERJA --}}
                                <tr>
                                    <td colspan="6" class="text-center font-bold text-lg p-3 bg-white tracking-widest">NILAI PRESTASI KERJA</td>
                                    <td class="text-center font-bold text-lg p-3 bg-white">(-)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    @endsection
</x-app-layout>
