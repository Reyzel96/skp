<x-app-layout>
    @section('page-title', 'Tambah SKP Tendik')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <script>
            function preventMinus(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
            }

            function setupMasterSKP() {
                return {
                    setupModal: {{ old('user_id') ? 'false' : 'true' }},
                    confirmModal: false,
                    pegawaiId: '{{ old('user_id', '') }}',
                    pegawaiName: '......................',
                    pegawaiNIP: '......................',
                    pegawaiPangkat: '......................',
                    pegawaiJabatan: '......................',

                    periodeId: '{{ old('periode_skp_id', '') }}',
                    periodeText: '......................',

                    penilaiId: '{{ old('pejabat_penilai_id', '') }}',
                    penilaiName: '......................',
                    penilaiNIP: '......................',
                    penilaiPangkat: '......................',
                    penilaiJabatan: '......................',

                    atasanId: '{{ old('atasan_pejabat_penilai_id', '') }}',
                    atasanName: '......................',
                    atasanNIP: '......................',
                    atasanPangkat: '......................',
                    atasanJabatan: '......................',

                    // --- State Alpine untuk Form Tugas Tambahan ---
                    tugasTambahan: [
                        { tugas_tambahan: '', detail: '' }
                    ],
                    addTugasTambahan() {
                        this.tugasTambahan.push({ tugas_tambahan: '', detail: '' });
                    },
                    removeTugasTambahan(index) {
                        this.tugasTambahan.splice(index, 1);
                    },
                    // ----------------------------------------------

                    allPegawai: {{ Js::from($jsonPegawai) }},
                    allPenilai: {{ Js::from($jsonPenilai) }},

                    perilakuCategories: {{ Js::from($perilakuCategories) }},
                    rebalancePercentages(catIndex) {
                        let indicators = this.perilakuCategories[catIndex].indicators;
                        let count = indicators.length;
                        if (count === 0) return;
                        if (count === 1) {
                            indicators[0].persentase = 100;
                            return;
                        }
                        let base = (100 / count).toFixed(2);
                        let assigned = 0;
                        for (let i = 0; i < count - 1; i++) {
                            indicators[i].persentase = parseFloat(base);
                            assigned += parseFloat(base);
                        }
                        indicators[count - 1].persentase = parseFloat((100 - assigned).toFixed(2));
                    },
                    addIndicator(catIndex) {
                        this.perilakuCategories[catIndex].indicators.push({ text: "", persentase: 0 });
                        this.rebalancePercentages(catIndex);
                    },
                    removeIndicator(catIndex, indIndex) {
                        this.perilakuCategories[catIndex].indicators.splice(indIndex, 1);
                        this.rebalancePercentages(catIndex);
                    },
                    updatePercentage(catIndex, indIndex, newValue) {
                        let indicators = this.perilakuCategories[catIndex].indicators;
                        let count = indicators.length;
                        if (count <= 1) {
                            if(count === 1) indicators[0].persentase = 100;
                            return;
                        }

                        let val = parseFloat(newValue);
                        if (isNaN(val)) val = 0;
                        if (val > 100) val = 100;
                        if (val < 0) val = 0;
                        
                        indicators[indIndex].persentase = val;
                        
                        let remaining = 100 - val;
                        let otherCount = count - 1;
                        let otherVal = (remaining / otherCount).toFixed(2);
                        
                        let assignedOther = 0;
                        for (let i = 0; i < count; i++) {
                            if (i !== indIndex) {
                                if (i === count - 1 || (indIndex === count - 1 && i === count - 2)) {
                                    indicators[i].persentase = parseFloat((remaining - assignedOther).toFixed(2));
                                } else {
                                    indicators[i].persentase = parseFloat(otherVal);
                                    assignedOther += parseFloat(otherVal);
                                }
                            }
                        }
                    },
                    getTotalPersentase(catIndex) {
                        return this.perilakuCategories[catIndex].indicators.reduce((acc, curr) => acc + parseFloat(curr.persentase || 0), 0);
                    },
                    get isPersentaseValid() {
                        for(let i=0; i<this.perilakuCategories.length; i++) {
                            let cat = this.perilakuCategories[i];
                            if (cat.indicators.length > 0) {
                                let total = this.getTotalPersentase(i);
                                if (total !== 100) return false;
                            }
                        }
                        return true;
                    },

                    updatePegawaiInfo(id) {
                        let u = this.allPegawai.find(x => x.id == id);
                        if(u) {
                            this.pegawaiName = u.nama;
                            this.pegawaiNIP = u.nip;
                            this.pegawaiPangkat = u.pangkat;
                            this.pegawaiJabatan = u.jabatan;

                            setTimeout(() => {
                                document.querySelectorAll('select[name$="[role_name]"]').forEach(select => {
                                    if (u.jabatan && u.jabatan !== '......................') {
                                        const jabatans = u.jabatan.split(',').map(j => j.trim());
                                        let options = '<option value="">-- Pilih --</option>';
                                        jabatans.forEach(jab => {
                                            const selected = (jabatans.length === 1) ? 'selected' : '';
                                            options += `<option value="${jab}" ${selected}>${jab}</option>`;
                                        });
                                        select.innerHTML = options;
                                        if (typeof window.fetchTupoksiByBidang === 'function') {
                                            window.fetchTupoksiByBidang(select);
                                        }
                                    }
                                });
                            }, 50);
                        } else {
                            this.pegawaiName = this.pegawaiNIP = this.pegawaiPangkat = this.pegawaiJabatan = '......................';
                            
                            setTimeout(() => {
                                document.querySelectorAll('select[name$="[role_name]"]').forEach(select => {
                                    select.innerHTML = '<option value="">-- Pilih --</option>';
                                    if (typeof window.fetchTupoksiByBidang === 'function') {
                                        window.fetchTupoksiByBidang(select);
                                    }
                                });
                            }, 50);
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
                            this.penilaiName = this.penilaiNIP = this.penilaiPangkat = this.penilaiJabatan = '......................';
                        }
                    },
                    updateAtasanInfo(id) {
                        let u = this.allPenilai.find(x => x.id == id);
                        if(u) {
                            this.atasanName = u.nama;
                            this.atasanNIP = u.nip;
                            this.atasanPangkat = u.pangkat;
                            this.atasanJabatan = u.jabatan;
                        } else {
                            this.atasanName = this.atasanNIP = this.atasanPangkat = this.atasanJabatan = '......................';
                        }
                    },

                    get isValidSetup() { return this.pegawaiId !== '' && this.periodeId !== '' && this.penilaiId !== '' && this.atasanId !== ''; }
                }
            }
        </script>

        <style>
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

            .table-input:focus {
                outline: 2px solid #3b82f6 !important;
                background-color: #f0f9ff;
                z-index: 10;
                position: relative;
            }

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

            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            ::-webkit-scrollbar-thumb {
                background-color: #cbd5e1;
                border-radius: 3px;
            }

            [x-cloak] {
                display: none !important;
            }

            .drag-active {
                border-color: #3b82f6 !important;
                background-color: #eff6ff !important;
            }

            /* CSS TAMBAHAN UNTUK TABEL PERILAKU */
            .table-skp-custom {
                border-collapse: collapse !important;
                border-style: hidden !important; /* Menyembunyikan border terluar tabel agar tidak dobel dengan wrapper */
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

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans" x-data="setupMasterSKP()">
            <form action="{{ route('superadmin.master-skp-tendik.store') }}" method="POST" id="mainForm"
                class="w-full grid grid-cols-1" x-on:submit="if(!isPersentaseValid) { alert('Total persentase perilaku harus 100% pada kategori yang memiliki indikator!'); $event.preventDefault(); return false; }">
                @csrf

                <input type="hidden" name="periode_skp_id" x-model="periodeId">
                <input type="hidden" name="user_id" x-model="pegawaiId">
                <input type="hidden" name="pejabat_penilai_id" x-model="penilaiId">
                <input type="hidden" name="atasan_pejabat_penilai_id" x-model="atasanId">

                {{-- 1. MODAL PERSIAPAN AWAL (PILIH TENDIK & PERIODE) --}}
                <template x-teleport="body">
                    <div x-show="setupModal" style="z-index: 9999 !important;"
                        class="fixed inset-0 flex items-center justify-center bg-gray-900/90 backdrop-blur-sm" x-cloak>
                        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-5xl w-full mx-4 transform transition-all max-h-[90vh] overflow-y-auto relative animate-in zoom-in duration-300">

                            <div class="text-center mb-4 relative">
                                <h2 class="text-xl font-black text-gray-800 tracking-tight uppercase">Persiapan Awal <span class="text-blue-600">Master SKP</span></h2>
                                <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest font-bold">Lengkapi data dasar pegawai, pejabat penilai & atasan penilai</p>
                                <a href="{{ route('superadmin.master-skp-tendik.index') }}" class="absolute top-0 right-0 text-gray-400 hover:text-red-600 transition bg-gray-100 hover:bg-red-50 rounded-full w-8 h-8 flex items-center justify-center">
                                    <i class="fas fa-times text-sm"></i>
                                </a>
                            </div>

                            <div class="space-y-4">
                                @php
                                    $sortedUsersTendik = $usersTendik->sortBy(
                                        fn($u) => $u->profile->nama_lengkap ?? $u->name,
                                    );
                                @endphp

                                {{-- PILIH PERIODE --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-1.5 ml-1 tracking-wider">1. Pilih Periode SKP *</label>
                                    <select x-model="periodeId"
                                        @change="periodeText = $event.target.options[$event.target.selectedIndex].text" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold bg-white text-xs text-gray-700">
                                        <option value="">-- Pilih Periode SKP --</option>
                                        @foreach ($periode as $p)
                                            <option value="{{ $p->id }}">
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

                                {{-- TABEL SETUP IDENTITAS --}}
                                <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-[11px] overflow-hidden">
                                    <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">
                                        {{-- SISI KIRI: PEGAWAI --}}
                                        <div class="flex-1 flex flex-col min-w-0 bg-white">
                                            <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                                <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0 font-black">I</div>
                                                <div class="flex-1 px-3 text-blue-800 flex items-center text-[10px] gap-2 min-w-0 truncate tracking-tight font-black uppercase">PEGAWAI TENDIK (PILIH) *</div>
                                            </div>
                                            <div class="p-3 border-b border-gray-200 bg-gray-50">
                                                <select name="user_id" x-model="pegawaiId" @change="updatePegawaiInfo($el.value)" :disabled="!periodeId" required
                                                    class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 font-bold text-gray-800 bg-white text-xs disabled:bg-gray-100 disabled:cursor-not-allowed">
                                                    <option value="">-- Pilih Pegawai --</option>
                                                    @foreach ($sortedUsersTendik as $ut)
                                                        @php
                                                            $role = $ut->getRoleNames()->first() ?? '-';
                                                        @endphp
                                                        <option value="{{ $ut->id }}">[{{ $role }}] {{ $ut->profile->nama_lengkap ?? $ut->name }}</option>
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

                                        {{-- SISI KANAN: PEJABAT PENILAI --}}
                                        <div class="flex-1 flex flex-col min-w-0 bg-white">
                                            <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                                <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black">II</div>
                                                <div class="flex-1 px-3 text-emerald-800 flex items-center text-[10px] gap-2 min-w-0 truncate tracking-tight font-black uppercase">PEJABAT PENILAI (PILIH) *</div>
                                            </div>
                                            <div class="p-3 border-b border-gray-200 bg-gray-50">
                                                <select x-model="penilaiId" @change="updatePenilaiInfo($el.value)" required
                                                    class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-emerald-500 font-bold text-gray-800 bg-white text-xs">
                                                    <option value="">-- Pilih Penilai --</option>
                                                    @foreach ($pejabatPenilaiList as $cp)
                                                        @php
                                                            $role = $cp->getRoleNames()->first() ?? '-';
                                                        @endphp
                                                        <option value="{{ $cp->id }}">[{{ $role }}] {{ $cp->profile->nama_lengkap ?? $cp->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">1</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Nama</div>
                                                <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiName"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">2</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">NIP</div>
                                                <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiNIP"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">3</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Pangkat/Golongan</div>
                                                <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiPangkat"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">4</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Jabatan</div>
                                                <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiJabatan"></div>
                                            </div>
                                            <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">5</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Unit Kerja</div>
                                                <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- BARIS 2: ATASAN PENILAI VERTIKAL FULL WIDTH --}}
                                    <div class="bg-white flex flex-col min-w-0">
                                        <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0 font-black">III</div>
                                            <div class="flex-1 px-3 text-indigo-800 flex items-center text-[10px] gap-2 min-w-0 truncate tracking-tight font-black uppercase">ATASAN PEJABAT PENILAI (PILIH) *</div>
                                        </div>
                                        <div class="p-3 border-b border-gray-200 bg-gray-50">
                                            <select x-model="atasanId" @change="updateAtasanInfo($el.value)" required
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 font-bold text-gray-800 bg-white text-xs text-gray-800">
                                                <option value="">-- Pilih Atasan Penilai --</option>
                                                @foreach ($atasanPenilaiList as $cp)
                                                    @php
                                                        $role = $cp->getRoleNames()->first() ?? '-';
                                                    @endphp
                                                    <option value="{{ $cp->id }}">[{{ $role }}] {{ $cp->profile->nama_lengkap ?? $cp->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex flex-col w-full">
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">1</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Nama</div>
                                                <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanName"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">2</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">NIP</div>
                                                <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanNIP"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">3</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Pangkat/Golongan</div>
                                                <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanPangkat"></div>
                                            </div>
                                            <div class="flex border-b border-gray-200 hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">4</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Jabatan</div>
                                                <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanJabatan"></div>
                                            </div>
                                            <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                                <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full">5</div>
                                                <div class="w-32 px-3 text-[11px] text-gray-600 font-medium shrink-0 flex items-center h-full">Unit Kerja</div>
                                                <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-col-reverse sm:flex-row gap-3">
                                    <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                                        class="w-full sm:w-1/3 py-3 rounded-xl font-bold text-center tracking-wider uppercase transition-all border border-gray-300 text-gray-600 hover:bg-gray-100 text-[10px]">
                                        BATAL / KEMBALI
                                    </a>
                                    <button type="button" :disabled="!isValidSetup" @click="setupModal = false"
                                        class="w-full sm:w-2/3 py-3 rounded-xl font-black tracking-wider uppercase transition-all shadow-lg bg-blue-600 hover:bg-blue-700 text-white transform hover:-translate-y-1 flex justify-center items-center gap-2 text-[10px]">
                                        <span>LANJUTKAN PENGISIAN</span> <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 3. MODAL KONFIRMASI SIMPAN --}}
                <div x-show="confirmModal"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm" x-cloak>
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 text-center"
                        @click.away="confirmModal = false">
                        <div
                            class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-yellow-50 shadow-sm">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-600"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-800 mb-2">Konfirmasi Penyimpanan</h3>
                        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Apakah Anda yakin sudah mengecek seluruh data (Bidang, Tupoksi, Penilai, dll) dengan teliti?</p>

                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button type="button" @click="confirmModal = false"
                                class="px-6 py-2.5 bg-gray-100 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition w-full sm:w-auto">Cek Kembali</button>
                            <button type="button" @click="document.getElementById('mainForm').submit()"
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition w-full sm:w-auto shadow-lg">
                                <i class="fas fa-check-circle mr-2"></i> Ya, Simpan Sekarang
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MAIN CONTENT --}}
                <div x-show="!setupModal" x-cloak>
                    {{-- HEADER IDENTITAS UTAMA (BIODATA TABLE) --}}
                    <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">
                            {{-- SISI KIRI: PEGAWAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0 font-black tracking-widest">I</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-blue-800 min-w-0">
                                        <span class="truncate text-[10px] font-black tracking-tight">PEGAWAI YANG DINILAI</span>
                                        <button type="button" @click="setupModal = true" class="text-white bg-blue-600 px-2.5 py-1.5 rounded-lg text-[9px] shadow-lg shadow-blue-200 tracking-wider shrink-0 ml-2 hover:bg-blue-700 transition-all font-black uppercase flex items-center gap-1.5 active:scale-95">
                                            <i class="fas fa-user-edit"></i> GANTI PENILAI
                                        </button>
                                    </div>
                                </div>

                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>

                            {{-- SISI KANAN: PEJABAT PENILAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black tracking-widest">II</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-emerald-800 min-w-0">
                                        <span class="truncate text-[10px] font-black tracking-tight">PEJABAT PENILAI</span>
                                        <span class="text-white bg-emerald-600 px-2.5 py-1.5 rounded-lg text-[9px] shadow-lg shadow-emerald-100 tracking-wider shrink-0 ml-2 font-black uppercase" x-text="periodeText">
                                            -
                                        </span>
                                    </div>
                                </div>

                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>
                        </div>

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
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="atasanJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-4 rounded-r-lg shadow-sm animate-in fade-in duration-500">
                            <p class="font-bold text-sm"><i class="fas fa-check-circle mr-1"></i> Berhasil!</p>
                            <p class="text-xs">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg shadow-sm animate-in fade-in duration-500">
                            <p class="font-bold text-sm"><i class="fas fa-exclamation-triangle mr-1"></i> Error!</p>
                            <p class="text-xs">{{ session('error') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg shadow-sm">
                            <p class="font-bold text-sm mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Gagal Menyimpan Data. Periksa hal berikut:</p>
                            <ul class="list-disc list-inside text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">📝 A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</h2>
                    </div>

                    {{-- TABLE 1: TUPOKSI & BEBAN KERJA --}}
                    <div class="mb-2 ml-1 flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                        <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Rincian Tupoksi & Beban Kerja</h3>
                    </div>
                    <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-4">
                        <table class="w-full table-fixed border-collapse bg-white">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th rowspan="2" style="width: 3%;">No</th>
                                    <th rowspan="2" style="width: 8%;">Bidang</th>
                                    <th rowspan="2" style="width: 14%;">Tupoksi</th>
                                    <th rowspan="2" style="width: 15%;">Jenis Aktivitas Unit Kerja</th>
                                    <th rowspan="2" style="width: 7%;">Penilai</th>
                                    <th colspan="3" style="width: 6%;" class="border-b border-slate-300">Kategori</th>
                                    <th rowspan="2" style="width: 6%;">Periode</th>
                                    <th rowspan="2" style="width: 5%;">Freq</th>
                                    <th rowspan="2" style="width: 5%;">Dur</th>
                                    <th rowspan="2" style="width: 4%;">SDM</th>
                                    <th rowspan="2" style="width: 6%;">Vol Kerja</th>
                                    <th rowspan="2" style="width: 4%;" class="bg-white border-none"></th>
                                </tr>
                                <tr class="bg-gray-100 text-[8px] text-gray-500">
                                    <th style="width: 2%;" class="px-0 border-r border-slate-300">Utm</th>
                                    <th style="width: 2%;" class="px-0 border-r border-slate-300">Prd</th>
                                    <th style="width: 2%;" class="px-0">Bkn</th>
                                </tr>
                            </thead>
                            <tbody id="tupoksi-container"></tbody>
                        </table>
                    </div>

                    {{-- TOMBOL TAMBAH BARIS --}}
                    <div class="mt-2 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-300 w-full mb-10">
                        <button type="button" id="btn-add-row"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg text-xs font-black hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center justify-center gap-2">
                            + Tambah Baris
                        </button>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight italic">Menambahkan baris akan otomatis sinkron ke Tabel 1 dan Tabel 2</div>
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

                            {{-- TARGET SKP UTAMA --}}
                            <tbody id="skp-container"></tbody>

                            {{-- ======================================================== --}}
                            {{-- START: HEADER & LIST TUGAS TAMBAHAN DAN KREATIVITAS      --}}
                            {{-- ======================================================== --}}
                            <tbody class="border-t-[3px] border-double border-gray-400">
                                <tr class="bg-gray-100 text-gray-800 h-10">
                                    <th class="border border-slate-300"></th>
                                    <th class="text-left px-3 font-bold uppercase text-[11px] border border-slate-300 tracking-wider">
                                        <div class="flex justify-between items-center w-full">
                                            <span>II. TUGAS TAMBAHAN DAN KREATIVITAS:</span>
                                            <button type="button" @click="addTugasTambahan()" class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wider flex items-center gap-1 bg-white px-2 py-1 rounded border border-blue-200 shadow-sm transition-colors">
                                                <i class="fas fa-plus-circle"></i> Tambah Baris
                                            </button>
                                        </div>
                                    </th>
                                    <th class="border border-slate-300 bg-amber-50"></th>
                                    <th colspan="4" class="border border-slate-300 bg-amber-50"></th>
                                </tr>
                            </tbody>

                            <tbody>
                                <template x-for="(item, index) in tugasTambahan" :key="index">
                                    <tr class="hover:bg-amber-50/10 transition-colors">
                                        <td class="text-center text-[10px] font-bold text-slate-400 pt-3 border border-slate-300 align-top" x-text="index + 1"></td>
                                        <td class="p-0 border border-slate-300 align-top">
                                            <textarea x-model="item.tugas_tambahan" :name="'tugas_tambahan['+index+'][tugas_tambahan]'" class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)" placeholder="Input tugas tambahan..."></textarea>
                                        </td>
                                        <td class="bg-amber-50/20 border border-slate-300 p-0 text-center align-top relative">
                                            <input type="number" :name="'tugas_tambahan['+index+'][ak]'" value="1" readonly class="table-input font-bold text-blue-700 text-center bg-gray-50 cursor-not-allowed h-full min-h-[40px]">
                                        </td>
                                        <td colspan="4" class="p-0 border border-slate-300 align-top relative pr-10">
                                            <textarea x-model="item.detail" :name="'tugas_tambahan['+index+'][detail]'" class="table-input text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)" placeholder="Input detail tugas tambahan..."></textarea>

                                            <div class="absolute top-2 right-2 flex items-center">
                                                <button type="button" @click="removeTugasTambahan(index)" class="text-red-400 hover:text-red-600 bg-white rounded border border-red-200 p-1.5 shadow-sm transition-all" title="Hapus Tugas Tambahan">
                                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            {{-- ======================================================== --}}
                            {{-- END: HEADER & LIST TUGAS TAMBAHAN DAN KREATIVITAS        --}}
                            {{-- ======================================================== --}}
                        </table>
                    </div>

                    {{-- TABEL PERILAKU (Diperbarui dengan rounded curve corner melalui wrapper) --}}
                    <div class="bg-white mb-8 w-full overflow-x-auto pb-4 pt-1">
                        <div class="border border-black rounded-xl overflow-hidden min-w-full inline-block align-middle shadow-sm">
                            <table class="w-full text-sm table-skp-custom bg-white">
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
                                                <div class="flex justify-between items-center mb-1">
                                                    <span x-text="(catIndex + 1) + '. ' + cat.name" class="mt-1 text-[13px]"></span>
                                                    <button type="button" @click="addIndicator(catIndex)" class="bg-gray-100 border border-gray-300 text-gray-700 text-[10px] px-2 py-1 rounded font-bold hover:bg-gray-200 transition" title="Tambah">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>

                                                <div class="flex flex-col gap-2 mt-2">
                                                    <template x-for="(ind, indIndex) in cat.indicators" :key="indIndex">
                                                        <div class="flex items-start gap-2 bg-gray-50 p-2 border border-gray-300 rounded shadow-sm">
                                                            <div class="flex-1 min-w-0">
                                                                <textarea :name="'perilaku[' + cat.name + '][indicators]['+indIndex+'][text]'" x-model="ind.text"
                                                                    class="w-full text-xs border-gray-300 rounded focus:ring-blue-500 resize-y p-1.5" rows="2"
                                                                    oninput="autoResize(this)" placeholder="Form Input unsur kegiatan" required></textarea>
                                                            </div>
                                                            <div class="w-20 shrink-0 flex flex-col items-center justify-center">
                                                                <span class="text-[9px] font-bold text-gray-500 uppercase">Persentase</span>
                                                                <input type="number" step="0.01" min="1" max="100" required :name="'perilaku[' + cat.name + '][indicators]['+indIndex+'][persentase]'" x-model="ind.persentase" @input="updatePercentage(catIndex, indIndex, $event.target.value)"
                                                                    class="w-full text-center text-xs border-gray-300 rounded p-1 font-bold text-blue-700">
                                                            </div>
                                                            <div class="shrink-0 pt-3">
                                                                <button type="button" @click="removeIndicator(catIndex, indIndex)" class="text-red-400 hover:text-red-600 p-1" title="Hapus">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="cat.indicators.length > 0 && getTotalPersentase(catIndex) !== 100">
                                                        <div class="text-xs font-bold text-red-500 mt-1">
                                                            <i class="fas fa-exclamation-triangle"></i> Total persentase harus 100% (Saat ini: <span x-text="getTotalPersentase(catIndex)"></span>%)
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

                    <div class="mt-4 flex flex-col sm:flex-row justify-end items-center gap-4 bg-gray-50 p-4 rounded-lg border w-full">
                        <button type="button"
                            @click="
                                if(tupoksiContainer.children.length === 0) {
                                    alert('Gagal! Tabel masih kosong. Silakan tambah baris terlebih dahulu.');
                                } else if(document.getElementById('mainForm').reportValidity()) {
                                    confirmModal = true;
                                }
                            "
                            class="w-full sm:w-auto px-10 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-lg hover:bg-blue-700 transition uppercase tracking-widest text-center">
                            <i class="fas fa-save mr-1"></i> Simpan Data (<span id="count-display">0</span>)
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <script>
            let rowCount = 0;
            const tupoksiContainer = document.getElementById('tupoksi-container');
            const skpContainer = document.getElementById('skp-container');
            const countDisplay = document.getElementById('count-display');

            const rawBidang = {{ Js::from($bidangList ?? []) }};
            const availableBidang = Array.isArray(rawBidang) ? rawBidang : Object.values(rawBidang);
            const penilaiRoles = {{ Js::from($penilaiRoles ?? []) }};

            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            function updateCounter() {
                if(countDisplay) countDisplay.innerText = tupoksiContainer.children.length;
            }

            function reindexForms() {
                const tupoksiRows = tupoksiContainer.children;
                const skpRows = skpContainer.children;

                for (let i = 0; i < tupoksiRows.length; i++) {
                    tupoksiRows[i].querySelector('.row-num').innerText = i + 1;
                    if(skpRows[i]) skpRows[i].querySelector('.row-num').innerText = i + 1;

                    const inputsT = tupoksiRows[i].querySelectorAll('[name^="items["]');
                    inputsT.forEach(input => {
                        input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
                    });

                    const inputsS = skpRows[i].querySelectorAll('[name^="items["]');
                    inputsS.forEach(input => {
                        input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
                    });
                }
                rowCount = tupoksiRows.length;
                updateCounter();
            }

            function removeSyncedRow(idx) {
                const trT = document.getElementById(`row-tupoksi-${idx}`);
                const trS = document.getElementById(`row-skp-${idx}`);
                if(trT) trT.remove();
                if(trS) trS.remove();
                reindexForms();
            }

            function validateNumber(e) {
                if (['-', 'e', 'E'].includes(e.key)) e.preventDefault();
            }

            function syncActivity(idx, val) {
                const displayEl = document.getElementById(`display-activity-${idx}`);
                if(displayEl) {
                    displayEl.value = val || '';
                    autoResize(displayEl);
                }
            }

            function createFormRow(data = {}) {
                if(!tupoksiContainer || !skpContainer) return;
                const idx = rowCount++;

                // Ambil jabatan pegawai dari Alpine state
                const appState = document.querySelector('[x-data="setupMasterSKP()"]');
                const currentJabatan = appState ? Alpine.$data(appState).pegawaiJabatan : null;

                // --- TABLE 1: TUPOKSI ---
                const trT = document.createElement('tr');
                trT.id = `row-tupoksi-${idx}`;
                trT.className = "hover:bg-slate-50 transition-colors form-row-item";

                let bidangOptionsHTML = '<option value="">-- Pilih --</option>';
                if (currentJabatan && currentJabatan !== '......................') {
                    const jabatanArray = currentJabatan.split(',').map(j => j.trim());
                    jabatanArray.forEach(jab => {
                        const isExisting = data.bidang && data.bidang === jab;
                        const selected = (jabatanArray.length === 1 || isExisting) ? 'selected' : '';
                        bidangOptionsHTML += `<option value="${jab}" ${selected}>${jab}</option>`;
                    });
                } else {
                    availableBidang.forEach(bidangName => {
                        const isExisting = data.bidang && data.bidang === bidangName;
                        const selected = isExisting ? 'selected' : '';
                        bidangOptionsHTML += `<option value="${bidangName}" ${selected}>${bidangName}</option>`;
                    });
                }

                let penilaiHTML = '<option value="">-- Pilih --</option>';
                penilaiRoles.forEach(roleItem => {
                    const penilai = (typeof roleItem === 'object' && roleItem.name) ? roleItem.name : String(roleItem);
                    const selected = (data.penilai_role && data.penilai_role.trim().toLowerCase() === penilai
                        .toLowerCase()) ? 'selected' : '';
                    penilaiHTML += `<option value="${penilai}" ${selected}>${penilai}</option>`;
                });

                const periodeOptions = ["Bulanan", "Harian", "Kegiatan", "Mingguan", "Semester", "Tahunan", "Triwulan"];
                let periodeOptionsHTML = '<option value="">-- Pilih --</option>';
                periodeOptions.forEach(p => {
                    const selected = (data.periode && data.periode.toLowerCase() === p.toLowerCase()) ? 'selected' : '';
                    periodeOptionsHTML += `<option value="${p.toLowerCase()}" ${selected}>${p}</option>`;
                });

                trT.innerHTML = `
                    <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${tupoksiContainer.children.length + 1}</td>
                    <td>
                        <select name="items[${idx}][role_name]" required class="table-input font-semibold text-blue-800"
                            onchange="fetchTupoksiByBidang(this)">
                            ${bidangOptionsHTML}
                        </select>
                    </td>
                    <td>
                        <select name="items[${idx}][tupoksi_select]" class="table-input font-bold text-gray-800"
                            onchange="fillTupoksiData(this)">
                            <option value="">-- Pilih Tupoksi --</option>
                        </select>
                        <input type="hidden" name="items[${idx}][tupoksi]" value="${data.tupoksi || ''}">
                    </td>
                    <td><textarea name="items[${idx}][jenis_aktivitas]" required readonly class="table-input leading-snug bg-gray-50 cursor-not-allowed opacity-60"
                        oninput="autoResize(this); syncActivity(${idx}, this.value)" placeholder="Pilih tupoksi dahulu...">${data.jenis_aktivitas || ''}</textarea></td>
                    <td><select name="items[${idx}][penilai_role]" required class="table-input font-semibold text-emerald-700 bg-gray-50 cursor-not-allowed opacity-60 pointer-events-none" tabindex="-1">${penilaiHTML}</select></td>
                    <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="items[${idx}][kategori][]" value="Utama" ${data.utama ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60 pointer-events-none" onclick="return false;"></td>
                    <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="items[${idx}][kategori][]" value="Periodik" ${data.periodik ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60 pointer-events-none" onclick="return false;"></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Bukan Tupoksi" ${data.bukan_tupoksi ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60 pointer-events-none" onclick="return false;"></td>

                    <td>
                        <select name="items[${idx}][periode]" required class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60 pointer-events-none" tabindex="-1">
                            ${periodeOptionsHTML}
                        </select>
                    </td>

                    <td><input type="number" name="items[${idx}][frekuensi_per_tahun]" value="${data.frekuensi || ''}" min="0" step="1" required readonly class="table-input text-center required-number px-1 bg-gray-50 cursor-not-allowed opacity-60" onkeypress="preventMinus(event)" oninput="calcVol(this)" placeholder="..."></td>
                    <td><input type="number" name="items[${idx}][durasi_menit]" value="${data.durasi || ''}" min="0" step="1" required readonly class="table-input text-center required-number px-1 bg-gray-50 cursor-not-allowed opacity-60" onkeypress="preventMinus(event)" oninput="calcVol(this)" placeholder="..."></td>
                    <td><input type="number" name="items[${idx}][jumlah_sdm]" value="1" readonly tabindex="-1" class="table-input text-center px-1 bg-gray-100 cursor-not-allowed font-bold text-gray-500"></td>
                    <td class="bg-slate-50 text-right px-2 font-bold text-indigo-700 text-[9px] vol-display pt-3">${((data.frekuensi || 0) * (data.durasi || 0) * 1).toLocaleString('id-ID')}</td>

                    <td class="text-center bg-white border-none align-middle">
                        <button type="button" class="text-red-400 hover:text-red-600 transition" onclick="removeSyncedRow(${idx})">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </td>
                `;
                tupoksiContainer.appendChild(trT);

                // --- TABLE 2: SKP ---
                const trS = document.createElement('tr');
                trS.id = `row-skp-${idx}`;
                trS.className = "hover:bg-amber-50/10 transition-colors";
                trS.innerHTML = `
                    <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${skpContainer.children.length + 1}</td>
                    <td class="p-0">
                        <textarea id="display-activity-${idx}" readonly class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)">${data.jenis_aktivitas || ''}</textarea>
                    </td>
                    <td class="bg-amber-50/20"><input type="number" step="0.01" name="items[${idx}][ak]" class="table-input font-bold text-blue-700" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" name="items[${idx}][kuantitas]" class="table-input text-center" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" min="0" max="100" name="items[${idx}][kualitas]" class="table-input text-center" placeholder="0" onkeypress="preventMinus(event)"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" min="1" max="12" name="items[${idx}][waktu]" class="table-input text-center" placeholder="1" required oninput="if(this.value>12)this.value=12; if(this.value!='' && this.value<1)this.value=1;"></td>
                    <td class="bg-amber-50/20"><input type="number" step="1" name="items[${idx}][biaya]" class="table-input text-right font-bold text-emerald-700" placeholder="0" min="0" onkeypress="preventMinus(event)"></td>
                `;
                skpContainer.appendChild(trS);

                trT.querySelectorAll('textarea').forEach(el => autoResize(el));
                trS.querySelectorAll('textarea').forEach(el => autoResize(el));
                const roleSelect = trT.querySelector('select[name*="role_name"]');
                if(roleSelect.value !== "") {
                    fetchTupoksiByBidang(roleSelect, data.tupoksi);
                }

                updateCounter();
            }

            window.fetchTupoksiByBidang = function(el, selectedTupoksi = null) {
                const tr = el.closest('tr');
                if(!tr) return;
                const tupoksiSelect = tr.querySelector('select[name*="tupoksi_select"]');
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
                            const isSelected = (selectedTupoksi && selectedTupoksi === item.tupoksi) ? 'selected' : '';
                            const option = document.createElement('option');
                            option.value = JSON.stringify(item);
                            option.text = item.tupoksi;
                            if(isSelected) option.selected = true;
                            tupoksiSelect.appendChild(option);
                        });
                    });
            }

            window.fillTupoksiData = function(el) {
                const tr = el.closest('tr');
                if(!tr) return;
                const idxStr = tr.id.split('-').pop();
                const idx = parseInt(idxStr);

                const hiddenTupoksi = tr.querySelector('input[name*="[tupoksi]"]');
                const aktivitasTextarea = tr.querySelector('textarea[name*="jenis_aktivitas"]');
                const periodeSelect = tr.querySelector('select[name*="periode"]');
                const frekuensiInput = tr.querySelector('input[name*="frekuensi_per_tahun"]');
                const durasiInput = tr.querySelector('input[name*="durasi_menit"]');
                const penilaiSelect = tr.querySelector('select[name*="penilai_role"]');
                const checkboxes = tr.querySelectorAll('.category-checkbox');

                const allLockedInputs = [penilaiSelect, periodeSelect, frekuensiInput, durasiInput];

                if(el.value === "") {
                    hiddenTupoksi.value = "";
                    aktivitasTextarea.readOnly = true;
                    aktivitasTextarea.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60');

                    allLockedInputs.forEach(s => {
                        s.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60', 'pointer-events-none');
                        s.tabIndex = -1;
                    });

                    checkboxes.forEach(cb => {
                        cb.classList.add('opacity-60', 'pointer-events-none');
                        cb.onclick = () => false;
                    });

                    syncActivity(idx, "");
                    return;
                }

                // Enable fields
                aktivitasTextarea.readOnly = false;
                aktivitasTextarea.classList.remove('bg-gray-50', 'cursor-not-allowed', 'opacity-60');

                allLockedInputs.forEach(s => {
                    s.classList.remove('bg-gray-50', 'cursor-not-allowed', 'opacity-60', 'pointer-events-none');
                    s.tabIndex = 0;
                });

                checkboxes.forEach(cb => {
                    cb.classList.remove('opacity-60', 'pointer-events-none');
                    cb.onclick = null;
                });

                penilaiSelect.classList.add('bg-emerald-50/30');

                const data = JSON.parse(el.value);

                // Cek duplikasi tupoksi
                const selectedTupoksi = data.tupoksi;
                const allTupoksiInputs = document.querySelectorAll('input[name*="[tupoksi]"]');
                let isDuplicate = false;
                allTupoksiInputs.forEach(input => {
                    if (input !== hiddenTupoksi && input.value === selectedTupoksi) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    if (!confirm("Tupoksi sudah ada, apakah anda yakin untuk menambahkan lagi?")) {
                        el.value = "";
                        hiddenTupoksi.value = "";
                        allLockedInputs.forEach(input => {
                            input.disabled = true;
                            input.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                        });
                        checkboxes.forEach(cb => {
                            cb.disabled = true;
                            cb.classList.add('opacity-60');
                        });
                        syncActivity(idx, "");
                        return;
                    }
                }

                hiddenTupoksi.value = data.tupoksi;
                aktivitasTextarea.value = data.jenis_aktivitas;
                autoResize(aktivitasTextarea);
                syncActivity(idx, data.jenis_aktivitas);

                if(data.periode) {
                    periodeSelect.value = data.periode.toLowerCase();
                }

                frekuensiInput.value = data.frekuensi_per_tahun;
                durasiInput.value = data.durasi_menit;

                checkboxes.forEach(cb => cb.checked = false);
                if(data.kategori && Array.isArray(data.kategori)) {
                    data.kategori.forEach(kat => {
                        const target = tr.querySelector(`.category-checkbox[value="${kat}"]`);
                        if(target) target.checked = true;
                    });
                }

                calcVol(frekuensiInput);
            }

            window.calcVol = function(el) {
                const tr = el.closest('tr');
                if(!tr) return;
                const f = parseInt(tr.querySelector('[name*="frekuensi_per_tahun"]').value) || 0;
                const d = parseInt(tr.querySelector('[name*="durasi_menit"]').value) || 0;
                const s = 1;
                tr.querySelector('.vol-display').innerText = (f * d * s).toLocaleString('id-ID');
            }

            document.getElementById('btn-add-row').addEventListener('click', () => createFormRow());

            // Hapus fitur scroll number
            document.addEventListener('wheel', function(event) {
                if (document.activeElement.type === 'number') {
                    document.activeElement.blur();
                }
            }, {
                passive: false
            });

            createFormRow();
        </script>
    @endsection
</x-app-layout>
