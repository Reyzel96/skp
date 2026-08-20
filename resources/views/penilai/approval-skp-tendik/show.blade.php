<x-app-layout>
    @section('page-title', 'Detail Review Master SKP Tendik')

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

            /* CSS TAMBAHAN UNTUK TABEL PERILAKU */
            .table-skp-custom {
                border-collapse: collapse !important;
                border-style: hidden !important;
            }
            .table-skp-custom th, .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }
            .bg-grey-custom {
                background-color: #d1d5db !important;
            }
        </style>

        @php
            $isFinalized = (isset($header) && in_array($header->status, ['Target Diajukan', 'Disetujui', 'Review Akhir', 'Selesai', 'Diajukan', 'Pending Utama', 'Pending Akhir']));
            $canEditTarget = $isLevel1Or2 && !$isFinalized;
        @endphp

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans"
            x-data="{ 
                addTaskModal: false, 
                isDirty: false,
                openAspect: null,
                perilaku: @js($perilaku->map(fn($items) => $items->map(fn($i) => ['id' => $i->id, 'indikator' => $i->indikator, 'nilai' => $i->nilai, 'bobot_persen' => $i->bobot_persen, 'is_from_admin' => $i->is_from_admin])->values())),
                
                addIndikator(aspek) {
                    if (!this.perilaku[aspek]) this.perilaku[aspek] = [];
                    this.perilaku[aspek].push({ id: null, indikator: '', bobot_persen: 0, nilai: 0, is_from_admin: 1 });
                    this.isDirty = true;
                    
                    let items = this.perilaku[aspek];
                    let count = items.length;
                    let baseAvg = Math.floor(100 / count);
                    let remainder = 100 - (baseAvg * count);
                    items.forEach((item, idx) => {
                        item.bobot_persen = baseAvg + (idx < remainder ? 1 : 0);
                    });
                },

                removeIndikator(aspek, index) {
                    this.perilaku[aspek].splice(index, 1);
                    this.isDirty = true;
                    
                    let items = this.perilaku[aspek];
                    if (items.length > 0) {
                        let count = items.length;
                        let baseAvg = Math.floor(100 / count);
                        let remainder = 100 - (baseAvg * count);
                        items.forEach((item, idx) => {
                            item.bobot_persen = baseAvg + (idx < remainder ? 1 : 0);
                        });
                    }
                },
                
                recalculateBobot(aspek, changedIndex) {
                    let items = this.perilaku[aspek];
                    if (!items || items.length <= 1) return;
                    
                    let changedVal = parseInt(items[changedIndex].bobot_persen, 10) || 0;
                    if (changedVal > 100) changedVal = 100;
                    if (changedVal < 0) changedVal = 0;
                    items[changedIndex].bobot_persen = changedVal;
                    
                    let sum = items.reduce((s, item) => s + (parseInt(item.bobot_persen, 10) || 0), 0);
                    let diff = 100 - sum;
                    
                    if (diff === 0) {
                        this.isDirty = true;
                        return;
                    }
                    
                    let absorbOrder = [];
                    for (let i = changedIndex + 1; i < items.length; i++) absorbOrder.push(i);
                    for (let i = changedIndex - 1; i >= 0; i--) absorbOrder.push(i);
                    
                    for (let idx of absorbOrder) {
                        if (diff === 0) break;
                        let currentVal = parseInt(items[idx].bobot_persen, 10) || 0;
                        let newVal = currentVal + diff;
                        
                        if (newVal > 100) {
                            diff = newVal - 100;
                            newVal = 100;
                        } else if (newVal < 0) {
                            diff = newVal;
                            newVal = 0;
                        } else {
                            diff = 0;
                        }
                        
                        items[idx].bobot_persen = newVal;
                    }
                    this.isDirty = true;
                },

                getWeight(aspek) {
                    const items = this.perilaku[aspek] || [];
                    return items.length > 0 ? (100 / items.length).toFixed(1) : 0;
                }
            }">
            
            {{-- HEADER NAVIGASI --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 w-full">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="bg-emerald-600 p-3 rounded-xl shadow-lg shadow-emerald-200">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-gray-900">Review Approval Master SKP</h1>
                        <p class="text-sm text-gray-500 font-medium">Review dan berikan persetujuan untuk rincian tugas pegawai</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                    @php 
                        $isFinalized = (isset($header) && in_array($header->status, ['Target Diajukan', 'Disetujui', 'Review Akhir', 'Selesai', 'Diajukan', 'Pending Utama', 'Pending Akhir'])); 
                        // Cek apakah penilai punya kegiatan yang bisa di-approve (pending & role cocok)
                        $myPendingActivities = $activities->where('status_approval', 'pending')->filter(fn($a) => $isBypassRole || (isset($myRoles) && $myRoles->contains($a->penilai_role)));
                    @endphp

                    @if($myPendingActivities->count() > 0)
                        <form action="{{ route('penilai.approval-skp-tendik.bulk-approve') }}" method="POST" onsubmit="return confirm('Setujui seluruh kegiatan Anda yang belum di-approve?');">
                            @csrf
                            @foreach($myPendingActivities as $act)
                                <input type="hidden" name="ids[]" value="{{ $act->id }}">
                            @endforeach
                            <button type="submit" 
                                @if(!$isFinalized) disabled title="Menunggu finalisasi pegawai" @endif
                                class="w-full md:w-auto text-center inline-flex items-center justify-center {{ $isFinalized ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-400 cursor-not-allowed opacity-60' }} text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg text-sm">
                                <i class="fas fa-check-double mr-2"></i> Setujui Semua ({{ $myPendingActivities->count() }})
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('penilai.approval-skp-tendik.index') }}"
                        class="w-full md:w-auto text-center inline-flex items-center justify-center bg-gray-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-gray-700 transition-all shadow-lg text-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>

            {{-- HEADER IDENTITAS UTAMA (BIODATA TABLE) --}}
            <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden">
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
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $pegawai->profile->nama_lengkap ?? ($pegawai->name ?? '-') }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $pegawai->profile->nip ?? '-' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $pegawai->profile->pangkat_golongan ?? '-' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $pegawai->getRoleNames()->first() ?? '-' }}</div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $pegawai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}</div>
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
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->pejabatPenilai->profile->nama_lengkap ?? ($header->pejabatPenilai->name ?? '......................') }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->pejabatPenilai->profile->nip ?? '......................' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->pejabatPenilai->profile->pangkat_golongan ?? '......................' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->pejabatPenilai->profile->jabatan ?? ($header->pejabatPenilai ? ($header->pejabatPenilai->getRoleNames()->first() ?? '......................') : '......................') }}</div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->pejabatPenilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}</div>
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
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->atasanPejabatPenilai->profile->nama_lengkap ?? ($header->atasanPejabatPenilai->name ?? '......................') }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->atasanPejabatPenilai->profile->nip ?? '......................' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->atasanPejabatPenilai->profile->pangkat_golongan ?? '......................' }}</div>
                        </div>
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->atasanPejabatPenilai->profile->jabatan ?? ($header->atasanPejabatPenilai ? ($header->atasanPejabatPenilai->getRoleNames()->first() ?? '......................') : '......................') }}</div>
                        </div>
                        <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                            <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $header->atasanPejabatPenilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MONITORING PERSETUJUAN --}}
            @if($header)
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
                        
                        <div class="mt-2">
                            @if($header->is_target_approved_atasan)
                                <p class="text-[9px] text-emerald-600 font-bold uppercase"><i class="fas fa-check-circle mr-1"></i> Sudah Disetujui</p>
                            @elseif($isFinalized && $header->atasan_pejabat_penilai_id == auth()->id())
                                <form action="{{ route('penilai.evaluasi.approve-target', $header->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-black py-1.5 px-3 rounded shadow-sm transition uppercase tracking-wider">
                                        Setujui Target
                                    </button>
                                </form>
                            @else
                                <p class="text-[9px] text-gray-500 font-bold truncate">Menunggu Persetujuan</p>
                            @endif
                        </div>
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
                        
                        <div class="mt-2">
                            @if($header->is_target_approved_penilai)
                                <p class="text-[9px] text-emerald-600 font-bold uppercase"><i class="fas fa-check-circle mr-1"></i> Sudah Disetujui</p>
                            @elseif($isFinalized && $header->pejabat_penilai_id == auth()->id())
                                <form action="{{ route('penilai.evaluasi.approve-target', $header->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-black py-1.5 px-3 rounded shadow-sm transition uppercase tracking-wider">
                                        Setujui Target
                                    </button>
                                </form>
                            @else
                                <p class="text-[9px] text-gray-500 font-bold truncate">Menunggu Persetujuan</p>
                            @endif
                        </div>
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
            </div>
            @endif

            <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter mb-4">📝 A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</h2>

            {{-- FORM BULK SAVE --}}
            <form id="form-bulk-save" action="{{ route('penilai.approval-skp-tendik.bulk-update', $pegawai->id) }}" method="POST">
                @csrf
            </form>

            {{-- TABLE 1: TUPOKSI & OPERASIONAL --}}
            <div class="mb-2 ml-1 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Tupoksi & Operasional SKP</h3>
                </div>
                @if($canEditTarget)
                    <button type="button" onclick="createNewActivityRow()" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-black uppercase hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        <i class="fas fa-plus mr-1"></i> Tambah Tupoksi
                    </button>
                @endif
            </div>
            <div class="w-full shadow-md border border-gray-300 rounded-xl bg-white overflow-hidden mb-8">
                <div class="overflow-x-auto w-full custom-scrollbar">
                    <table class="w-full border-collapse bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 h-10">
                                <th rowspan="2" class="px-2" style="width: 3%;">No</th>
                                <th rowspan="2" class="px-3" style="width: 13%;">I. Jabatan/Bidang</th>
                                <th rowspan="2" class="px-3" style="width: 18%;">II. Kegiatan Tupoksi</th>
                                <th rowspan="2" class="px-3" style="width: 18%;">III. Jenis Aktivitas Unit Kerja</th>
                                <th colspan="3" class="border-b border-slate-300 px-1" style="width: 9%;">Kategori</th>
                                <th rowspan="2" class="px-2" style="width: 6%;">Periode</th>
                                <th rowspan="2" class="px-1" style="width: 4%;">Freq</th>
                                <th rowspan="2" class="px-1" style="width: 4%;">Dur</th>
                                <th rowspan="2" class="px-1" style="width: 3%;">SDM</th>
                                <th rowspan="2" class="px-3" style="width: 7%;">Vol Kerja</th>
                                <th rowspan="2" class="px-2" style="width: 8%;">Penilai</th>
                                <th rowspan="2" class="px-2" style="width: 10%;">Aksi</th>
                            </tr>
                            <tr class="bg-gray-100 text-gray-700">
                                <th style="width: 3%;" class="text-[8px] px-0">Utama</th>
                                <th style="width: 3%;" class="text-[8px] px-0">Prd</th>
                                <th style="width: 3%;" class="text-[8px] px-0">Bkn</th>
                            </tr>
                        </thead>
                        <tbody id="tupoksi-container">
                            @foreach($activities as $index => $item)
                                <tr class="hover:bg-slate-50 transition-colors approval-row" id="row-tupoksi-{{ $item->id }}">
                                    <td class="text-center text-[9px] font-bold text-slate-400 pt-3">
                                        <div class="display-cell center">{{ $index + 1 }}</div>
                                    </td>
                                    <td>
                                        <select name="items[{{ $item->id }}][role_name]" form="form-bulk-save" class="table-input font-bold text-blue-800 uppercase" onchange="window.fetchTupoksiByBidang(this)" {{ !$canEditTarget ? 'disabled' : '' }}>
                                            @foreach($bidangList as $bidang)
                                                <option value="{{ $bidang }}" {{ $item->role_name == $bidang ? 'selected' : '' }}>{{ $bidang }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="tupoksi_select" class="table-input font-bold text-gray-800" onchange="window.fillTupoksiData(this)" {{ !$canEditTarget ? 'disabled' : '' }}>
                                            <option value="">-- Pilih Tupoksi --</option>
                                            <option value="{{ json_encode(['tupoksi' => $item->tupoksi]) }}" selected>{{ $item->tupoksi }}</option>
                                        </select>
                                        <input type="hidden" name="items[{{ $item->id }}][tupoksi]" form="form-bulk-save" value="{{ $item->tupoksi }}">
                                    </td>
                                    <td>
                                        <textarea name="items[{{ $item->id }}][jenis_aktivitas]" form="form-bulk-save" class="table-input italic" rows="2" oninput="autoResize(this); syncToTable2(this, '{{ $item->id }}')" {{ !$canEditTarget ? 'readonly' : '' }}>{{ $item->jenis_aktivitas }}</textarea>
                                    </td>
                                    <td class="checkbox-cell border-r border-slate-300">
                                        <input type="checkbox" name="items[{{ $item->id }}][kategori][]" value="Utama" form="form-bulk-save" {{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? 'checked' : '' }} {{ !$canEditTarget ? 'disabled' : '' }} class="w-3 h-3 text-blue-600 rounded">
                                    </td>
                                    <td class="checkbox-cell border-r border-slate-300">
                                        <input type="checkbox" name="items[{{ $item->id }}][kategori][]" value="Periodik" form="form-bulk-save" {{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? 'checked' : '' }} {{ !$canEditTarget ? 'disabled' : '' }} class="w-3 h-3 text-blue-600 rounded">
                                    </td>
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="items[{{ $item->id }}][kategori][]" value="Bukan Tupoksi" form="form-bulk-save" {{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? 'checked' : '' }} {{ !$canEditTarget ? 'disabled' : '' }} class="w-3 h-3 text-blue-600 rounded">
                                    </td>
                                    <td>
                                        <select name="items[{{ $item->id }}][periode]" form="form-bulk-save" class="table-input text-center uppercase font-bold text-gray-500" {{ !$canEditTarget ? 'disabled' : '' }}>
                                            @foreach(['bulanan','harian','mingguan','triwulan','semester','tahunan','kegiatan'] as $p)
                                                <option value="{{ $p }}" {{ strtolower($item->periode) == $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $item->id }}][frekuensi_per_tahun]" form="form-bulk-save" class="table-input text-center inp-frekuensi font-bold" value="{{ $item->frekuensi_per_tahun }}" oninput="recalcVolume(this)" {{ !$canEditTarget ? 'readonly' : '' }}></td>
                                    <td><input type="number" name="items[{{ $item->id }}][durasi_menit]" form="form-bulk-save" class="table-input text-center inp-durasi font-bold" value="{{ $item->durasi_menit }}" oninput="recalcVolume(this)" {{ !$canEditTarget ? 'readonly' : '' }}></td>
                                    <td><input type="number" name="items[{{ $item->id }}][jumlah_sdm]" form="form-bulk-save" class="table-input text-center inp-sdm font-bold" value="{{ $item->jumlah_sdm }}" oninput="recalcVolume(this)" {{ !$canEditTarget ? 'readonly' : '' }}></td>
                                    <td class="bg-slate-50 text-right px-2 font-black text-indigo-700 text-[9px] pt-3 vol-display">
                                        {{ number_format($item->volume_kerja ?? ($item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm), 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <select name="items[{{ $item->id }}][penilai_role]" form="form-bulk-save" class="table-input text-[8px] font-black uppercase text-center" {{ !$canEditTarget ? 'disabled' : '' }}>
                                            @foreach($penilaiRoles as $pr)
                                                <option value="{{ $pr->name }}" {{ $item->penilai_role == $pr->name ? 'selected' : '' }}>{{ $pr->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-2 align-middle text-center">
                                        <div class="flex flex-col gap-1">
                                            @php
                                                $canApproveThis = ($isBypassRole || (isset($myRoles) && $myRoles->contains($item->penilai_role)));
                                            @endphp

                                            @if($item->status_approval === 'approved')
                                                <div class="flex flex-col items-center bg-emerald-50 py-2 px-2 rounded-lg border border-emerald-300 shadow-sm">
                                                    <span class="text-[10px] font-black text-emerald-700 uppercase mb-1 tracking-wider"><i class="fas fa-check-circle mr-1 text-emerald-500"></i> Disetujui</span>
                                                    <span class="text-[9px] font-bold text-gray-700 text-center leading-tight">{{ $item->approver?->profile?->nama_lengkap ?? 'Sistem' }}</span>
                                                    <span class="text-[8px] font-semibold text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded mt-0.5 uppercase tracking-wide">{{ $item->penilai_role }}</span>
                                                </div>
                                            @elseif($item->status_approval === 'rejected')
                                                <span class="text-[8px] font-black text-red-600 uppercase bg-red-50 py-1 px-2 rounded border border-red-200">Ditolak</span>
                                                @if($isLevel1Or2 && $isFinalized)
                                                    <form action="{{ route('penilai.approval-skp-tendik.approve', $item->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="text-emerald-500 hover:text-emerald-700 text-[8px] font-bold uppercase mt-1">Setujui Ulang</button>
                                                    </form>
                                                @endif
                                            @else
                                                @if($canApproveThis)
                                                    <form action="{{ route('penilai.approval-skp-tendik.approve', $item->id) }}" method="POST" class="w-full mb-1">
                                                        @csrf
                                                        <button type="submit" class="w-full py-1 rounded text-[8px] font-black uppercase transition shadow-sm {{ $isFinalized ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}" {{ !$isFinalized ? 'disabled title="Menunggu finalisasi pegawai"' : '' }}>Setujui</button>
                                                    </form>
                                                    <button type="button" @if($isFinalized) @click="$dispatch('open-reject-modal', { id: {{ $item->id }} })" @endif class="w-full py-1 rounded text-[8px] font-black uppercase transition border {{ $isFinalized ? 'bg-red-50 text-red-600 hover:bg-red-100 border-red-200' : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' }}" {{ !$isFinalized ? 'disabled title="Menunggu finalisasi pegawai"' : '' }}>Tolak</button>
                                                @else
                                                    <span class="text-[8px] font-bold text-gray-400 italic">Bukan Penilai</span>
                                                @endif
                                            @endif

                                            @if($canEditTarget)
                                                <form action="{{ route('penilai.approval-skp-tendik.destroy', $item->id) }}" method="POST" class="mt-1" onsubmit="return confirm('Hapus tupoksi ini?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full text-red-400 hover:text-red-600 transition">
                                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TABLE 2: TARGET SKP --}}
            <div class="mb-2 ml-1 flex items-center gap-2">
                <div class="w-1.5 h-4 bg-amber-500 rounded-full"></div>
                <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Penetapan Target SKP</h3>
            </div>
            <div class="w-full shadow-md border border-gray-300 rounded-xl bg-white overflow-hidden mb-8">
                <div class="overflow-x-auto w-full custom-scrollbar">
                    <table class="w-full border-collapse bg-white">
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
                        <tbody id="skp-container">
                            @foreach($activities as $index => $item)
                                @php
                                    // Menggunakan ->get() dengan normalisasi string agar spasi/case tidak membuat missmatch
                                    $normalizedActivity = strtolower(preg_replace('/\s+/', ' ', trim($item->jenis_aktivitas)));
                                    $detail = collect($skpDetails)->get($normalizedActivity);
                                    $detail = $detail ? $detail->first() : null;
                                @endphp
                                <tr class="hover:bg-amber-50/10 transition-colors" id="row-skp-{{ $item->id }}">
                                    <td class="text-center text-[9px] font-bold text-slate-400 pt-3">
                                        <div class="display-cell center">{{ $index + 1 }}</div>
                                    </td>
                                    <td class="p-0">
                                        <textarea id="table2-activity-{{ $item->id }}" readonly class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)">{{ $item->jenis_aktivitas }}</textarea>
                                    </td>
                                    <td class="bg-amber-50/20 p-0">
                                        <input type="number" step="1" name="items[{{ $item->id }}][ak]" form="form-bulk-save" class="table-input font-bold text-blue-700 text-center" value="{{ round($detail->ak ?? 0) }}" required {{ !$canEditTarget ? 'readonly' : '' }}>
                                    </td>
                                    <td class="bg-amber-50/20 p-0">
                                        <input type="number" name="items[{{ $item->id }}][kuantitas]" form="form-bulk-save" class="table-input text-center font-bold" value="{{ $detail->kuantitas_output ?? 0 }}" required {{ !$canEditTarget ? 'readonly' : '' }}>
                                    </td>
                                    <td class="bg-amber-50/20 p-0">
                                        <div class="flex items-center">
                                            <input type="number" name="items[{{ $item->id }}][kualitas]" form="form-bulk-save" class="table-input text-center font-bold" value="{{ $detail->kualitas_mutu ?? 0 }}" required {{ !$canEditTarget ? 'readonly' : '' }}>
                                            <span class="text-[10px] font-bold text-amber-700 pr-1">%</span>
                                        </div>
                                    </td>
                                    <td class="bg-amber-50/20 p-0">
                                        <div class="flex items-center">
                                            <input type="number" name="items[{{ $item->id }}][waktu]" form="form-bulk-save" class="table-input text-center font-bold" value="{{ $detail->waktu ?? 1 }}" min="1" max="12" required oninput="if(this.value !== '' && this.value < 1) this.value = 1; if(this.value > 12) this.value = 12;" onkeypress="preventMinus(event)" {{ !$canEditTarget ? 'readonly' : '' }}>
                                            <span class="text-[9px] font-bold text-amber-700 pr-1">bln</span>
                                        </div>
                                    </td>
                                    <td class="bg-amber-50/20 p-0">
                                        <input type="number" name="items[{{ $item->id }}][biaya]" form="form-bulk-save" class="table-input text-right font-bold text-emerald-700" value="{{ $detail->biaya ?? 0 }}" {{ !$canEditTarget ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @endforeach

                            <tr id="tugas-tambahan-marker" style="display:none;"></tr>

                            {{-- TUGAS TAMBAHAN SECTION --}}
                            <tr class="font-bold bg-gray-100 border-b border-gray-800">
                                <td class="border-r border-gray-800 p-2 text-center font-bold text-gray-800">IV</td>
                                <td colspan="6" class="p-2">
                                    <div class="flex justify-between items-center w-full">
                                        <span class="font-black text-gray-900 uppercase">Tugas tambahan dan kreatifitas</span>
                                        @if($canEditTarget)
                                            <button type="button" @click="addNewTugasTambahanRow()" class="bg-blue-600 text-white text-[9px] px-3 py-1 rounded font-bold hover:bg-blue-700 uppercase transition shadow-sm">
                                                <i class="fas fa-plus mr-1"></i> + tugas tambahan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            @if(isset($tugasTambahan) && $tugasTambahan->count() > 0)
                                @foreach($tugasTambahan as $tidx => $tugas)
                                    <tr class="hover:bg-amber-50/10 transition-colors border-b border-gray-300">
                                        <td class="text-center font-bold text-slate-400 pt-3">
                                            <div class="display-cell center text-[9px]">{{ $tidx + 1 }}</div>
                                        </td>
                                        <td class="p-0 align-top">
                                            @if($canEditTarget)
                                                <input type="text" name="realisasi_tugas[{{ $tugas->id }}][jenis]" form="form-bulk-save" value="{{ $tugas->jenis }}" class="table-input font-bold" required placeholder="Jenis (misal: Tugas Tambahan)">
                                            @else
                                                <div class="p-2 font-semibold text-gray-700 uppercase text-[9px]">{{ $tugas->jenis }}</div>
                                            @endif
                                        </td>
                                        <td class="bg-amber-50/20 p-0 text-center align-middle">
                                            <input type="number" step="0.01" name="realisasi_tugas[{{ $tugas->id }}][ak]" form="form-bulk-save" value="{{ $tugas->ak }}" class="table-input font-bold text-indigo-700 text-center bg-gray-50" readonly>
                                        </td>
                                        <td colspan="4" class="p-0 align-top">
                                            @if($canEditTarget)
                                                <textarea name="realisasi_tugas[{{ $tugas->id }}][kegiatan]" form="form-bulk-save" class="table-input" rows="2" required placeholder="Nama kegiatan tambahan..." oninput="autoResize(this)">{{ $tugas->kegiatan }}</textarea>
                                            @else
                                                <div class="p-2 font-bold text-gray-900 text-[11px] leading-snug">{{ $tugas->kegiatan }}</div>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center align-middle bg-gray-50">
                                            @if($canEditTarget)
                                                <div class="flex flex-col gap-1 px-1">
                                                    <form action="{{ route('pegawai.skp.tugas-tambahan.destroy', $tugas->id) }}" method="POST" onsubmit="return confirm('Hapus tugas tambahan ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="w-full bg-red-50 text-red-600 py-1 rounded text-[8px] font-black uppercase hover:bg-red-100 transition shadow-sm border border-red-200">
                                                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-[8px] text-gray-400 italic">Terkunci</span>
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
            </div>

            {{-- TABEL PERILAKU --}}
            <div class="bg-white mb-8 w-full">
                <div class="mb-2 ml-1 flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-emerald-600 rounded-full"></div>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">B. Penyusunan Target Perilaku Kerja</h3>
                </div>

                <div class="w-full overflow-hidden rounded-xl border border-black shadow-sm">
                    <table class="w-full border-collapse text-sm table-skp-custom bg-white border-none" style="min-width: 800px;">
                        <thead>
                            <tr>
                                <th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white">UNSUR YANG DINILAI</th>
                                <th class="text-center font-bold bg-white w-32">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- A. SKP (60%) --}}
                            <tr class="border-b border-gray-300">
                                <td rowspan="10" class="text-center font-bold align-middle w-12 bg-white text-base border-r border-gray-800">4.</td>
                                <td colspan="2" class="font-bold bg-white italic border-r border-gray-800">a. Sasaran Kerja Pegawai (SKP)</td>
                                <td class="text-center bg-white w-32 border-r border-gray-800">-</td>
                                <td class="text-center bg-white w-16 border-r border-gray-800">x</td>
                                <td class="text-center font-bold bg-white w-20 border-r border-gray-800">60%</td>
                                <td class="text-center font-bold bg-white text-blue-700">-</td>
                            </tr>

                            {{-- B. PERILAKU KERJA (40%) --}}
                            @foreach (['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'] as $label)
                                <tr class="border-b border-gray-300">
                                    @if ($loop->first)
                                        <td rowspan="9" class="font-bold align-middle w-48 bg-white px-4 border-r border-gray-800 italic">b. Perilaku Kerja</td>
                                    @endif
                                    <td class="align-top bg-white cursor-pointer hover:bg-emerald-50 transition-colors border-r border-gray-800" @click="openAspect === '{{ $label }}' ? openAspect = null : openAspect = '{{ $label }}'">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="mt-1 text-[13px] font-bold">{{ $loop->iteration }}. {{ $label }} @if ($label == 'Kepemimpinan')
                                                    <i class="text-[9px] text-gray-400 font-normal lowercase tracking-tighter">(opsional)</i>
                                                @endif
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] text-emerald-500 font-bold uppercase tracking-tighter">Rincian</span>
                                                <i class="fas fa-chevron-down text-[9px] transition-transform text-emerald-500" :class="openAspect === '{{ $label }}' ? 'rotate-180' : ''"></i>
                                            </div>
                                        </div>

                                        {{-- RINCIAN INDIKATOR PER ASPEK --}}
                                        <div x-show="openAspect === '{{ $label }}'" class="flex flex-col gap-2 mt-2" style="display: none;" @click.stop>
                                            <div class="space-y-4">
                                                <div class="flex justify-between items-center">
                                                    <p class="font-black text-[10px] uppercase tracking-widest text-emerald-800">Detail Kegiatan: {{ $label }}</p>
                                                    @if ($canEditTarget)
                                                        <button type="button" @click.stop="addIndikator('{{ $label }}')"
                                                            class="bg-emerald-600 text-white text-[9px] px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-700 shadow-sm uppercase tracking-widest transition">
                                                            <i class="fas fa-plus mr-1"></i> Tambah Poin
                                                        </button>
                                                    @endif
                                                </div>
                                                <div class="space-y-3">
                                                    <template x-for="(item, i) in perilaku['{{ $label }}']" :key="i">
                                                        <div class="bg-white p-3 border border-emerald-200 rounded-xl shadow-sm relative group">
                                                            <div class="flex items-start gap-4 w-full">
                                                                <div class="flex-1 min-w-0">
                                                                    <label class="block text-[8px] font-bold text-gray-400 uppercase mb-1">Kegiatan / Bukti Pendukung</label>
                                                                    @if($canEditTarget)
                                                                        <textarea x-model="item.indikator" :name="`perilaku[${'{{ $label }}'}][${i}][indikator]`" form="form-bulk-save"
                                                                            class="w-full text-[10px] border-gray-300 rounded focus:ring-emerald-500 resize-y p-1.5 bg-gray-50/50" rows="2"
                                                                            placeholder="Tuliskan indikator perilaku..." required></textarea>
                                                                        <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][is_from_admin]`" x-model="item.is_from_admin" form="form-bulk-save">
                                                                        <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][nilai]`" x-model="item.nilai" form="form-bulk-save">
                                                                    @else
                                                                        <div class="p-1 text-[10px] text-gray-700 font-semibold" x-text="item.indikator"></div>
                                                                    @endif
                                                                </div>
                                                                <div class="w-20 shrink-0">
                                                                    <label class="block text-[8px] font-bold text-blue-500 uppercase mb-1 text-center">Persentase</label>
                                                                    <div class="relative">
                                                                        @if($canEditTarget)
                                                                            <input type="number" x-model.number="item.bobot_persen" :name="`perilaku[${'{{ $label }}'}][${i}][bobot_persen]`" form="form-bulk-save"
                                                                                step="1" min="0" max="100"
                                                                                class="w-full text-center px-1 py-1 pr-4 text-sm font-black text-blue-700 border-blue-300 rounded focus:ring-blue-500 no-scroll bg-white"
                                                                                @change="recalculateBobot('{{ $label }}', i)">
                                                                        @else
                                                                            <input type="number" x-model.number="item.bobot_persen"
                                                                                class="w-full text-center px-1 py-1 pr-4 text-sm font-black text-blue-700 border-blue-300 rounded focus:ring-blue-500 no-scroll bg-gray-50 cursor-not-allowed"
                                                                                disabled>
                                                                        @endif
                                                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-blue-500">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="w-20 shrink-0">
                                                                    <label class="block text-[8px] font-bold text-emerald-500 uppercase mb-1 text-center">Nilai</label>
                                                                    <input type="number" x-model.number="item.nilai"
                                                                        step="0.01" min="0" max="100"
                                                                        class="w-full text-center px-1 py-1 text-sm font-black text-emerald-700 border-emerald-300 rounded focus:ring-emerald-500 no-scroll bg-gray-50 cursor-not-allowed"
                                                                        disabled>
                                                                </div>
                                                                @if ($canEditTarget)
                                                                    <div class="shrink-0 pt-4">
                                                                        <button type="button" @click.stop="removeIndikator('{{ $label }}', i)"
                                                                            class="text-red-500 hover:text-red-700 transition transform hover:scale-110"
                                                                            title="Hapus">
                                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                    <td colspan="2" class="text-center align-middle bg-white text-emerald-700 font-bold text-[10px] uppercase tracking-tighter border-r border-gray-800">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>
                            @endforeach

                            {{-- FOOTER 7. JUMLAH --}}
                            <tr class="border-b border-gray-300 font-bold">
                                <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">7. Jumlah</td>
                                <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800">-</td>
                                <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                <td class="bg-grey-custom"></td>
                            </tr>

                            {{-- FOOTER 8. NILAI RATA-RATA --}}
                            <tr class="border-b border-gray-300 font-bold">
                                <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">8. Nilai rata &ndash; rata</td>
                                <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800">-</td>
                                <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                <td class="bg-grey-custom"></td>
                            </tr>

                            {{-- FOOTER 9. NILAI PERILAKU KERJA --}}
                            <tr class="border-b border-gray-300 font-bold">
                                <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">9. Nilai Perilaku Kerja</td>
                                <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800">-</td>
                                <td class="text-center align-middle bg-white border-r border-gray-800 font-bold italic">x</td>
                                <td class="text-center font-bold align-middle bg-white w-20 border-r border-gray-800">40%</td>
                                <td class="text-center font-bold align-middle bg-white text-emerald-800 text-lg">-</td>
                            </tr>

                            {{-- KESIMPULAN NILAI PRESTASI KERJA --}}
                            <tr class="border-t-[3px] border-double border-gray-800">
                                <td colspan="6" class="text-center font-bold text-lg p-3 bg-white tracking-widest uppercase">NILAI PRESTASI KERJA</td>
                                <td class="text-center font-bold text-lg p-3 bg-white text-indigo-800">(-)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($canEditTarget)
                <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4 pb-12">
                    <button type="submit" form="form-bulk-save"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 px-12 rounded-2xl shadow-2xl transform transition hover:scale-105 uppercase tracking-[0.1em] text-lg flex items-center gap-4">
                        <i class="fas fa-save"></i> Simpan Seluruh Perubahan
                    </button>
                </div>
            @endif
        </div>
        </div>

        {{-- REJECT MODAL --}}
        <div x-data="{ rejectModal: false, rejectId: null, rejectNote: '' }" 
             @open-reject-modal.window="rejectModal = true; rejectId = $event.detail.id">
            <div x-show="rejectModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm" style="display: none;">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all" @click.away="rejectModal = false">
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter mb-4">Tolak Kegiatan</h3>
                    <form :action="`/penilai/approval-skp-tendik/${rejectId}/reject`" method="POST">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Alasan Penolakan</label>
                            <textarea name="catatan" class="w-full border-gray-200 rounded-2xl focus:ring-red-500 font-bold text-sm bg-gray-50 p-4" rows="4" placeholder="Tuliskan alasan mengapa kegiatan ini ditolak (Opsional)..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="rejectModal = false" class="px-6 py-2.5 rounded-xl font-bold text-gray-500 hover:bg-gray-100 transition">Batal</button>
                            <button type="submit" class="px-8 py-2.5 rounded-xl font-bold bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-100 transition">Kirim Penolakan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function preventMinus(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            }

            let newRowCount = 0;
            const bidangList = @js($bidangList);
            const penilaiRoles = @js($penilaiRoles);

            function createNewActivityRow() {
                const tupoksiContainer = document.getElementById('tupoksi-container');
                const skpContainer = document.getElementById('skp-container');
                if(!tupoksiContainer || !skpContainer) return;

                const idx = 'new-' + (newRowCount++);
                const nextNo = tupoksiContainer.children.length + 1;

                // --- TABLE 1 ROW ---
                const trT = document.createElement('tr');
                trT.className = "hover:bg-slate-50 transition-colors approval-row";
                trT.id = `row-tupoksi-${idx}`;

                let bidangOptions = '<option value="">-- Pilih --</option>';
                bidangList.forEach(b => bidangOptions += `<option value="${b}">${b}</option>`);

                let penilaiOptions = '<option value="">-- Pilih --</option>';
                penilaiRoles.forEach(p => penilaiOptions += `<option value="${p.name}">${p.name}</option>`);

                trT.innerHTML = `
                    <td class="text-center font-bold text-slate-400 pt-3">
                        <div class="display-cell center text-[9px]">${nextNo}</div>
                    </td>
                    <td>
                        <select name="new_activities[${idx}][role_name]" form="form-bulk-save" class="table-input font-bold text-blue-800" onchange="fetchTupoksiByBidang(this)" required>
                            ${bidangOptions}
                        </select>
                    </td>
                    <td>
                        <select name="tupoksi_select" class="table-input font-bold text-gray-800" onchange="fillTupoksiData(this)" required>
                            <option value="">-- Pilih Tupoksi --</option>
                        </select>
                        <input type="hidden" name="new_activities[${idx}][tupoksi]" form="form-bulk-save" value="">
                    </td>
                    <td>
                        <textarea name="new_activities[${idx}][jenis_aktivitas]" form="form-bulk-save" class="table-input italic" rows="2"
                            oninput="autoResize(this); syncToTable2(this, '${idx}')" required placeholder="Pilih tupoksi..."></textarea>
                    </td>
                    <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Utama" form="form-bulk-save" checked class="w-3 h-3 text-blue-600 rounded"></td>
                    <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Periodik" form="form-bulk-save" class="w-3 h-3 text-blue-600 rounded"></td>
                    <td class="checkbox-cell"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Bukan Tupoksi" form="form-bulk-save" class="w-3 h-3 text-blue-600 rounded"></td>
                    <td class="text-center">
                        <select name="new_activities[${idx}][periode]" form="form-bulk-save" class="table-input text-center uppercase font-bold text-gray-500" required>
                            <option value="bulanan">BULANAN</option>
                            <option value="harian">HARIAN</option>
                            <option value="mingguan">MINGGUAN</option>
                            <option value="triwulan">TRIWULAN</option>
                            <option value="semester">SEMESTER</option>
                            <option value="tahunan">TAHUNAN</option>
                            <option value="kegiatan">KEGIATAN</option>
                        </select>
                    </td>
                    <td class="text-center"><input type="number" name="new_activities[${idx}][frekuensi_per_tahun]" form="form-bulk-save" class="table-input text-center inp-frekuensi font-bold" value="1" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                    <td class="text-center"><input type="number" name="new_activities[${idx}][durasi_menit]" form="form-bulk-save" class="table-input text-center inp-durasi font-bold" value="60" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                    <td class="text-center"><input type="number" name="new_activities[${idx}][jumlah_sdm]" form="form-bulk-save" class="table-input text-center inp-sdm font-bold" value="1" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                    <td class="bg-slate-50 text-right px-2 font-black text-indigo-700 text-[9px] pt-3 vol-display">60</td>
                    <td class="text-center align-middle">
                        <select name="new_activities[${idx}][penilai_role]" form="form-bulk-save" class="table-input text-[8px] font-black uppercase text-center" required>
                            ${penilaiOptions}
                        </select>
                    </td>
                    <td class="p-2 align-middle text-center">
                        <button type="button" onclick="document.getElementById('row-tupoksi-${idx}').remove(); document.getElementById('row-skp-${idx}').remove();" class="text-red-500 hover:text-red-700 transition active:scale-95" title="Batal">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </td>
                `;
                tupoksiContainer.appendChild(trT);

                // --- TABLE 2 ROW ---
                const trS = document.createElement('tr');
                trS.className = "hover:bg-amber-50/10 transition-colors";
                trS.id = `row-skp-${idx}`;
                trS.innerHTML = `
                    <td class="text-center font-bold text-slate-400 pt-3">
                        <div class="display-cell center text-[9px]">${nextNo}</div>
                    </td>
                    <td class="p-0">
                        <textarea id="table2-activity-${idx}" readonly class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)" placeholder="(Akan tersimpan saat Simpan Perubahan)"></textarea>
                    </td>
                    <td class="bg-amber-50/20"><input type="number" step="0.01" name="new_activities[${idx}][target_ak]" form="form-bulk-save" class="table-input font-bold text-blue-700" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" name="new_activities[${idx}][target_kuantitas]" form="form-bulk-save" class="table-input text-center" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" min="0" max="100" name="new_activities[${idx}][target_kualitas]" form="form-bulk-save" class="table-input text-center" placeholder="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" min="1" max="12" name="new_activities[${idx}][target_waktu]" form="form-bulk-save" class="table-input text-center" placeholder="1" oninput="if(this.value !== '' && this.value < 1) this.value = 1; if(this.value > 12) this.value = 12;" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" name="new_activities[${idx}][target_biaya]" form="form-bulk-save" class="table-input text-right font-bold text-emerald-700" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                `;
                
                const marker = document.getElementById('tugas-tambahan-marker');
                if (marker) {
                    skpContainer.insertBefore(trS, marker);
                } else {
                    skpContainer.appendChild(trS);
                }
            }

            function addNewTugasTambahanRow() {
                const skpContainer = document.getElementById('skp-container');
                if(!skpContainer) return;

                const idx = 'tugas-new-' + (newRowCount++);
                const trS = document.createElement('tr');
                trS.className = "hover:bg-amber-50/10 transition-colors border-b border-gray-200";
                trS.id = `row-tugas-${idx}`;

                trS.innerHTML = `
                    <td class="text-center font-bold text-slate-400 pt-3">
                        <div class="display-cell center text-[9px]">NEW</div>
                    </td>
                    <td class="p-0 align-top">
                        <input type="text" name="new_tugas[${idx}][jenis]" form="form-bulk-save" class="table-input font-bold" required placeholder="Jenis (misal: Tugas Tambahan)">
                    </td>
                    <td class="bg-amber-50/20 p-0 text-center align-middle">
                        <input type="number" step="0.01" name="new_tugas[${idx}][ak]" form="form-bulk-save" class="table-input font-bold text-indigo-700 text-center bg-gray-50" value="1.00" readonly>
                    </td>
                    <td colspan="4" class="p-0 align-top">
                        <textarea name="new_tugas[${idx}][kegiatan]" form="form-bulk-save" class="table-input" rows="2" required placeholder="Nama kegiatan tambahan..." oninput="autoResize(this)"></textarea>
                    </td>
                    <td class="p-2 text-center align-middle bg-gray-50">
                        <button type="button" onclick="document.getElementById('row-tugas-${idx}').remove();" class="text-red-500 hover:text-red-700 transition active:scale-95" title="Batal">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </td>
                `;

                skpContainer.appendChild(trS);
            }

            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            function syncToTable2(el, id) {
                const target = document.getElementById('table2-activity-' + id);
                if (target) {
                    target.value = el.value || '';
                    autoResize(target);
                }
            }

            window.fetchTupoksiByBidang = function(el) {
                const container = el.closest('tr') || el.closest('form');
                if(!container) return;
                const tupoksiSelect = container.querySelector('select[name*="tupoksi_select"]');
                const bidang = el.value;

                if(bidang === "") {
                    tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                    return;
                }

                tupoksiSelect.innerHTML = '<option value="">Memuat...</option>';

                fetch('/lookup/daftar-tupoksi/bidang/' + encodeURIComponent(bidang))
                    .then(res => res.json())
                    .then(data => {
                        tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = JSON.stringify(item);
                            option.text = item.tupoksi;
                            tupoksiSelect.appendChild(option);
                        });
                    });
            }

            window.fillTupoksiData = function(el) {
                const container = el.closest('tr') || el.closest('form');
                if(!container) return;

                const hiddenTupoksi = container.querySelector('input[name*="[tupoksi]"]');
                const aktivitasTextarea = container.querySelector('textarea[name*="[jenis_aktivitas]"]');
                const periodeSelect = container.querySelector('select[name*="[periode]"]');
                const frekuensiInput = container.querySelector('input[name*="[frekuensi_per_tahun]"]');
                const durasiInput = container.querySelector('input[name*="[durasi_menit]"]');
                const sdmInput = container.querySelector('input[name*="[jumlah_sdm]"]');

                if(el.value === "") {
                    if(hiddenTupoksi) hiddenTupoksi.value = "";
                    return;
                }

                const data = JSON.parse(el.value);
                if(hiddenTupoksi) hiddenTupoksi.value = data.tupoksi;

                // Sync ke Table 2 berdasarkan Tupoksi (Col II)
                const row = el.closest('.approval-row');
                if (row) {
                    const trId = row.id;
                    const id = trId.replace('row-tupoksi-', '');
                    const targetTable2 = document.getElementById('table2-activity-' + id);
                    if (targetTable2) {
                        targetTable2.value = data.tupoksi || '';
                        autoResize(targetTable2);
                    }
                }

                if (aktivitasTextarea) {
                    aktivitasTextarea.value = data.jenis_aktivitas || data.tupoksi;
                    autoResize(aktivitasTextarea);
                }

                if (periodeSelect && data.periode) periodeSelect.value = data.periode.toLowerCase();
                if (frekuensiInput && data.frekuensi_per_tahun) frekuensiInput.value = data.frekuensi_per_tahun;
                if (durasiInput && data.durasi_menit) durasiInput.value = data.durasi_menit;
                if (sdmInput && data.jumlah_sdm) sdmInput.value = data.jumlah_sdm;

                recalcVolume(container);
            }

            function recalcVolume(el) {
                const row = el.closest('tr') || el.closest('form');
                if (!row) return;

                const freqEl = row.querySelector('.inp-frekuensi') || row.querySelector('[name*="[frekuensi_per_tahun]"]');
                const durEl = row.querySelector('.inp-durasi') || row.querySelector('[name*="[durasi_menit]"]');
                const sdmEl = row.querySelector('.inp-sdm') || row.querySelector('[name*="[jumlah_sdm]"]');

                if(!freqEl || !durEl || !sdmEl) return;

                const freq = parseFloat(freqEl.value) || 0;
                const dur = parseFloat(durEl.value) || 0;
                const sdm = sdmEl.tagName === 'INPUT' ? (parseFloat(sdmEl.value) || 0) : (parseFloat(sdmEl.innerText) || 0);

                const volume = freq * dur * sdm;
                const volDisplay = row.querySelector('.vol-display');
                if (volDisplay) volDisplay.innerText = volume.toLocaleString('id-ID');
            }

            // Initialize dropdowns and textareas on load
            document.addEventListener('DOMContentLoaded', function() {
                // Hapus fitur scroll number (mencegah scroll merubah nilai input type number)
                document.addEventListener('wheel', function(event) {
                    if (document.activeElement.type === 'number') {
                        document.activeElement.blur();
                    }
                }, {
                    passive: false
                });

                // Auto-resize existing textareas
                document.querySelectorAll('textarea.table-input').forEach(textarea => {
                    autoResize(textarea);
                });
            });
        </script>
    @endsection
</x-app-layout>
