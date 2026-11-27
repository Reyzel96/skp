<x-app-layout>
    @section('page-title', 'Edit SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Edit Rencana Hasil Kerja (SKP Kualitatif)</h2>

                    @if ($errors->any())
                        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>- {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('superadmin.skp-dosen-kualitatif.update', $skpKualitatif->id) }}" method="POST" id="skpForm"
    @submit.prevent="
        let form = $el;
        if (!form.checkValidity()) {
            let invalidElements = form.querySelectorAll(':invalid');
            if (invalidElements.length > 0) {
                let firstInvalid = invalidElements[0];
                let isLampiran = firstInvalid.closest('#tab-lampiran');
                if (isLampiran) {
                    activeTab = 'lampiran';
                } else {
                    activeTab = 'skp';
                }
                setTimeout(() => firstInvalid.reportValidity(), 100);
            }
        } else {
            confirmModal = true;
        }
    " x-data='{
                        activeTab: "skp",
                        confirmModal: false,
                        setupModal: false,
                        pegawaiId: "{{ old("user_id", $skpKualitatif->skp_header->user_id ?? $skpKualitatif->skpHeader->user_id ?? "") }}",
                        periodeId: "{{ old("periode_skp_id", $skpKualitatif->skp_header->periode_skp_id ?? $skpKualitatif->skpHeader->periode_skp_id ?? "") }}",
                        atasanId: "{{ old("atasan_pejabat_penilai_id", $skpKualitatif->skp_header->atasan_pejabat_penilai_id ?? $skpKualitatif->skpHeader->atasan_pejabat_penilai_id ?? "") }}",
                        penilaiId: "{{ old("pejabat_penilai_id", $skpKualitatif->skp_header->pejabat_penilai_id ?? $skpKualitatif->skpHeader->pejabat_penilai_id ?? "") }}",

                        pegawaiName: "{{ $skpKualitatif->skpHeader->user?->name ?? "-" }}",
                        pegawaiNIP: "{{ $skpKualitatif->skpHeader->user?->profile?->nip ?? "-" }}",
                        pegawaiPangkat: "{{ $skpKualitatif->skpHeader->user?->profile?->pangkat_golongan ?? "-" }}",
                        pegawaiJabatan: "{{ $skpKualitatif->skpHeader->user?->roles?->pluck("name")->implode(", ") ?: "Dosen" }}",

                        penilaiName: "{{ $skpKualitatif->skpHeader->pejabatPenilai?->name ?? "-" }}",
                        penilaiNIP: "{{ $skpKualitatif->skpHeader->pejabatPenilai?->profile?->nip ?? "-" }}",
                        penilaiPangkat: "{{ $skpKualitatif->skpHeader->pejabatPenilai?->profile?->pangkat_golongan ?? "-" }}",
                        penilaiJabatan: "{{ $skpKualitatif->skpHeader->pejabatPenilai?->roles?->pluck("name")->implode(", ") ?: "Dosen" }}",

                        atasanName: "{{ $skpKualitatif->skpHeader->atasanPejabatPenilai?->name ?? "-" }}",
                        atasanNIP: "{{ $skpKualitatif->skpHeader->atasanPejabatPenilai?->profile?->nip ?? "-" }}",
                        atasanPangkat: "{{ $skpKualitatif->skpHeader->atasanPejabatPenilai?->profile?->pangkat_golongan ?? "-" }}",
                        atasanJabatan: "{{ $skpKualitatif->skpHeader->atasanPejabatPenilai?->roles?->pluck("name")->implode(", ") ?: "Dosen" }}",

                        tambahanRows: [
                            @foreach($tambahans as $index => $tambahan)
                                { id: {{ $index + 1 }}, val: `{!! addslashes($tambahan->rencana_hasil_kerja) !!}` },
                            @endforeach
                            @if($tambahans->isEmpty())
                                { id: 1, val: `` }
                            @endif
                        ],
                        addTambahanRow() {
                            this.tambahanRows.push({ id: Date.now(), val: `` });
                        },
                        removeTambahanRow(id) {
                            if (this.tambahanRows.length > 1) {
                                this.tambahanRows = this.tambahanRows.filter(row => row.id !== id);
                            }
                        },

                        dukunganRows: [
                            @php $dukungans = $lampirans->where("jenis", "dukungan_sumber_daya")->values(); @endphp
                            @foreach($dukungans as $index => $item)
                                { id: {{ $index + 1 }}, val: `{!! addslashes($item->deskripsi) !!}` },
                            @endforeach
                            @if($dukungans->isEmpty())
                                { id: 1, val: `` }
                            @endif
                        ],
                        addDukunganRow() { this.dukunganRows.push({ id: Date.now(), val: `` }); },
                        removeDukunganRow(id) { if (this.dukunganRows.length > 1) this.dukunganRows = this.dukunganRows.filter(r => r.id !== id); },

                        skemaRows: [
                            @php $skemas = $lampirans->where("jenis", "skema_pertanggungjawaban")->values(); @endphp
                            @foreach($skemas as $index => $item)
                                { id: {{ $index + 1 }}, val: `{!! addslashes($item->deskripsi) !!}` },
                            @endforeach
                            @if($skemas->isEmpty())
                                { id: 1, val: `` }
                            @endif
                        ],
                        addSkemaRow() { this.skemaRows.push({ id: Date.now(), val: `` }); },
                        removeSkemaRow(id) { if (this.skemaRows.length > 1) this.skemaRows = this.skemaRows.filter(r => r.id !== id); },

                        konsekuensiRows: [
                            @php $konsekuensis = $lampirans->where("jenis", "konsekuensi")->values(); @endphp
                            @foreach($konsekuensis as $index => $item)
                                { id: {{ $index + 1 }}, val: `{!! addslashes($item->deskripsi) !!}` },
                            @endforeach
                            @if($konsekuensis->isEmpty())
                                { id: 1, val: `` }
                            @endif
                        ],
                        addKonsekuensiRow() { this.konsekuensiRows.push({ id: Date.now(), val: `` }); },
                        removeKonsekuensiRow(id) { if (this.konsekuensiRows.length > 1) this.konsekuensiRows = this.konsekuensiRows.filter(r => r.id !== id); },

                        get isValidSetup() { return this.pegawaiId !== "" && this.periodeId !== "" && this.penilaiId !== "" && this.atasanId !== ""; }
                    }'>
                        @method('PUT')
                        @csrf
                        
                        {{-- MODAL PERSIAPAN AWAL --}}
                        <div x-show="setupModal" style="z-index: 99999;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm" x-cloak @keydown.window.escape="setupModal = false">
                            <div class="flex min-h-screen w-full items-center justify-center p-4">
                                <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-4xl w-full transform transition-all relative max-h-[95vh] overflow-y-auto">
                                    <div class="text-center border-b border-gray-200 pb-4 mb-6">
                                        <h2 class="text-2xl sm:text-3xl font-black text-gray-800">Target SKP Dosen Kualitatif</h2>
                                        <p class="text-gray-500 text-sm sm:text-base mt-2">Pilih periode dan tentukan Pejabat Penilai</p>
                                    </div>

                                    <div class="space-y-6">
                                        {{-- 1. PILIH PERIODE --}}
                                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                            <label class="block text-sm font-bold text-gray-700 uppercase mb-2"><i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Periode SKP *</label>
                                            <select name="periode_skp_id" id="periode_skp_id" x-model="periodeId" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold bg-white text-sm shadow-sm transition-all">
                                                <option value="">-- Pilih Periode Aktif --</option>
                                                @foreach ($periode as $p)
                                                    @php $isNonSelectable = in_array(strtolower($p->status), ['draft', 'selesai']); @endphp
                                                    <option value="{{ $p->id }}" {{ $isNonSelectable ? 'disabled' : '' }}
                                                        class="{{ $isNonSelectable ? 'text-gray-400 bg-gray-100 italic' : '' }}">
                                                        {{ $p->nama_periode }}
                                                        ({{ strtoupper($p->status) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- 2. TABEL PEGAWAI & PENILAI --}}
                                        <div class="border border-gray-300 rounded-xl overflow-hidden shadow-sm bg-white">
                                            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-300 text-sm">
                                                
                                                {{-- SISI KIRI PEGAWAI YANG DINILAI --}}
                                                <div class="flex flex-col h-full bg-white">
                                                    <div class="flex items-center gap-3 p-3 border-b border-gray-300 font-bold bg-blue-50 text-blue-800 uppercase tracking-wide">
                                                        <div class="w-8 h-8 rounded-full bg-blue-200 flex items-center justify-center text-blue-900 shrink-0">I</div>
                                                        <span>DOSEN YANG DINILAI *</span>
                                                    </div>
                                                    <div class="p-3 border-b border-gray-200 bg-blue-50/30">
                                                        <select name="user_id" x-model="pegawaiId"
                                                            @change="
                                                            let opt = $el.options[$el.selectedIndex];
                                                            pegawaiName = opt.dataset.nama || '......................';
                                                            pegawaiNIP = opt.dataset.nip || '......................';
                                                            pegawaiPangkat = opt.dataset.pangkat || '......................';
                                                            pegawaiJabatan = opt.dataset.jabatan || '......................';
                                                            " required :disabled="!periodeId"
                                                            class="w-full px-3 py-2 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold text-blue-900 bg-white shadow-sm text-xs transition-all disabled:bg-gray-100 disabled:opacity-50">
                                                            <option value="">-- Pilih Dosen --</option>
                                                            @foreach ($jsonPegawai as $dosen)
                                                                <option value="{{ $dosen['id'] }}"
                                                                    data-nama="{{ $dosen['nama'] }}"
                                                                    data-nip="{{ $dosen['nip'] }}"
                                                                    data-pangkat="{{ $dosen['pangkat'] }}"
                                                                    data-jabatan="{{ $dosen['jabatan'] }}">
                                                                    [{{ $dosen['jabatan'] }}] {{ $dosen['nama'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex flex-col flex-1 divide-y divide-gray-100">
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Nama</span>
                                                            <span class="font-bold flex-1 break-words" x-text="pegawaiName"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">NIP/NIDN</span>
                                                            <span class="font-bold flex-1 break-words" x-text="pegawaiNIP"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Pangkat/Gol</span>
                                                            <span class="font-bold flex-1 break-words" x-text="pegawaiPangkat"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Jabatan</span>
                                                            <span class="font-bold flex-1 break-words" x-text="pegawaiJabatan"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- SISI KANAN PENILAI --}}
                                                <div class="flex flex-col h-full bg-white">
                                                    <div class="flex items-center gap-3 p-3 border-b border-gray-300 font-bold bg-emerald-50 text-emerald-800 uppercase tracking-wide">
                                                        <div class="w-8 h-8 rounded-full bg-emerald-200 flex items-center justify-center text-emerald-900 shrink-0">II</div>
                                                        <span>PEJABAT PENILAI *</span>
                                                    </div>
                                                    <div class="p-3 border-b border-gray-200 bg-emerald-50/30">
                                                        <select name="pejabat_penilai_id" x-model="penilaiId"
                                                            @change="
                                                            let opt = $el.options[$el.selectedIndex];
                                                            penilaiName = opt.dataset.nama || '......................';
                                                            penilaiNIP = opt.dataset.nip || '......................';
                                                            penilaiPangkat = opt.dataset.pangkat || '......................';
                                                            penilaiJabatan = opt.dataset.jabatan || '......................';
                                                            " required :disabled="!periodeId"
                                                            class="w-full px-3 py-2 border border-emerald-200 rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold text-emerald-900 bg-white shadow-sm text-xs transition-all disabled:bg-gray-100 disabled:opacity-50">
                                                            <option value="">-- Pilih Penilai --</option>
                                                            @foreach ($jsonPenilai as $cp)
                                                                <option value="{{ $cp['id'] }}"
                                                                    x-show="pegawaiId != '{{ $cp['id'] }}' || '{{ strtolower($cp['jabatan']) }}'.includes('dekan')"
                                                                    data-nama="{{ $cp['nama'] }}"
                                                                    data-nip="{{ $cp['nip'] }}"
                                                                    data-pangkat="{{ $cp['pangkat'] }}"
                                                                    data-jabatan="{{ $cp['jabatan'] }}">
                                                                    [{{ $cp['jabatan'] }}] {{ $cp['nama'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex flex-col flex-1 divide-y divide-gray-100">
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Nama</span>
                                                            <span class="font-bold flex-1 break-words" x-text="penilaiName"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">NIP/NIDN</span>
                                                            <span class="font-bold flex-1 break-words" x-text="penilaiNIP"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Pangkat/Gol</span>
                                                            <span class="font-bold flex-1 break-words" x-text="penilaiPangkat"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Jabatan</span>
                                                            <span class="font-bold flex-1 break-words" x-text="penilaiJabatan"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                                <div class="border-t border-gray-300 bg-white">
                                                    <div class="flex items-center gap-3 p-3 border-b border-gray-300 font-bold bg-indigo-50 text-indigo-800 uppercase tracking-wide">
                                                        <div class="w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-900 shrink-0">III</div>
                                                        <span>ATASAN PEJABAT PENILAI *</span>
                                                    </div>
                                                    <div class="p-3 border-b border-gray-200 bg-indigo-50/30">
                                                        <select name="atasan_pejabat_penilai_id" x-model="atasanId"
                                                            @change="
                                                            let opt = $el.options[$el.selectedIndex];
                                                            atasanName = opt.dataset.nama || '......................';
                                                            atasanNIP = opt.dataset.nip || '......................';
                                                            atasanPangkat = opt.dataset.pangkat || '......................';
                                                            atasanJabatan = opt.dataset.jabatan || '......................';
                                                            " required :disabled="!periodeId"
                                                            class="w-full px-3 py-2 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 font-bold text-indigo-900 bg-white shadow-sm text-xs transition-all disabled:bg-gray-100 disabled:opacity-50">
                                                            <option value="">-- Pilih Atasan Penilai --</option>
                                                            @foreach ($jsonPenilai as $cp)
                                                                <option value="{{ $cp['id'] }}"
                                                                    data-nama="{{ $cp['nama'] }}"
                                                                    data-nip="{{ $cp['nip'] }}"
                                                                    data-pangkat="{{ $cp['pangkat'] }}"
                                                                    data-jabatan="{{ $cp['jabatan'] }}">
                                                                    [{{ $cp['jabatan'] }}] {{ $cp['nama'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex flex-col flex-1 divide-y divide-gray-100 border-t border-gray-200">
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Nama</span>
                                                            <span class="font-bold flex-1 break-words" x-text="atasanName"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">NIP/NIDN</span>
                                                            <span class="font-bold flex-1 break-words" x-text="atasanNIP"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Pangkat/Gol</span>
                                                            <span class="font-bold flex-1 break-words" x-text="atasanPangkat"></span>
                                                        </div>
                                                        <div class="flex p-3 text-xs hover:bg-gray-50 transition-colors">
                                                            <span class="w-24 shrink-0 text-gray-500">Jabatan</span>
                                                            <span class="font-bold flex-1 break-words" x-text="atasanJabatan"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>

                                        {{-- 3. TOMBOL AKSI --}}
                                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 mt-6">
                                            <button type="button" @click="setupModal = false" class="w-full sm:w-1/3 py-3 px-4 rounded-xl font-bold text-center uppercase transition-colors border-2 border-gray-200 text-gray-600 hover:bg-gray-100 hover:text-gray-800">Batal</button>
                                            <button type="button" :disabled="!isValidSetup"
                                                @click="setupModal = false;"
                                                class="w-full sm:w-2/3 py-3 px-4 rounded-xl font-black uppercase transition-all shadow-md flex items-center justify-center gap-2"
                                                :class="isValidSetup ? 'bg-blue-600 hover:bg-blue-700 text-white transform hover:scale-[1.02]' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                                                Lanjutkan Susun Kegiatan <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- HALAMAN UTAMA --}}
                        <div x-show="!setupModal" x-cloak>
                            <div class="flex justify-between items-center mb-4">
                                <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}"
                                    class="text-sm font-bold text-gray-500 hover:text-emerald-600">&larr; Batal & Kembali</a>
                                
                                <button type="button" @click="setupModal = true" class="text-white bg-blue-600 px-4 py-2 rounded-lg text-xs shadow hover:bg-blue-700 transition-all font-bold uppercase flex items-center gap-2">
                                    <i class="fas fa-user-edit"></i> GANTI PEGAWAI / PENILAI
                                </button>
                            </div>

                            <style>
                                [x-cloak] { display: none !important; }
                                .table-kualitatif {
                                    width: 100%;
                                    border-collapse: collapse;
                                    border: 2px solid #000;
                                }
                                .table-kualitatif th, .table-kualitatif td {
                                    border: 1px solid #000;
                                    padding: 6px 8px;
                                    vertical-align: top;
                                    color: #000;
                                    font-size: 13px;
                                }
                                .input-no-border {
                                    width: 100%;
                                    border: none !important;
                                    outline: none !important;
                                    resize: none;
                                    background: transparent;
                                    font-family: inherit;
                                    font-size: 13px;
                                }
                                .input-no-border:focus {
                                    box-shadow: none !important;
                                    background-color: #f8fafc;
                                }
                                .bg-light-gray {
                                    background-color: #f3f4f6;
                                }
                            </style>

                            <div class="flex space-x-1 mb-4 border-b border-gray-200">
                                <button type="button" @click="activeTab = 'skp'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'skp', 'text-gray-500 hover:text-gray-700': activeTab !== 'skp'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                                    SKP Pegawai
                                </button>
                                <button type="button" @click="activeTab = 'lampiran'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'lampiran', 'text-gray-500 hover:text-gray-700': activeTab !== 'lampiran'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                                    Lampiran SKP
                                </button>
                            </div>

                            <div x-show="activeTab === 'skp'" id="tab-skp">
                                <div class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                                    <!-- IDENTITAS -->
                                    <table class="table-kualitatif mb-6" x-data="{ showRow2: true }">
                                    <thead>
                                        <tr class="bg-light-gray">
                                            <th class="w-12 text-center">NO</th>
                                            <th class="text-left w-5/12">PEGAWAI YANG DINILAI</th>
                                            <th class="w-12 text-center">NO</th>
                                            <th class="text-left w-5/12">PEJABAT PENILAI KINERJA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center font-bold">1</td>
                                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span x-text="pegawaiName"></span></td>
                                            <td class="text-center font-bold">1</td>
                                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span x-text="penilaiName"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-center font-bold">2</td>
                                            <td><span class="inline-block w-48 font-bold">NIP</span> <span x-text="pegawaiNIP"></span></td>
                                            <td class="text-center font-bold">2</td>
                                            <td><span class="inline-block w-48 font-bold">NIP</span> <span x-text="penilaiNIP"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-center font-bold">3</td>
                                            <td><span class="inline-block w-48 font-bold">PANGKAT/GOL. RUANG</span> <span x-text="pegawaiPangkat"></span></td>
                                            <td class="text-center font-bold">3</td>
                                            <td><span class="inline-block w-48 font-bold">PANGKAT/ GOL. RUANG</span> <span x-text="penilaiPangkat"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-center font-bold">4</td>
                                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span x-text="pegawaiJabatan"></span></td>
                                            <td class="text-center font-bold">4</td>
                                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span x-text="penilaiJabatan"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-center font-bold">5</td>
                                            <td><span class="inline-block w-48 font-bold">UNIT KERJA</span> Universitas Muhammadiyah Surabaya</td>
                                            <td class="text-center font-bold">5</td>
                                            <td><span class="inline-block w-48 font-bold">UNIT KERJA</span> Universitas Muhammadiyah Surabaya</td>
                                        </tr>
                                    </tbody>

                                    <!-- HASIL KERJA -->
                                    <tbody>
                                        <tr class="bg-light-gray">
                                            <td colspan="4" class="font-bold">HASIL KERJA</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="font-bold">A. UTAMA</td>
                                        </tr>
                                        </tbody>
                                        
                                        <!-- DYNAMIC UTAMA SECTION FROM ACUAN -->
                                        @foreach($utamaAcuan as $idx => $parent)
                                        @php
                                            // Check if this parent has any saved data in skpUtamas
                                            $savedParent = $skpUtamas->where('rencana_hasil_kerja', $parent->sasaran_kinerja)->first();
                                            $hasSavedData = $savedParent ? true : false;
                                            
                                            // Build default indikator if no saved data
                                            $defaultIndikator = '';
                                            if($parent->kategori != 'jabatan') {
                                                $counter = 1;
                                                foreach($parent->children as $child) {
                                                    $defaultIndikator .= $counter . '. ' . trim($child->indikator_kinerja) . '. ' . trim($child->target_ekspektasi) . "\n";
                                                    $counter++;
                                                }
                                            }
                                        @endphp
                                        <tbody x-data="{ showRow: {{ $hasSavedData || $parent->kategori != 'jabatan' ? 'true' : 'false' }} }" @restore-jabatan.window="showRow = true" class="border-b border-gray-200">
                                            <tr x-show="showRow">
                                                <td class="text-center font-bold w-12 align-top">
                                                    {{ $idx + 1 }}
                                                </td>
                                                <td colspan="3" class="align-top italic text-gray-800 relative group">
                                                    <input type="hidden" name="utama_rhk_pimpinan[]" :disabled="!showRow" value="">
                                                    <input type="hidden" name="utama_rhk[]" :disabled="!showRow" value="{{ $parent->sasaran_kinerja }}">
                                                    <input type="hidden" name="utama_acuan_parent_id[]" :disabled="!showRow" value="{{ $parent->id }}">
                                                    {{ $parent->sasaran_kinerja }}
                                                    
                                                    @if($parent->kategori == 'jabatan')
                                                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded-md">
                                                        <button type="button" @click="showRow = false" class="text-xs bg-red-600 text-white font-bold px-3 py-1 rounded shadow hover:bg-red-700 transition flex items-center mb-1">
                                                            <i class="fas fa-trash-alt mr-2"></i> Hapus Baris Jabatan
                                                        </button>
                                                        <span class="text-[10px] text-red-600 font-medium italic">* Silahkan Hapus Kolom berikut jika tidak menduduki jabatan di PT</span>
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr x-show="showRow">
                                                <td class="text-center w-12 border-t-0"></td>
                                                <td colspan="3">
                                                    <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                                                    <textarea name="utama_indikator[]" class="input-no-border font-medium text-gray-700" 
                                                        rows="{{ $parent->kategori == 'jabatan' ? 3 : max(4, $parent->children->count() * 2) }}" 
                                                        placeholder="1. Tuliskan Ukuran keberhasilan/ Indikator Kinerja Individu" 
                                                        :disabled="!showRow"
                                                        @if($parent->kategori == 'jabatan')
                                                            :value="(pegawaiJabatan && pegawaiJabatan !== '......................' && pegawaiJabatan.toLowerCase() !== 'dosen') ? @json(\App\Support\SkpKualitatifJabatan::KALIMAT_RESMI) + @json(' | Jabatan ') + pegawaiJabatan : @json(\App\Support\SkpKualitatifJabatan::KALIMAT_RESMI)"
                                                        @endif
                                                        required>{{ $savedParent ? $savedParent->indikator_target : $defaultIndikator }}</textarea>
                                                </td>
                                            </tr>
                                            <tr x-show="!showRow" x-cloak>
                                                <td colspan="4" class="text-center p-4 bg-gray-50 border-dashed border border-gray-300">
                                                    <p class="text-gray-500 text-sm mb-2">Baris <b>{{ $parent->sasaran_kinerja }}</b> telah disembunyikan.</p>
                                                    <button type="button" @click="showRow = true" class="text-xs bg-emerald-100 text-emerald-700 font-bold px-3 py-1 rounded shadow hover:bg-emerald-200 transition">
                                                        <i class="fas fa-undo mr-1"></i> Kembalikan Baris Ini
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        @endforeach
                                        
                                        <tbody>
                                        <tr>
                                            <td colspan="4" class="font-bold bg-gray-50 p-0">
                                                <div class="flex items-center justify-between p-2">
                                                    <span>B. TAMBAHAN</span>
                                                    <button type="button" @click="addTambahanRow()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded shadow-sm hover:bg-blue-200 transition-colors uppercase tracking-wide">
                                                        <i class="fas fa-plus mr-1"></i> Tambah Baris
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <template x-for="(row, index) in tambahanRows" :key="row.id">
                                            <tr class="group">
                                                <td class="text-center font-bold w-12 align-top pt-4" x-text="index + 1"></td>
                                                <td colspan="3" class="align-top relative p-0">
                                                    <div class="p-3">
                                                        <textarea name="tambahan[]" x-model="row.val" class="w-full border-b border-dashed border-gray-300 focus:border-blue-500 bg-transparent resize-none outline-none font-semibold text-gray-800 pb-2 mb-3" rows="2" placeholder="Tuliskan Rencana Hasil Kerja Tambahan..."></textarea>
                                                    </div>
                                                    
                                                    <button x-show="tambahanRows.length > 1" type="button" @click="removeTambahanRow(row.id)" class="absolute top-4 right-4 px-2 py-1 bg-red-100 text-red-600 rounded shadow-sm text-xs font-bold hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Baris Ini">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>

                                    <!-- PERILAKU KERJA -->
                                    <tbody>
                                        <tr class="bg-light-gray">
                                            <td colspan="4" class="font-bold">PERILAKU KERJA*</td>
                                        </tr>
                                        @foreach($perilakuAcuan as $idx => $parent)
                                        <tr>
                                            <td class="text-center font-bold w-12 align-top">{{ $idx + 1 }}</td>
                                            <td colspan="2" class="align-top">
                                                <div class="font-bold mb-1">{{ $parent->sasaran_kinerja }}</div>
                                                <ul class="list-none pl-0 m-0 text-gray-700">
                                                    @foreach($parent->children as $child)
                                                    <li>- {{ trim($child->indikator_kinerja) }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="align-top">
                                                <div class="font-bold mb-1">Ekspektasi Khusus Pimpinan:</div>
                                                <textarea name="ekspektasi[{{ $parent->sasaran_kinerja }}]" class="input-no-border font-medium text-gray-700" rows="{{ max(4, $parent->children->count() * 2) }}" 
                                                    placeholder="1. Tuliskan Ekspektasi..."
                                                    required>{{ $perilakus->where("aspek", $parent->sasaran_kinerja)->first()->ekspektasi_pimpinan ?? "" }}</textarea>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>

                            <!-- BAGIAN LAMPIRAN SKP -->
                            <div x-show="activeTab === 'lampiran'" id="tab-lampiran" x-cloak>
                                <div class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                                    <table class="table-kualitatif w-full">
                                    <tbody>
                                        <tr class="bg-light-gray">
                                            <td colspan="4" class="font-bold text-center">LAMPIRAN SASARAN KINERJA PEGAWAI</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="font-bold bg-gray-50 p-2">
                                                <div class="flex items-center justify-between">
                                                    <span>DUKUNGAN SUMBER DAYA (pegawai, sarpras, anggaran, metode)</span>
                                                    <button type="button" @click="addDukunganRow()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded shadow-sm hover:bg-blue-200 transition-colors uppercase tracking-wide">
                                                        <i class="fas fa-plus mr-1"></i> Tambah Baris
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <template x-for="(row, index) in dukunganRows" :key="row.id">
                                            <tr class="group">
                                                <td class="text-center font-bold w-12 align-top pt-4" x-text="index + 1"></td>
                                                <td colspan="3" class="align-top relative p-0">
                                                    <div class="p-3">
                                                        <textarea name="dukungan[]" :required="index === 0" x-model="row.val" class="w-full border-b border-dashed border-gray-300 focus:border-blue-500 bg-transparent resize-none outline-none font-semibold text-gray-800 pb-2 mb-3" rows="2" placeholder="Tuliskan Dukungan Sumber Daya..."></textarea>
                                                    </div>
                                                    <button x-show="dukunganRows.length > 1" type="button" @click="removeDukunganRow(row.id)" class="absolute top-4 right-4 px-2 py-1 bg-red-100 text-red-600 rounded shadow-sm text-xs font-bold hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Baris Ini">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>

                                        <tr>
                                            <td colspan="4" class="font-bold bg-gray-50 p-2">
                                                <div class="flex items-center justify-between">
                                                    <span>SKEMA PERTANGGUNGJAWABAN</span>
                                                    <button type="button" @click="addSkemaRow()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded shadow-sm hover:bg-blue-200 transition-colors uppercase tracking-wide">
                                                        <i class="fas fa-plus mr-1"></i> Tambah Baris
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <template x-for="(row, index) in skemaRows" :key="row.id">
                                            <tr class="group">
                                                <td class="text-center font-bold w-12 align-top pt-4" x-text="index + 1"></td>
                                                <td colspan="3" class="align-top relative p-0">
                                                    <div class="p-3">
                                                        <textarea name="skema[]" :required="index === 0" x-model="row.val" class="w-full border-b border-dashed border-gray-300 focus:border-blue-500 bg-transparent resize-none outline-none font-semibold text-gray-800 pb-2 mb-3" rows="2" placeholder="Tuliskan Skema Pertanggungjawaban..."></textarea>
                                                    </div>
                                                    <button x-show="skemaRows.length > 1" type="button" @click="removeSkemaRow(row.id)" class="absolute top-4 right-4 px-2 py-1 bg-red-100 text-red-600 rounded shadow-sm text-xs font-bold hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Baris Ini">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>

                                        <tr>
                                            <td colspan="4" class="font-bold bg-gray-50 p-2">
                                                <div class="flex items-center justify-between">
                                                    <span>KONSEKUENSI</span>
                                                    <button type="button" @click="addKonsekuensiRow()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded shadow-sm hover:bg-blue-200 transition-colors uppercase tracking-wide">
                                                        <i class="fas fa-plus mr-1"></i> Tambah Baris
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <template x-for="(row, index) in konsekuensiRows" :key="row.id">
                                            <tr class="group">
                                                <td class="text-center font-bold w-12 align-top pt-4" x-text="index + 1"></td>
                                                <td colspan="3" class="align-top relative p-0">
                                                    <div class="p-3">
                                                        <textarea name="konsekuensi[]" :required="index === 0" x-model="row.val" class="w-full border-b border-dashed border-gray-300 focus:border-blue-500 bg-transparent resize-none outline-none font-semibold text-gray-800 pb-2 mb-3" rows="2" placeholder="Tuliskan Konsekuensi..."></textarea>
                                                    </div>
                                                    <button x-show="konsekuensiRows.length > 1" type="button" @click="removeKonsekuensiRow(row.id)" class="absolute top-4 right-4 px-2 py-1 bg-red-100 text-red-600 rounded shadow-sm text-xs font-bold hover:bg-red-200 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Baris Ini">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 mt-6">
                                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transition-colors flex items-center">
                                    <i class="fas fa-save mr-2"></i> Simpan Input SKP
                                </button>
                                <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}" class="px-6 py-2.5 bg-gray-200 text-gray-800 font-bold rounded-lg shadow-md hover:bg-gray-300 transition-colors flex items-center">
                                    <i class="fas fa-times mr-2"></i> Batal
                                </a>
                            </div>
                        </div>
                    
                        <!-- CONFIRM MODAL -->
                        <div x-show="confirmModal" style="z-index: 99999;" class="fixed inset-0 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center" x-cloak @keydown.window.escape="confirmModal = false">
                            <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-md w-full transform transition-all relative" @click.away="confirmModal = false">
                                <div class="text-center mb-6">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                                        <i class="fas fa-question text-blue-600 text-xl"></i>
                                    </div>
                                    <h3 class="text-lg leading-6 font-bold text-gray-900">Konfirmasi Simpan Data</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">Apakah data yang akan diinput sudah benar?</p>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button type="button" @click="confirmModal = false" class="w-full sm:w-1/2 py-2 px-4 rounded-lg font-bold text-center uppercase transition-colors border border-gray-300 text-gray-700 hover:bg-gray-50">
                                        Batal
                                    </button>
                                    <button type="button" @click="$el.closest('form').submit()" class="w-full sm:w-1/2 py-2 px-4 rounded-lg font-bold text-center uppercase transition-all bg-blue-600 hover:bg-blue-700 text-white shadow">
                                        Ya, Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
