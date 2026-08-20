<x-app-layout>
    @section('page-title', 'Evaluasi SKP & Perilaku Bawahan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
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

            .display-cell {
                width: 100%;
                min-height: 40px;
                padding: 6px 4px;
                display: block;
                box-sizing: border-box;
                font-size: 10px;
                line-height: 1.3;
                word-wrap: break-word;
                white-space: normal;
            }

            .display-cell.center {
                text-align: center;
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

            .table-skp-custom {
                border-collapse: collapse !important;
                border-style: hidden !important;
            }

            .table-skp-custom th,
            .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }

            .bg-grey-custom {
                background-color: #d1d5db !important;
            }
        </style>

        @php
            $p_perilaku = [];
            $labels = ['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'];
            foreach ($labels as $label) {
                $inds = $header->perilakuIndicators->where('aspek', $label);
                $p_perilaku[$label] = $inds
                    ->map(function ($i) {
                        return [
                            'id' => $i->id,
                            'indikator' => $i->indikator,
                            'nilai' => $i->nilai,
                            'bobot_persen' => round($i->bobot_persen),
                            'is_from_admin' => (bool) $i->is_from_admin,
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            $p_skp = number_format((float) $nilaiAkhirSkp, 2, '.', '');
            $p_isLengkap = !empty($isLengkap) ? 'true' : 'false';

            $userId = auth()->id();
            $isAssignedEvaluator =
                $header->penilai_utama_id == $userId ||
                $header->penilai_akhir_id == $userId ||
                $header->pejabat_penilai_id == $userId ||
                $header->atasan_pejabat_penilai_id == $userId;

            $isMyTurn = false;
            if ($header->status === 'Diajukan') {
                // Tahap 1: Pejabat Penilai / Penilai Utama
                if ($header->pejabat_penilai_id == $userId || $header->penilai_utama_id == $userId) {
                    $isMyTurn = true;
                }
            } elseif ($header->status === 'Review Akhir') {
                // Tahap 2: Atasan Pejabat Penilai / Penilai Akhir (Dekan/Rektor)
                if ($header->atasan_pejabat_penilai_id == $userId || $header->penilai_akhir_id == $userId) {
                    $isMyTurn = true;
                }
            } elseif ($header->status === 'Disetujui' && $isAssignedEvaluator) {
                $isMyTurn = true;
            }

            $isBypassRole =
                auth()->user()->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']) ||
                auth()->user()->roles->contains(fn($r) => str_contains($r->name, 'Kaprodi'));

            $btnLabel = 'Setujui & Verifikasi';
            if ($header->atasan_pejabat_penilai_id === $userId || $header->penilai_akhir_id === $userId) {
                $btnLabel = 'Setujui Final';
            } elseif ($header->pejabat_penilai_id === $userId || $header->penilai_utama_id === $userId) {
                $atasanName = $header->atasanPejabatPenilai?->profile?->nama_lengkap ?? ($header->atasanPejabatPenilai?->name ?? 'Atasan');
                $btnLabel = 'Setujui, Lanjutkan Ke ' . $atasanName;
            }
        @endphp

        <div class="max-w-full mx-auto p-4 font-sans" x-data="{
            isLengkap: {{ $p_isLengkap }},
            skp: {{ $p_skp }},
            perilaku: @js($p_perilaku),
            openAspect: null,
            isSubmitting: false,
            scoresMaster: @js($skpUtama->mapWithKeys(fn($i) => [$i->id => $i->penilaian->nilai_capaian ?? 0])),
            scoresTugas: @js($tugasTambahan->mapWithKeys(fn($i) => [$i->id => $i->nilai_capaian ?? 0])),
        
            getPredicateNormal(val) {
                let v = parseFloat(val);
                if (isNaN(v) || v == 0) return '';
                if (v <= 50) return '(Buruk)';
                if (v <= 60) return '(Sedang)';
                if (v <= 75) return '(Cukup)';
                if (v <= 90.99) return '(Baik)';
                return '(Sangat Baik)';
            },
        
            getAvgAspek(label) {
                let items = this.perilaku[label] || [];
                if (items.length === 0) return 0;
                let sum = 0;
                items.forEach(item => {
                    let nilai = parseFloat(item.nilai) || 0;
                    let bobot = parseFloat(item.bobot_persen) || 0;
                    sum += (nilai * bobot / 100);
                });
                return sum;
            },
            get sumPerilaku() {
                let total = 0;
                const labels = ['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'];
                labels.forEach(l => total += this.getAvgAspek(l));
                return total;
            },
            get avgPerilaku() {
                let total = 0;
                let count = 0;
                const labels = ['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'];
                labels.forEach(l => {
                    let items = this.perilaku[l] || [];
                    if(items.length > 0) {
                        total += this.getAvgAspek(l);
                        count++;
                    }
                });
                return count > 0 ? (this.sumPerilaku / count) : 0;
            },
        
            get realtimeSkp() {
                let masterKeys = Object.keys(this.scoresMaster);
                if (masterKeys.length === 0) return 0;
                let totalMaster = 0;
                masterKeys.forEach(k => totalMaster += parseFloat(this.scoresMaster[k]) || 0);
                let avgMaster = totalMaster / masterKeys.length;
                let totalTugas = 0;
                Object.keys(this.scoresTugas).forEach(k => totalTugas += parseFloat(this.scoresTugas[k]) || 0);
                return (avgMaster + totalTugas).toFixed(2);
            },
        
            get calcSkp60() {
                return (parseFloat(this.realtimeSkp) * 0.6);
            },
        
            get calcPerilaku40() {
                return (this.avgPerilaku * 0.4);
            },
        
            get finalScore() {
                return (parseFloat(this.calcSkp60) + parseFloat(this.calcPerilaku40)).toFixed(2);
            },
        
            get realtimePredikat() {
                let val = parseFloat(this.realtimeSkp);
                if (val > 90) return 'Sangat Baik';
                if (val >= 76) return 'Baik';
                if (val >= 61) return 'Cukup';
                if (val >= 51) return 'Kurang';
                return 'Sangat Kurang';
            },

            addDetail(label) {
                if (!this.perilaku[label]) this.perilaku[label] = [];
                this.perilaku[label].push({
                    id: null,
                    indikator: '',
                    nilai: 0,
                    is_from_admin: true
                });
            },

            removeDetail(label, index) {
                this.perilaku[label].splice(index, 1);
            }
        }">

            <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <a href="{{ route('penilai.evaluasi.index') }}" class="text-blue-600 hover:underline font-bold text-sm">&larr;
                    Kembali ke Daftar Bawahan</a>

                @if ($isBypassRole && !$isAssignedEvaluator)
                    <div class="bg-amber-50 border border-amber-200 px-4 py-2 rounded-xl flex items-center gap-3 shadow-sm">
                        <i class="fas fa-bolt text-amber-500"></i>
                        <span class="text-xs font-bold text-amber-800 uppercase tracking-tight">Mode Monitoring Pimpinan</span>
                    </div>
                @endif
            </div>

            {{-- 1. HEADER UTAMA --}}
            <div
                class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-indigo-600 p-3 rounded-xl shadow-lg shadow-indigo-200">
                        <i class="fas fa-file-signature text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Evaluasi Realisasi SKP</h1>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">
                            Periode: <span class="text-indigo-600">{{ $header->periode->nama_periode }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right hidden md:block">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pegawai</p>
                        <p class="text-sm font-black text-gray-800">{{ $bawahan->profile->nama_lengkap ?? $bawahan->name }}
                        </p>
                    </div>
                    <img src="{{ $bawahan->profile->foto ?? asset('images/default-avatar.png') }}"
                        class="w-10 h-10 rounded-full border-2 border-white shadow-md">
                </div>
            </div>

            {{-- 2. STATUS SKP ALERT --}}
            <div
                class="mb-6 p-4 rounded-xl shadow-sm border-l-4
                @if ($header->status == 'Disetujui') border-emerald-500 bg-emerald-50
                @elseif(in_array($header->status, ['Diajukan', 'Review Akhir'])) border-indigo-500 bg-indigo-50
                @elseif($header->status == 'Selesai') border-blue-500 bg-blue-50
                @elseif($header->status == 'Ditolak') border-red-500 bg-red-50
                @else border-gray-400 bg-gray-50 @endif">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tighter">
                            Status SKP:
                            @if ($header->status == 'Disetujui')
                                <span class="text-emerald-700">Dalam Proses Pelaksanaan</span>
                            @elseif ($header->status == 'Diajukan')
                                <span class="text-indigo-700">Menunggu Penilaian oleh {{ $header->pejabatPenilai?->profile?->nama_lengkap ?? ($header->pejabatPenilai?->name ?? 'Pejabat Penilai') }}</span>
                            @elseif ($header->status == 'Review Akhir')
                                <span class="text-indigo-700">Sudah Dinilai oleh {{ $header->pejabatPenilai?->profile?->nama_lengkap ?? ($header->pejabatPenilai?->name ?? 'Pejabat Penilai') }} dan Dalam Review {{ $header->atasanPejabatPenilai?->profile?->nama_lengkap ?? ($header->atasanPejabatPenilai?->name ?? 'Atasan Pejabat Penilai') }}</span>
                            @elseif ($header->status == 'Selesai')
                                <span class="text-blue-700">Sudah Di Review (Final)</span>
                                <span class="ml-2 bg-blue-100 text-blue-800 text-[10px] px-2 py-0.5 rounded-full border border-blue-200">Final</span>
                            @elseif ($header->status == 'Ditolak')
                                <span class="text-red-700">Dikembalikan ke Pegawai</span>
                            @else
                                <span class="text-gray-700">{{ $header->status }}</span>
                            @endif
                        </h3>
                        @if ($header->catatan_penilai)
                            <p class="mt-1 text-xs text-gray-600 italic">"{{ $header->catatan_penilai }}"</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. Persetujuan Monitoring --}}
            <div class="mb-6 bg-white border border-gray-300 rounded-xl shadow-md p-5">
                <h3 class="text-xs font-black text-gray-800 uppercase mb-4 tracking-widest flex items-center gap-2">
                    <i class="fas fa-shield-alt text-indigo-600"></i> Persetujuan Target SKP
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        class="p-3 rounded-lg border {{ $header->is_target_approved_atasan ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_atasan ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i
                                    class="fas {{ $header->is_target_approved_atasan ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 1</span>
                        </div>
                        <p
                            class="text-xs font-black {{ $header->is_target_approved_atasan ? 'text-emerald-700' : 'text-orange-700' }}">
                            ATASAN PEJABAT PENILAI</p>
                        <p class="text-[9px] text-gray-500 font-bold truncate mt-1">
                            {{ $header->atasanPejabatPenilai?->profile->nama_lengkap ?? ($header->atasanPejabatPenilai?->name ?? '......................') }}
                        </p>
                    </div>

                    <div
                        class="p-3 rounded-lg border {{ $header->is_target_approved_penilai ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_penilai ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i
                                    class="fas {{ $header->is_target_approved_penilai ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 2</span>
                        </div>
                        <p
                            class="text-xs font-black {{ $header->is_target_approved_penilai ? 'text-emerald-700' : 'text-orange-700' }}">
                            PEJABAT PENILAI</p>
                        <p class="text-[9px] text-gray-500 font-bold truncate mt-1">
                            {{ $header->pejabatPenilai?->profile->nama_lengkap ?? ($header->pejabatPenilai?->name ?? '......................') }}
                        </p>
                    </div>

                    @php
                        $totalActivitiesCount = \App\Models\MasterSkpTendik::where('user_id', $header->user_id)->count();
                        $approvedActivitiesCount = \App\Models\MasterSkpTendik::where('user_id', $header->user_id)
                            ->where('status_approval', 'approved')
                            ->count();
                        $isAllActivitiesApproved =
                            $totalActivitiesCount > 0 && $totalActivitiesCount === $approvedActivitiesCount;
                    @endphp
                    <div
                        class="p-3 rounded-lg border {{ $isAllActivitiesApproved ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-center mb-2">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isAllActivitiesApproved ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                <i class="fas {{ $isAllActivitiesApproved ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 3</span>
                        </div>
                        <p
                            class="text-xs font-black {{ $isAllActivitiesApproved ? 'text-emerald-700' : 'text-orange-700' }}">
                            PENILAI KEGIATAN</p>
                        <p class="text-[9px] text-gray-500 font-bold mt-1">{{ $approvedActivitiesCount }} /
                            {{ $totalActivitiesCount }} Item Tupoksi Disetujui</p>
                    </div>
                </div>
            </div>

            {{-- 4. IDENTITAS FORMAL --}}
            @php
                $pejabatPenilai = $header->pejabatPenilai;
                $atasanPejabatPenilai = $header->atasanPejabatPenilai;
                $pegawai = $header->user;
            @endphp
            <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-8 overflow-hidden">
                <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">
                    {{-- I. PEGAWAI YANG DINILAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold h-12">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 font-black">
                                I</div>
                            <div class="flex-1 px-3 flex items-center text-blue-800 text-[10px] font-black uppercase tracking-tight">
                                PEGAWAI YANG DINILAI</div>
                        </div>
                        @foreach ([['Nama', $pegawai?->profile?->nama_lengkap ?? ($pegawai?->name ?? '......................')], ['NIP', $pegawai?->profile?->nip ?? '......................'], ['Pangkat/Gol', $pegawai?->profile?->pangkat_golongan ?? '......................'], ['Jabatan', $pegawai ? ($pegawai->getRoleNames()->first() ?? '-') : '......................'], ['Unit Kerja', $pegawai?->profile?->unit_kerja ?? 'Fakultas Teknik']] as $i => $row)
                            <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-10">
                                <div
                                    class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">
                                    {{ $i + 1 }}</div>
                                <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 uppercase tracking-tighter">
                                    {{ $row[0] }}</div>
                                <div class="flex-1 px-3 font-bold text-gray-900 truncate text-xs">{{ $row[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                    {{-- II. PEJABAT PENILAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold h-12">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 font-black">
                                II</div>
                            <div class="flex-1 px-3 flex items-center text-emerald-800 text-[10px] font-black uppercase tracking-tight">
                                PEJABAT PENILAI</div>
                        </div>
                        @foreach ([['Nama', $pejabatPenilai?->profile?->nama_lengkap ?? ($pejabatPenilai?->name ?? '......................')], ['NIP', $pejabatPenilai?->profile?->nip ?? '......................'], ['Pangkat/Gol', $pejabatPenilai?->profile?->pangkat_golongan ?? '......................'], ['Jabatan', $pejabatPenilai ? ($pejabatPenilai->getRoleNames()->first() ?? '-') : '......................'], ['Unit Kerja', $pejabatPenilai?->profile?->unit_kerja ?? 'Fakultas Teknik']] as $i => $row)
                            <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-10">
                                <div
                                    class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">
                                    {{ $i + 1 }}</div>
                                <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 uppercase tracking-tighter">
                                    {{ $row[0] }}</div>
                                <div class="flex-1 px-3 font-bold text-gray-900 truncate text-xs">{{ $row[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white flex flex-col min-w-0">
                    <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold h-12">
                        <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 font-black">
                            III</div>
                        <div class="flex-1 px-3 flex items-center text-indigo-800 text-[10px] font-black uppercase tracking-tight">
                            ATASAN PEJABAT PENILAI</div>
                    </div>
                    @foreach ([['Nama', $atasanPejabatPenilai?->profile?->nama_lengkap ?? ($atasanPejabatPenilai?->name ?? '......................')], ['NIP', $atasanPejabatPenilai?->profile?->nip ?? '......................'], ['Pangkat/Gol', $atasanPejabatPenilai?->profile?->pangkat_golongan ?? '......................'], ['Jabatan', $atasanPejabatPenilai ? ($atasanPejabatPenilai->getRoleNames()->first() ?? '-') : '......................'], ['Unit Kerja', $atasanPejabatPenilai?->profile?->unit_kerja ?? 'Fakultas Teknik']] as $i => $row)
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-10 last:border-0">
                            <div
                                class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">
                                {{ $i + 1 }}</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 uppercase tracking-tighter">
                                {{ $row[0] }}</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate text-xs">{{ $row[1] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 5. TABEL TUPOKSI & OPERASIONAL (TABEL 1) --}}
            @if ($jenisSkp == 'Tendik' && isset($masterActivities) && $masterActivities->count() > 0)
                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter mb-4 mt-8">📝 A. Sasaran Kerja Pegawai (SKP)</h2>
                <div class="mb-2 ml-1 flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Tupoksi & Operasional SKP</h3>
                </div>
                <div class="w-full shadow-md border border-gray-300 rounded-xl bg-white overflow-hidden mb-8">
                    <div class="overflow-x-auto w-full custom-scrollbar">
                        <table class="w-full border-collapse bg-white">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 h-10">
                                    <th rowspan="2" class="px-2" style="width: 3%;">No</th>
                                    <th rowspan="2" class="px-3" style="width: 13%;">I. Jabatan/Bidang</th>
                                    <th rowspan="2" class="px-3" style="width: 20%;">II. Kegiatan Tupoksi</th>
                                    <th rowspan="2" class="px-3" style="width: 22%;">III. Jenis Aktivitas Unit Kerja</th>
                                    <th rowspan="2" class="px-2" style="width: 8%;">Penilai</th>
                                    <th colspan="3" class="border-b border-slate-300 px-1" style="width: 9%;">Kategori</th>
                                    <th rowspan="2" class="px-2" style="width: 6%;">Periode</th>
                                    <th rowspan="2" class="px-1" style="width: 4%;">Freq</th>
                                    <th rowspan="2" class="px-1" style="width: 4%;">Dur</th>
                                    <th rowspan="2" class="px-1" style="width: 3%;">SDM</th>
                                    <th rowspan="2" class="px-3" style="width: 8%;">Vol Kerja</th>
                                </tr>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th style="width: 3%;" class="text-[8px] px-0">Utama</th>
                                    <th style="width: 3%;" class="text-[8px] px-0">Prd</th>
                                    <th style="width: 3%;" class="text-[8px] px-0">Bkn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($masterActivities as $index => $item)
                                    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-200 text-[10px]">
                                        <td class="text-center font-bold text-slate-400">
                                            <div class="display-cell center text-[9px]">{{ $index + 1 }}</div>
                                        </td>
                                        <td><div class="display-cell font-semibold text-blue-800 uppercase">{{ $item->role_name }}</div></td>
                                        <td><div class="display-cell font-bold text-gray-800">{{ $item->tupoksi }}</div></td>
                                        <td><div class="display-cell text-gray-700 leading-snug">{{ $item->jenis_aktivitas }}</div></td>
                                        <td><div class="display-cell center font-semibold text-emerald-700">{{ $item->penilai_role ?? '-' }}</div></td>
                                        <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black">{{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? '✓' : '' }}</td>
                                        <td class="checkbox-cell border-r border-slate-300 text-amber-600 font-black">{{ is_array($item->kategori) && in_array('Peredam', $item->kategori) ? '✓' : '' }}</td>
                                        <td class="checkbox-cell text-red-600 font-black">{{ is_array($item->kategori) && in_array('Bukan Kinerja', $item->kategori) ? '✓' : '' }}</td>
                                        <td><div class="display-cell center font-bold text-slate-600">{{ $item->periode }}</div></td>
                                        <td><div class="display-cell center font-bold">{{ $item->frekuensi_per_tahun }}</div></td>
                                        <td><div class="display-cell center font-bold">{{ $item->durasi_menit }}</div></td>
                                        <td><div class="display-cell center font-bold">{{ $item->jumlah_sdm }}</div></td>
                                        <td class="bg-slate-50"><div class="display-cell center font-black text-indigo-700">{{ number_format($item->volume_kerja, 2, ',', '.') }}</div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- 6. TABEL REALISASI (TABEL 2) --}}
            <div class="mb-2 ml-1 flex items-center gap-2">
                <div class="w-1.5 h-4 bg-emerald-600 rounded-full"></div>
                <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Target dan Realisasi SKP</h3>
            </div>
            <div class="bg-white overflow-hidden mb-8 border border-gray-300 shadow-md w-full rounded-xl">
                <div class="overflow-x-auto w-full custom-scrollbar">
                    <table class="w-full min-w-[1100px] border-collapse text-[11px] border-none">
                        <thead class="text-gray-900 font-bold text-center align-middle bg-gray-100 border-b border-gray-300 uppercase">
                            <tr>
                                <th rowspan="2" class="border-r border-gray-300 p-2 w-8">NO</th>
                                <th rowspan="2" class="border-r border-gray-300 p-3 min-w-[250px] uppercase tracking-wider text-left align-middle bg-slate-100 text-slate-700 shadow-inner">III. Kegiatan Tugas Jabatan</th>
                                <th rowspan="2" class="border-r border-gray-300 p-2 w-12">AK</th>
                                <th colspan="4" class="border-r border-b border-gray-300 p-2 uppercase">TARGET</th>
                                <th rowspan="2" class="border-r border-gray-300 p-2 w-12">AK(Realisasi)</th>
                                <th colspan="4" class="border-r border-b border-gray-300 p-2 uppercase text-emerald-800">REALISASI</th>
                                <th rowspan="2" class="border-r border-gray-300 p-2 w-16">PENGHITUNGAN</th>
                                <th rowspan="2" class="border-r border-gray-300 p-2 w-16">NILAI CAPAIAN</th>
                                <th rowspan="2" class="p-2 w-16 bg-gray-100">BUKTI</th>
                            </tr>
                            <tr>
                                <th class="border-r border-gray-300 p-1 w-16 uppercase text-[9px]">Kuant</th>
                                <th class="border-r border-gray-300 p-1 w-12 uppercase text-[9px]">Kual</th>
                                <th class="border-r border-gray-300 p-1 w-12 uppercase text-[9px]">Waktu</th>
                                <th class="border-r border-gray-300 p-1 w-20 uppercase text-[9px]">Biaya</th>
                                <th class="border-r border-gray-300 p-1 w-48 uppercase text-[9px] text-emerald-800">Kuantitas (Bukti)</th>
                                <th class="border-r border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800">Kualitas</th>
                                <th class="border-r border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800">Waktu</th>
                                <th class="border-r border-gray-300 p-1 w-24 uppercase text-[9px] text-emerald-800">Biaya</th>
                            </tr>
                        </thead>
                        @if ($jenisSkp == 'Tendik')
                            @foreach ($skpUtama as $induk)
                                @php
                                    $nilai = $induk->penilaian;
                                    $totalAK = $induk->details->sum('ak');
                                    $totalKuantitas = $induk->details->sum('kuantitas_output');
                                    $avgKualitas = $induk->details->avg('kualitas_mutu') ?? 100;
                                    $maxWaktu = $induk->details->max('waktu') ?? 0;
                                    $totalBiaya = $induk->details->sum('biaya');
                                @endphp
                                <tbody x-data="{ expanded: false }" class="text-gray-800 border-b border-gray-300">
                                    <tr @click="expanded = !expanded" class="hover:bg-blue-50 transition-colors cursor-pointer group">
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-bold text-gray-400 relative">
                                            <div class="display-cell center text-[9px] mb-2">{{ $loop->iteration }}</div>
                                            <div class="flex justify-center">
                                                <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 transition-all duration-300 group-hover:scale-110 shadow-sm" :class="expanded ? 'rotate-90 bg-blue-600 text-white shadow-inner' : ''">
                                                    <i class="fas fa-chevron-right text-[10px]"></i>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="border-r border-gray-300 p-3 align-top whitespace-normal text-left">
                                            <div class="text-[11px] font-bold text-slate-800 leading-relaxed mb-2">
                                                {!! nl2br(e($induk->tupoksi)) !!}
                                            </div>
                                            <div class="inline-flex items-center gap-1.5 text-[9px] font-bold text-blue-600 bg-blue-50/80 hover:bg-blue-100 transition-colors px-2.5 py-1 rounded-md border border-blue-200 w-fit">
                                                <i class="fas fa-hand-pointer"></i>
                                                <span>Klik untuk melihat {{ $induk->details->count() }} aktivitas</span>
                                            </div>
                                        </td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-black text-indigo-700">
                                            {{ number_format($totalAK, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top">{{ $totalKuantitas }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top">{{ round($avgKualitas) }}%</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top">{{ $maxWaktu }} bln</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top">
                                            {{ number_format($totalBiaya, 0, ',', '.') }}</td>

                                        <td class="border-r border-gray-300 p-2 text-center align-top font-black text-emerald-700 bg-emerald-50/20">
                                            {{ number_format($nilai->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-bold text-emerald-800 bg-emerald-50/20">
                                            {{ $nilai->realisasi_kuantitas ?? 0 }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-bold text-emerald-800 bg-emerald-50/20">
                                            {{ round($nilai->realisasi_kualitas ?? 0) }}%</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-bold text-emerald-800 bg-emerald-50/20">
                                            {{ $nilai->realisasi_waktu ?? 0 }} bln</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-top font-bold text-emerald-800 bg-emerald-50/20">
                                            {{ number_format($nilai->realisasi_biaya ?? 0, 0, ',', '.') }}</td>

                                        <td class="border-r border-gray-300 p-2 text-center align-middle font-bold text-indigo-700 bg-gray-50/50">
                                            {{ number_format($nilai->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center align-middle font-black text-indigo-800 bg-indigo-50/30">
                                            {{ number_format($nilai->nilai_capaian ?? 0, 2, ',', '.') }}</td>

                                        <td class="p-2 text-center align-middle bg-gray-50">
                                            @if (isset($nilai->bukti) && is_array($nilai->bukti) && count($nilai->bukti) > 0)
                                                <div class="flex flex-wrap justify-center gap-1">
                                                    @foreach ($nilai->bukti as $idxB => $path)
                                                        <a href="{{ route('penilai.evaluasi.download-bukti', ['path' => $path]) }}"
                                                            target="_blank"
                                                            class="inline-flex items-center justify-center w-6 h-6 rounded bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-sm"
                                                            title="Bukti {{ $idxB + 1 }}">
                                                            <i class="fas fa-file-pdf text-[10px]"></i>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-[9px] text-gray-400 italic">No File</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr x-show="expanded" x-transition.opacity style="display: none;" class="bg-slate-50">
                                        <td colspan="15" class="p-4 border-t border-slate-200">
                                            <div class="ml-8 border border-slate-200 rounded-lg overflow-hidden shadow-sm bg-white">
                                                <div class="bg-slate-100 border-b border-slate-200 p-2 px-3 flex items-center gap-2">
                                                    <i class="fas fa-list-ul text-slate-500 text-[10px]"></i>
                                                    <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">Rincian Aktivitas Unit Kerja</span>
                                                </div>
                                                <table class="w-full text-[10px] border-collapse text-left">
                                                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                                                        <tr>
                                                            <th class="p-2 border-r border-slate-200 text-center w-8">#</th>
                                                            <th class="p-2 border-r border-slate-200">Jenis Aktivitas</th>
                                                            <th class="p-2 border-r border-slate-200 text-center w-12">AK</th>
                                                            <th class="p-2 border-r border-slate-200 text-center w-16">Kuantitas</th>
                                                            <th class="p-2 border-r border-slate-200 text-center w-16">Kualitas</th>
                                                            <th class="p-2 border-r border-slate-200 text-center w-16">Waktu</th>
                                                            <th class="p-2 text-center w-24">Biaya</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($induk->details as $idx => $detail)
                                                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                                                <td class="p-2 border-r border-slate-100 text-center text-slate-400">{{ $idx + 1 }}</td>
                                                                <td class="p-2 border-r border-slate-100 text-slate-800 font-medium">{!! nl2br(e($detail->jenis_aktivitas)) !!}</td>
                                                                <td class="p-2 border-r border-slate-100 text-center text-indigo-600 font-bold">{{ number_format($detail->ak, 2, ',', '.') }}</td>
                                                                <td class="p-2 border-r border-slate-100 text-center text-slate-600">{{ $detail->kuantitas_output }}</td>
                                                                <td class="p-2 border-r border-slate-100 text-center text-slate-600">{{ round($detail->kualitas_mutu) }}%</td>
                                                                <td class="p-2 border-r border-slate-100 text-center text-slate-600">{{ $detail->waktu }} bln</td>
                                                                <td class="p-2 text-center text-slate-600">{{ number_format($detail->biaya, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @endforeach
                        @else
                            <tbody class="text-gray-800">
                                {{-- DOSEN LAYOUT (Simplified for unified view) --}}
                                @foreach ($skpUtama as $item)
                                    @php $nilai = $item->penilaian; @endphp
                                    <tr class="hover:bg-blue-50 transition-colors border-b border-gray-300">
                                        <td class="border-r border-gray-300 p-2 text-center font-bold text-gray-400">
                                            <div class="display-cell center text-[9px]">{{ $loop->iteration }}</div>
                                        </td>
                                        <td class="border-r border-gray-300 p-3 align-top whitespace-normal text-left">
                                            <div class="text-[11px] font-bold text-slate-800 leading-relaxed">
                                                {!! nl2br(e($item->kegiatan)) !!}
                                            </div>
                                        </td>
                                        <td class="border-r border-gray-300 p-2 text-center text-indigo-700 font-bold">
                                            {{ number_format($item->ak ?? 0, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center">{{ $item->kuantitas_output }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center">{{ round($item->kualitas_mutu) }}%</td>
                                        <td class="border-r border-gray-300 p-2 text-center">{{ $item->waktu }} bln</td>
                                        <td class="border-r border-gray-300 p-2 text-center">
                                            {{ number_format($item->biaya ?? 0, 0, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-emerald-700 font-bold">
                                            {{ number_format($nilai->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-emerald-800 font-bold">
                                            {{ $nilai->realisasi_kuantitas ?? 0 }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-emerald-800 font-bold">
                                            {{ round($nilai->realisasi_kualitas ?? 0) }}%</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-emerald-800 font-bold">
                                            {{ $nilai->realisasi_waktu ?? 0 }} bln</td>
                                        <td class="border-r border-gray-300 p-2 text-center text-emerald-800 font-bold">
                                            {{ number_format($nilai->realisasi_biaya ?? 0, 0, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center font-bold text-indigo-700">
                                            {{ number_format($nilai->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                        <td class="border-r border-gray-300 p-2 text-center font-black text-indigo-800">
                                            {{ number_format($nilai->nilai_capaian ?? 0, 2, ',', '.') }}</td>
                                        <td class="p-2 text-center align-middle bg-gray-50 italic text-[10px] text-gray-400">Dosen Module
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif

                        <tbody class="text-gray-800">
                            <tr class="font-bold bg-gray-100 border-b border-gray-800 border-t border-gray-800">
                                <td class="border-r border-gray-300 p-2 text-center font-bold text-gray-800">IV</td>
                                <td colspan="14" class="p-2 py-3">
                                    <span class="font-black text-gray-900 uppercase tracking-widest">Tugas Tambahan dan Kreativitas</span>
                                </td>
                            </tr>
                            @if (isset($tugasTambahan) && $tugasTambahan->count() > 0)
                                @foreach ($tugasTambahan as $tugas)
                                    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300 bg-white">
                                        <td class="border-r border-gray-300 p-2 text-center align-top">
                                            <div class="display-cell center text-[9px] font-bold text-gray-400">{{ $loop->iteration }}</div>
                                        </td>
                                        <td class="border-r border-gray-300 p-2 font-semibold text-gray-700 align-top whitespace-normal">
                                            {{ $tugas->jenis }}
                                        </td>
                                        <td class="border-r border-gray-300 p-2 text-center font-bold align-top text-indigo-700">
                                            {{ round($tugas->ak ?? 1) }}
                                        </td>
                                        <td colspan="4" class="border-r border-gray-300 p-2 font-bold align-top whitespace-normal">
                                            {{ $tugas->kegiatan }}
                                        </td>
                                        <td class="border-r border-gray-300 p-0 align-top text-emerald-700">
                                            <div class="p-2 text-center font-bold">{{ round($tugas->ak_realisasi ?? 0) }}</div>
                                        </td>
                                        <td colspan="4" class="border-r border-gray-300 p-2 align-top whitespace-normal">
                                            <div class="flex flex-col">
                                                <div class="text-[11px] text-gray-800 leading-snug mb-2">
                                                    {!! nl2br(e($tugas->realisasi_kegiatan ?? '-')) !!}
                                                </div>
                                                @if ($tugas->realisasi_bukti)
                                                    <div class="mt-auto">
                                                        <a href="{{ route('penilai.evaluasi.download-bukti', ['path' => $tugas->realisasi_bukti]) }}" target="_blank"
                                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors font-bold text-[8px]">
                                                            <i class="fas fa-file-pdf"></i> Lihat Bukti
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="border-r border-gray-300 p-2 text-center font-bold align-middle bg-gray-50/50">
                                            {{ number_format($tugas->penghitungan ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td class="p-2 text-center bg-gray-50 align-middle">
                                            <div class="font-black text-indigo-700">{{ number_format($tugas->nilai_capaian ?? 0, 2, ',', '.') }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="border-b border-gray-800 bg-gray-50">
                                    <td colspan="15"
                                        class="p-8 text-center text-gray-400 italic font-black uppercase tracking-widest">Belum Ada Data
                                        Tugas Tambahan</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="border-t-2 border-gray-800">
                            <tr class="bg-gray-100 font-black">
                                <td colspan="13"
                                    class="border-r border-gray-300 p-4 text-right pr-6 text-base bg-blue-50 text-blue-900 uppercase tracking-widest">
                                    NILAI CAPAIAN SKP TENDIK</td>
                                <td class="p-3 text-center text-xl text-indigo-800 bg-blue-100 shadow-inner">
                                    <span x-text="realtimeSkp.toString().replace('.', ',')"></span>
                                    <div class="text-[10px] font-bold text-blue-600 mt-1 uppercase tracking-widest"
                                        x-text="realtimePredikat"></div>
                                </td>
                                <td class="bg-gray-100"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <form action="{{ route('penilai.evaluasi.verify', $header->id) }}" method="POST" @submit="isSubmitting = true">
                @csrf @method('PATCH')
                <input type="hidden" name="is_draft" id="is_draft" value="0">
                <input type="hidden" name="status" value="Selesai">

                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter mb-4 mt-12">🌟 B. Penilaian Perilaku Kerja (Oleh Pejabat Penilai)</h2>
                <div class="bg-white mb-8 w-full overflow-x-auto pb-4 pt-1">
                    <div class="border border-black rounded-xl overflow-hidden min-w-full inline-block align-middle shadow-sm">
                        <table class="w-full text-sm table-skp-custom bg-white border-none" style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white">UNSUR YANG DINILAI
                                    </th>
                                    <th class="text-center font-bold bg-white w-32">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-300">
                                    <td :rowspan="6 + 4"
                                        class="text-center font-bold align-middle w-12 bg-white text-base border-r border-gray-300">4.
                                    </td>
                                    <td colspan="2" class="font-bold bg-white italic border-r border-gray-800">a. Sasaran Kerja
                                        Pegawai (SKP)</td>
                                    <td class="text-center bg-white w-32 border-r border-gray-800 font-bold"
                                        x-text="realtimeSkp.toString().replace('.', ',')"></td>
                                    <td class="text-center bg-white w-16 border-r border-gray-800">x</td>
                                    <td class="text-center font-bold bg-white w-20 border-r border-gray-800">60%</td>
                                    <td class="text-center font-bold bg-white text-blue-700 text-lg">
                                        <span x-text="(realtimeSkp * 0.6).toFixed(2).toString().replace('.', ',')"></span>
                                    </td>
                                </tr>

                                @foreach (['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'] as $index => $label)
                                    <tr class="border-b border-gray-300">
                                        @if ($loop->first)
                                            <td rowspan="9"
                                                class="font-bold align-middle w-48 bg-white px-4 border-r border-gray-800 italic">b.
                                                Perilaku Kerja</td>
                                        @endif
                                        <td class="align-top bg-white cursor-pointer hover:bg-emerald-50 transition-colors border-r border-gray-800"
                                            @click="openAspect === '{{ $label }}' ? openAspect = null : openAspect = '{{ $label }}'">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="mt-1 text-[13px] font-bold">{{ $loop->iteration }}.
                                                    {{ $label }} @if ($label == 'Kepemimpinan')
                                                        <i class="text-[9px] text-gray-400 font-normal lowercase tracking-tighter">(opsional)</i>
                                                    @endif
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[9px] text-emerald-500 font-bold uppercase tracking-tighter">Beri
                                                        Nilai</span>
                                                    <i class="fas fa-chevron-down text-[9px] transition-transform text-emerald-500"
                                                        :class="openAspect === '{{ $label }}' ? 'rotate-180' : ''"></i>
                                                </div>
                                            </div>

                                            <div x-show="openAspect === '{{ $label }}'" x-cloak class="flex flex-col gap-2 mt-2 pb-4"
                                                @click.stop>
                                                <div class="flex justify-between items-center px-1">
                                                    <p class="font-black text-[10px] uppercase tracking-widest text-emerald-800">Rincian Evaluasi: {{ $label }}</p>
                                                    {{-- Button Tambah disembunyikan sesuai permintaan --}}
                                                    {{-- @if ($isAssignedEvaluator && $isMyTurn)
                                                        <button type="button" @click="addDetail('{{ $label }}')" 
                                                            class="text-[9px] bg-emerald-600 text-white px-2 py-1 rounded hover:bg-emerald-700 transition-colors uppercase font-bold shadow-sm">
                                                            <i class="fas fa-plus mr-1"></i> Tambah Rincian
                                                        </button>
                                                    @endif --}}
                                                </div>
                                                
                                                <div class="space-y-4">
                                                    <template x-for="(item, i) in perilaku['{{ $label }}']" :key="i">
                                                        <div
                                                            class="bg-white p-3 border border-emerald-200 rounded-xl shadow-sm relative group">
                                                            
                                                            {{-- Button Delete disembunyikan sesuai permintaan --}}
                                                            {{-- @if ($isAssignedEvaluator && $isMyTurn)
                                                                <button type="button" @click="removeDetail('{{ $label }}', i)"
                                                                    class="absolute -top-2 -right-2 bg-red-500 text-white w-5 h-5 rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition-colors">
                                                                    <i class="fas fa-times text-[10px]"></i>
                                                                </button>
                                                            @endif --}}

                                                            <div class="flex flex-col md:flex-row gap-4 w-full items-start">
                                                                <div class="flex-1 min-w-0 w-full">
                                                                    <label class="block text-[8px] font-bold text-gray-400 uppercase mb-1">Indikator / Bukti Realisasi</label>
                                                                    <textarea :name="`perilaku[${'{{ $label }}'}][${i}][indikator]`"
                                                                        x-model="item.indikator"
                                                                        class="w-full text-[10px] font-bold text-gray-700 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 p-2 min-h-[40px] bg-gray-50/30"
                                                                        placeholder="Masukkan deskripsi indikator..."
                                                                        :disabled="!{{ $isAssignedEvaluator ? 'true' : 'false' }} || !{{ $isMyTurn ? 'true' : 'false' }}"></textarea>
                                                                <div class="w-full md:w-20 shrink-0">
                                                                    <label class="block text-[8px] font-bold text-blue-500 uppercase mb-1 md:text-left text-center">Persentase</label>
                                                                    <div class="relative flex md:justify-start justify-center">
                                                                        <input type="number" x-model.number="item.bobot_persen"
                                                                            :name="`perilaku[${'{{ $label }}'}][${i}][bobot_persen]`"
                                                                            step="1" min="0" max="100"
                                                                            class="w-full text-center px-1 py-1 pr-4 text-sm font-black text-blue-700 border-blue-300 rounded focus:ring-blue-500 no-scroll bg-gray-50 cursor-not-allowed"
                                                                            disabled>
                                                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-blue-500">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="w-full md:w-32 shrink-0">
                                                                    <label class="block text-[8px] font-bold text-emerald-500 uppercase mb-1 md:text-left text-center">Nilai Evaluasi</label>
                                                                    <div class="flex md:justify-start justify-center">
                                                                        <input type="number" x-model.number="item.nilai"
                                                                            :name="`perilaku[${'{{ $label }}'}][${i}][nilai]`"
                                                                            step="0.01" min="0" max="100"
                                                                            class="w-full text-center px-1 py-1 text-sm font-black text-emerald-700 border-emerald-300 rounded focus:ring-emerald-500 no-scroll bg-white"
                                                                            :disabled="!{{ $isAssignedEvaluator ? 'true' : 'false' }} || !{{ $isMyTurn ? 'true' : 'false' }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][id]`"
                                                                x-model="item.id">
                                                            <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][is_from_admin]`"
                                                                x-model="item.is_from_admin">
                                                        </div>
                                                    </template>

                                                    <template x-if="!perilaku['{{ $label }}'] || perilaku['{{ $label }}'].length === 0">
                                                        <div class="p-4 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest italic">Belum Ada Rincian Penilaian</p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle bg-white border-r border-gray-800">
                                            <span class="font-bold text-lg text-emerald-800"
                                                x-text="parseFloat(getAvgAspek('{{ $label }}')).toFixed(2).replace('.', ',')"></span>
                                        </td>
                                        <td colspan="2"
                                            class="text-center align-middle bg-white text-emerald-700 font-bold text-[10px] uppercase tracking-tighter border-r border-gray-800"
                                            x-text="getPredicateNormal(getAvgAspek('{{ $label }}'))"></td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>
                                @endforeach

                                <tr class="border-b border-gray-300 font-bold">
                                    <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">7. Jumlah</td>
                                    <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800"
                                        x-text="sumPerilaku.toFixed(2).replace('.', ',')"></td>
                                    <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>
                                <tr class="border-b border-gray-300 font-bold">
                                    <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">8. Nilai rata &ndash; rata
                                    </td>
                                    <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800"
                                        x-text="avgPerilaku.toFixed(2).replace('.', ',')"></td>
                                    <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                    <td class="bg-grey-custom"></td>
                                </tr>
                                <tr class="border-b border-gray-300 font-bold">
                                    <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">9. Nilai Perilaku Kerja</td>
                                    <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800"
                                        x-text="avgPerilaku.toFixed(2).replace('.', ',')"></td>
                                    <td class="text-center align-middle bg-white border-r border-gray-800 font-bold italic">x</td>
                                    <td class="text-center font-bold align-middle bg-white w-20 border-r border-gray-800">40%</td>
                                    <td class="text-center font-bold align-middle bg-white text-emerald-800 text-lg"
                                        x-text="calcPerilaku40.toFixed(2).replace('.', ',')"></td>
                                </tr>
                                <tr class="border-t-[3px] border-double border-gray-800">
                                    <td colspan="6" class="text-center font-bold text-lg p-3 bg-white tracking-widest uppercase">
                                        NILAI PRESTASI KERJA</td>
                                    <td class="text-center font-bold text-lg p-3 bg-white text-indigo-800">
                                        (<span x-text="finalScore.toString().replace('.', ',')"></span>)
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- C. KEPUTUSAN EVALUASI & VERIFIKASI --}}
                <div class="bg-white overflow-hidden shadow-lg border border-gray-300 p-6 mb-12 w-full rounded-2xl">
                    <h3 class="text-sm font-black text-gray-800 uppercase mb-6 tracking-widest border-b border-gray-100 pb-3">C.
                        KEPUTUSAN EVALUASI & VERIFIKASI</h3>

                    <div
                        class="flex flex-col md:flex-row items-center justify-between gap-8 mb-8 bg-slate-50 p-6 rounded-2xl border border-slate-200 shadow-inner">
                        <div class="text-center md:text-left">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Hasil Evaluasi Akhir</p>
                            <div class="flex items-baseline gap-3">
                                <span class="text-6xl font-black text-gray-900"
                                    x-text="finalScore.toString().replace('.', ',')"></span>
                                <span class="text-xl font-black text-indigo-600 uppercase tracking-tighter"
                                    x-text="realtimePredikat"></span>
                            </div>
                        </div>
                        <div class="w-full md:w-auto">
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 italic">Catatan /
                                Masukan Evaluator</label>
                            <textarea name="catatan_penilai" rows="3"
                                class="w-full md:w-80 lg:w-96 text-sm border border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm p-3 bg-white disabled:bg-gray-100"
                                placeholder="Berikan saran pengembangan..." @if (!$isAssignedEvaluator || !$isMyTurn) disabled @endif>{{ $header->catatan_penilai }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @if ($isAssignedEvaluator && $isMyTurn)
                            <button type="submit" :disabled="isSubmitting"
                                onclick="document.getElementById('is_draft').value = '1';"
                                class="w-full sm:w-auto px-8 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-lg transition-all uppercase tracking-widest text-sm flex items-center justify-center gap-2">
                                <i class="fas fa-save text-xl"></i>
                                <span>Simpan Perubahan (Sementara)</span>
                            </button>

                            <button type="submit" :disabled="isSubmitting"
                                onclick="document.getElementById('is_draft').value = '0';"
                                class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 px-8 rounded-2xl shadow-xl transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 uppercase tracking-widest text-sm">
                                <i class="fas fa-check-double text-xl" x-show="!isSubmitting"></i>
                                <i class="fas fa-circle-notch fa-spin text-xl" x-show="isSubmitting" x-cloak></i>
                                <span x-text="isSubmitting ? 'Sedang Memproses...' : '{{ $btnLabel }}'"></span>
                            </button>
                        @elseif($isAssignedEvaluator && !$isMyTurn)
                            <div
                                class="w-full bg-blue-50 text-blue-800 font-black py-5 px-8 rounded-2xl text-center border-2 border-dashed border-blue-200 uppercase tracking-[0.2em] text-xs">
                                <i class="fas fa-history mr-2 animate-pulse"></i> Berhasil Diproses / Menunggu Tahap Selanjutnya
                            </div>
                        @else
                            <div
                                class="w-full bg-gray-100 text-gray-500 font-black py-5 px-8 rounded-2xl text-center border-2 border-dashed border-gray-300 uppercase tracking-[0.2em] text-xs">
                                <i class="fas fa-lock mr-2"></i> Mode Monitoring (Pimpinan / Administrator)
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        @push('scripts')
            <script>
                // Mouse wheel scroll prevention on numbers
                document.addEventListener('wheel', function(event) {
                    if (document.activeElement.type === 'number') {
                        document.activeElement.blur();
                    }
                }, {
                    passive: false
                });

                function autoResize(textarea) {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                }

                // Initialize resize
                document.querySelectorAll('textarea').forEach(el => autoResize(el));
            </script>
        @endpush
    @endsection
</x-app-layout>
