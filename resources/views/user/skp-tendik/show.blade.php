<x-app-layout>
    @section('page-title', 'SKP Tendik (Operasional)')

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

            .display-cell.right {
                text-align: right;
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
            $p_perilaku = [];
            foreach (['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'] as $label) {
                $p_perilaku[$label] = [];
                if (isset($skpHeader) && $skpHeader->perilakuIndicators) {
                    foreach ($skpHeader->perilakuIndicators as $p) {
                        if ($p->aspek == $label) {
                            $p_perilaku[$label][] = [
                                'id' => $p->id,
                                'indikator' => $p->indikator,
                                'nilai' => $p->nilai,
                                'bobot_persen' => round($p->bobot_persen),
                                'is_from_admin' => $p->is_from_admin,
                            ];
                        }
                    }
                }
            }

            $p_skp = number_format((float) $nilaiAkhirSkp, 2, '.', '');
            $p_isLengkap = !empty($isLengkap) ? 'true' : 'false';

            $isFinalized = (isset($skpHeader) && in_array($skpHeader->status, ['Target Diajukan', 'Disetujui', 'Review Akhir', 'Selesai', 'Diajukan', 'Pending Utama', 'Pending Akhir']));

            // ✅ Lakukan penguncian form jika sudah difinalisasi
            $isFormLocked = $isFinalized;

            // ✅ Realisasi bisa diisi HANYA jika sudah di-approve 3 pimpinan (isRealizable)
            // DAN status belum diajukan kembali untuk review realisasi (Status: Disetujui)
            $canInputRealisasi = isset($isRealizable) && $isRealizable && isset($skpHeader) && $skpHeader->status == 'Disetujui';



            // --- LOGIKA DESKRIPSI STATUS SKP ---
            $statusDeskripsi = "Belum Di Finalisasi";
            $statusColor = "text-orange-600";
            $statusBg = "bg-orange-50";

            if (isset($skpHeader)) {
                if ($skpHeader->status === 'Selesai') {
                    $statusDeskripsi = "Skp Complete / Skp Sudah di nilai oleh pimpinan";
                    $statusColor = "text-emerald-700";
                    $statusBg = "bg-emerald-100";
                } elseif (in_array($skpHeader->status, ['Diajukan', 'Review Akhir'])) {
                    $statusDeskripsi = "Dalam Review Penilaian";
                    $statusColor = "text-blue-700";
                    $statusBg = "bg-blue-100";
                } elseif ($skpHeader->status === 'Disetujui') {
                    $statusDeskripsi = "Dalam Pengerjaan / Sedang Di Realisasikan Oleh Pegawai " . (Auth::user()->profile->nama_lengkap ?? Auth::user()->name);
                    $statusColor = "text-emerald-600";
                    $statusBg = "bg-emerald-50";
                } elseif ($isFinalized) {
                    if ($skpHeader->is_target_approved_atasan && !$skpHeader->is_target_approved_penilai) {
                        $statusDeskripsi = "Sudah di Setujui oleh Atasan Pejabat Penilai, sedang menunggu persetujuan dari Pejabat Penilai";
                    } elseif (!$skpHeader->is_target_approved_atasan && $skpHeader->is_target_approved_penilai) {
                        $statusDeskripsi = "Sudah di Setujui oleh Pejabat Penilai, sedang menunggu persetujuan dari Atasan Pejabat Penilai";
                    } elseif (isset($isRealizable) && $isRealizable && !$skpHeader->is_target_approved_penilai) {
                         // Fallback logic for Level 3
                         $statusDeskripsi = "Sudah di Setujui oleh Seluruh Penilai Kegiatan, sedang menunggu persetujuan dari Pejabat Penilai";
                    } else {
                        $statusDeskripsi = "Sudah Di Finalisasi, Dalam Review Persetujuan";
                    }
                    $statusColor = "text-blue-600";
                    $statusBg = "bg-blue-50";
                }
            }
        @endphp

        {{-- ✅ FIX: Memindahkan script x-data keluar agar tidak terjadi error bentrok kutip pada HTML --}}
        <script>
            // ✅ Fungsi Kalkulasi Real-time Capaian
            window.recalculateLiveRow = function(id) {
                const row = document.getElementById(`row-realisasi-${id}`);
                if (!row) return;

                const tKuant = parseFloat(row.dataset.targetKuantitas) || 1;
                const tKual = parseFloat(row.dataset.targetKualitas) || 100;
                const tWaktu = parseFloat(row.dataset.targetWaktu) || 1;
                const tBiaya = parseFloat(row.dataset.targetBiaya) || 0;

                const rKuantInput = row.querySelector(`[name="realisasi[${id}][kuantitas]"]`);
                const rKualInput = row.querySelector(`[name="realisasi[${id}][kualitas]"]`);
                const rWaktuInput = row.querySelector(`[name="realisasi[${id}][waktu]"]`);
                const rBiayaInput = row.querySelector(`[name="realisasi[${id}][biaya]"]`);

                if (!rKuantInput || !rKualInput || !rWaktuInput || !rBiayaInput) return;

                const rKuant = parseFloat(rKuantInput.value) || 0;
                const rKual = parseFloat(rKualInput.value) || 0;
                const rWaktu = parseFloat(rWaktuInput.value) || 0;
                const rBiaya = parseFloat(rBiayaInput.value) || 0;

                // 1. Nilai Kuantitas & Kualitas
                let nKuant = (rKuant / tKuant) * 100;
                let nKual = (rKual / tKual) * 100;

                // 2. Nilai Waktu (Formula khusus efisiensi)
                let efWaktu = 100 - ((rWaktu / tWaktu) * 100);
                let nWaktu = (efWaktu <= 24)
                    ? ((1.76 * tWaktu - rWaktu) / tWaktu) * 100
                    : 76 - ((((1.76 * tWaktu - rWaktu) / tWaktu) * 100) - 100);

                // 3. Nilai Biaya (Jika ada target biaya)
                let nBiaya = 0;
                let pembagi = 3;
                if (tBiaya > 0) {
                    let efBiaya = 100 - ((rBiaya / tBiaya) * 100);
                    nBiaya = (efBiaya <= 24)
                        ? ((1.76 * tBiaya - rBiaya) / tBiaya) * 100
                        : 76 - ((((1.76 * tBiaya - rBiaya) / tBiaya) * 100) - 100);
                    pembagi = 4;
                }

                let totalPenghitungan = nKuant + nKual + nWaktu + nBiaya;
                let capaian = totalPenghitungan / pembagi;

                if (capaian > 92) capaian = 92;

                // Update UI
                const displayPenghitungan = row.querySelector('.display-penghitungan');
                const displayCapaian = row.querySelector('.display-capaian');

                if (displayPenghitungan) displayPenghitungan.innerText = totalPenghitungan.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (displayCapaian) displayCapaian.innerText = capaian.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // Update Alpine State
                if (window.skpAlpineInstance) {
                    window.skpAlpineInstance.updateMasterScore(id, capaian);
                }
            };

            // ✅ Fungsi Kalkulasi Real-time Tugas Tambahan
            window.recalculateTugasRow = function(id) {
                const row = document.getElementById(`row-tugas-item-${id}`);
                if (!row) return;

                const akRealInput = row.querySelector(`[name="realisasi_tugas[${id}][ak_realisasi]"]`);
                const realKegInput = row.querySelector(`[name="realisasi_tugas[${id}][realisasi_kegiatan]"]`);
                
                if (!akRealInput || !realKegInput) return;

                const akReal = parseFloat(akRealInput.value) || 0;
                const realKeg = realKegInput.value.trim();

                let valNum = 0;
                if (realKeg !== "") {
                    valNum = akReal;
                }

                const dispP = row.querySelector('.display-penghitungan-tugas');
                const dispC = row.querySelector('.display-capaian-tugas');
                if(dispP) dispP.innerText = valNum.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                if(dispC) dispC.innerText = valNum.toLocaleString('id-ID', { minimumFractionDigits: 2 });

                // Update Alpine State
                if (window.skpAlpineInstance) {
                    window.skpAlpineInstance.updateTugasScore(id, valNum);
                }
            };

            window.skpTendikData = function() {
                return {
                    openTugasModal: false,
                    addTaskModal: false,
                    tupoksiList: [],
                    isLengkap: {{ $p_isLengkap }},
                    isFinalized: {{ $isFinalized ? 'true' : 'false' }},
                    skp: {{ $p_skp }},
                    perilaku: @js($p_perilaku),
                    openAspect: null,
                    newRowCount: 0,
                    newTugasRows: [],
                    bidangList: @js($bidangList ?? []),
                    penilaiRoles: @js($penilaiRoles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]) ?? []),

                    // ✅ State untuk Realtime Calculation
                    scoresMaster: @js($skpSaya->mapWithKeys(fn($i) => [$i->id => $i->penilaian->nilai_capaian ?? 0])),
                    scoresTugas: @js($tugasTambahan->mapWithKeys(fn($i) => [$i->id => $i->nilai_capaian ?? 0])),

                    init() {
                        window.skpAlpineInstance = this;
                    },

                    updateMasterScore(id, val) {
                        this.scoresMaster[id] = val;
                        // Refresh proxy reference to ensure reactivity
                        this.scoresMaster = Object.assign({}, this.scoresMaster);
                    },

                    updateTugasScore(id, val) {
                        this.scoresTugas[id] = val;
                        // Refresh proxy reference to ensure reactivity
                        this.scoresTugas = Object.assign({}, this.scoresTugas);
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

                    get realtimePredikat() {
                        let val = parseFloat(this.realtimeSkp);
                        if (val > 90) return 'Sangat Baik';
                        if (val >= 76) return 'Baik';
                        if (val >= 61) return 'Cukup';
                        if (val >= 51) return 'Kurang';
                        return 'Sangat Kurang';
                    },

                    addNewTugasRow() {
                        this.newTugasRows.push({ jenis: '', kegiatan: '', ak: '1', ak_realisasi: '1', realisasi_kegiatan: '' });
                    },

                    removeNewTugasRow(index) {
                        this.newTugasRows.splice(index, 1);
                    },

                    fetchTupoksiByBidang(bidang) {
                        if(!bidang) { this.tupoksiList = []; return; }
                        fetch('/lookup/daftar-tupoksi/bidang/' + encodeURIComponent(bidang))
                            .then(res => res.json())
                            .then(data => { this.tupoksiList = data; });
                    },

                    fillTupoksiData(json, id) {
                        if(!json) return;
                        const data = JSON.parse(json);
                        const form = document.getElementById('form-new-' + id);
                        if(!form) return;

                        form.querySelector('[name=tupoksi]').value = data.tupoksi;
                        form.querySelector('[name=jenis_aktivitas]').value = data.jenis_aktivitas;
                        form.querySelector('[name=periode]').value = data.periode.toLowerCase();
                        form.querySelector('[name=frekuensi_per_tahun]').value = data.frekuensi_per_tahun;
                        form.querySelector('[name=durasi_menit]').value = data.durasi_menit;

                        const targetDesc = document.getElementById('table2-activity-' + id);
                        if(targetDesc) {
                            targetDesc.value = data.tupoksi || '';
                            autoResize(targetDesc);
                        }
                        recalcVolume(form.querySelector('[name=frekuensi_per_tahun]'));
                    },

                    createNewActivityRow() {
                        const tupoksiContainer = document.getElementById('tupoksi-container');
                        const skpContainer = document.getElementById('skp-container');
                        if(!tupoksiContainer || !skpContainer) return;

                        const idx = 'new-' + (this.newRowCount++);
                        const nextNo = tupoksiContainer.children.length + 1;

                        const trT = document.createElement('tr');
                        trT.className = 'hover:bg-slate-50 transition-colors border-b border-gray-200 approval-row';
                        trT.id = 'row-tupoksi-' + idx;

                        let bidangOptions = '<option value="">-- Pilih --</option>';
                        this.bidangList.forEach(b => bidangOptions += `<option value="${b}">${b}</option>`);

                        let penilaiOptions = '<option value="">-- Pilih --</option>';
                        this.penilaiRoles.forEach(p => {
                            penilaiOptions += `<option value="${p.name}">${p.name}</option>`;
                        });

                        trT.innerHTML = `
                            <td class="text-center font-bold text-slate-400 pt-3">
                                <div class="display-cell center text-[9px]">${nextNo}</div>
                            </td>
                            <td>
                                <select name="new_activities[${idx}][role_name]" form="main-skp-form" class="table-input font-bold text-blue-800" onchange="window.fetchTupoksiForNewRow(this, '${idx}')" required>
                                    ${bidangOptions}
                                </select>
                            </td>
                            <td>
                                <select name="tupoksi_select" class="table-input font-bold text-gray-800" onchange="window.fillTupoksiForNewRow(this, '${idx}')" required>
                                    <option value="">-- Pilih Tupoksi --</option>
                                </select>
                                <input type="hidden" name="new_activities[${idx}][tupoksi]" form="main-skp-form" value="">
                            </td>
                            <td>
                                <textarea name="new_activities[${idx}][jenis_aktivitas]" form="main-skp-form" class="table-input italic" rows="2"
                                    oninput="autoResize(this); syncToTable2(this, '${idx}')" required placeholder="Pilih tupoksi..."></textarea>
                            </td>
                            <td class="text-center align-middle">
                                <select name="new_activities[${idx}][penilai_role]" form="main-skp-form" class="table-input text-[8px] font-black uppercase text-center" required>
                                    ${penilaiOptions}
                                </select>
                            </td>
                            <td class="checkbox-cell border-r border-slate-300 text-center"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Utama" form="main-skp-form" checked class="w-3 h-3 text-blue-600 rounded"></td>
                            <td class="checkbox-cell border-r border-slate-300 text-center"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Periodik" form="main-skp-form" class="w-3 h-3 text-blue-600 rounded"></td>
                            <td class="checkbox-cell text-center"><input type="checkbox" name="new_activities[${idx}][kategori][]" value="Bukan Tupoksi" form="main-skp-form" class="w-3 h-3 text-blue-600 rounded"></td>

                            <td class="text-center">
                                <select name="new_activities[${idx}][periode]" form="main-skp-form" class="table-input text-center uppercase font-bold text-gray-500 text-xs" required>
                                    <option value="bulanan">BULANAN</option>
                                    <option value="harian">HARIAN</option>
                                    <option value="mingguan">MINGGUAN</option>
                                    <option value="triwulan">TRIWULAN</option>
                                    <option value="semester">SEMESTER</option>
                                    <option value="tahunan">TAHUNAN</option>
                                    <option value="kegiatan">KEGIATAN</option>
                                </select>
                            </td>
                            <td class="text-center"><input type="number" name="new_activities[${idx}][frekuensi_per_tahun]" form="main-skp-form" class="table-input text-center inp-frekuensi font-bold" value="1" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                            <td class="text-center"><input type="number" name="new_activities[${idx}][durasi_menit]" form="main-skp-form" class="table-input text-center inp-durasi font-bold" value="60" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                            <td class="text-center"><input type="number" name="new_activities[${idx}][jumlah_sdm]" form="main-skp-form" class="table-input text-center inp-sdm font-bold" value="1" oninput="recalcVolume(this)" min="0" onkeypress="preventMinus(event)" required></td>
                            <td class="bg-slate-50 text-right px-2 font-black text-indigo-700 text-[9px] pt-3 vol-display">60</td>

                            <td class="p-2 align-middle text-center">
                                <button type="button" onclick="document.getElementById('row-tupoksi-${idx}').remove(); document.getElementById('row-skp-${idx}').remove();" class="text-red-500 hover:text-red-700 transition active:scale-95" title="Batal">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </td>
                        `;
                        tupoksiContainer.appendChild(trT);

                        const trS = document.createElement('tr');
                        trS.className = 'hover:bg-amber-50/10 transition-colors border-b border-gray-300';
                        trS.id = 'row-skp-' + idx;

                        let extraCols = '';
                        if ({{ $isFormLocked ? 'true' : 'false' }}) {
                            extraCols = `
                                <td class="border-r border-gray-800 p-1 text-center bg-white">
                                    <input type="number" step="0.01" min="0" name="new_activities[${idx}][ak_realisasi]" form="main-skp-form"
                                        placeholder="0" ${ {{ $canInputRealisasi ? 'true' : 'false' }} ? '' : 'readonly' }
                                        class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-emerald-700 no-scroll">
                                </td>
                                <td class="border-r border-gray-800 p-1 text-center bg-gray-100 align-middle">
                                    <span class="text-[8px] font-bold text-gray-400 uppercase italic text-center block w-full">Finalisasi</span>
                                </td>
                                <td class="border-r border-gray-800 p-1 text-center bg-gray-100">
                                    <input type="text" disabled placeholder="-" class="w-full text-center text-[11px] border-none p-1 bg-transparent cursor-not-allowed">
                                </td>
                                <td class="border-r border-gray-800 p-1 text-center bg-gray-100">
                                    <input type="text" disabled placeholder="-" class="w-full text-center text-[11px] border-none p-1 bg-transparent cursor-not-allowed">
                                </td>
                                <td class="border-r border-gray-800 p-1 text-center bg-gray-100">
                                    <input type="text" disabled placeholder="-" class="w-full text-center text-[11px] border-none p-1 bg-transparent cursor-not-allowed">
                                </td>
                                <td class="border-r border-gray-800 p-2 text-center font-bold text-gray-700 bg-gray-50/50">-</td>
                                <td class="border-r border-gray-800 p-2 text-center font-black text-indigo-700 bg-gray-50">-</td>
                            `;
                        }

                        trS.innerHTML = `
                            <td class="border-r border-gray-300 p-2 text-center font-bold bg-gray-50/50">
                                <div class="display-cell center text-[9px]">${nextNo}</div>
                            </td>
                            <td class="p-0 border-r border-gray-800">
                                <textarea id="table2-activity-${idx}" readonly class="table-input font-bold text-gray-800 leading-tight bg-transparent" style="min-height: 40px;" oninput="autoResize(this)" placeholder="(Akan tersimpan saat finalisasi)"></textarea>
                            </td>
                            <td class="border-r border-gray-800 p-1 text-center bg-white">
                                <input type="number" step="0.01" min="0" name="new_activities[${idx}][target_ak]" form="main-skp-form"
                                    onkeydown="if(event.key==='-')event.preventDefault();"
                                    placeholder="" required
                                    class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-indigo-700 no-scroll">
                            </td>
                            <td class="border-r border-gray-800 p-1 text-center bg-white">
                                <input type="number" step="1" min="0" name="new_activities[${idx}][target_kuantitas]" form="main-skp-form"
                                    onkeydown="if(event.key==='-')event.preventDefault();"
                                    placeholder="" required
                                    class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll">
                            </td>
                            <td class="border-r border-gray-800 p-1 text-center bg-white">
                                <div class="flex items-center">
                                    <input type="number" step="1" min="0" max="100" name="new_activities[${idx}][target_kualitas]" form="main-skp-form"
                                        onkeydown="if(event.key==='-')event.preventDefault();"
                                        oninput="if(this.value > 100) this.value = 100;"
                                        placeholder="" required
                                        class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll">
                                    <span class="text-[10px] font-bold text-amber-700 pr-1">%</span>
                                </div>
                            </td>
                            <td class="border-r border-gray-800 p-1 text-center bg-white">
                                <div class="flex items-center">
                                    <input type="number" step="1" min="1" max="12" name="new_activities[${idx}][target_waktu]" form="main-skp-form"
                                        onkeydown="if(event.key==='-')event.preventDefault();"
                                        oninput="if(this.value > 12) this.value = 12; if(this.value !== '' && this.value < 1) this.value = 1;"
                                        placeholder="" required
                                        class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll">
                                    <span class="text-[9px] font-bold text-amber-700 pr-1">bln</span>
                                </div>
                            </td>
                            <td class="border-r border-gray-800 p-1 text-center bg-white">
                                <input type="number" step="1" min="0" name="new_activities[${idx}][target_biaya]" form="main-skp-form"
                                    onkeydown="if(event.key==='-')event.preventDefault();"
                                    placeholder=""
                                    class="w-full text-right text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll">
                            </td>
                            ${extraCols}
                        `;

                        const marker = document.getElementById('tugas-tambahan-marker');
                        if (marker) {
                            skpContainer.insertBefore(trS, marker);
                        } else {
                            skpContainer.appendChild(trS);
                        }
                    },

                    getPredicateNormal(val) {
                        let v = parseFloat(val);
                        if (isNaN(v) || v == 0) return '';
                        if (v <= 50) return '(Buruk)';
                        if (v <= 60) return '(Sedang)';
                        if (v <= 75) return '(Cukup)';
                        if (v <= 90.99) return '(Baik)';
                        return '(Sangat Baik)';
                    },

                    getPredicateFinal(val) {
                        if (!this.isLengkap) return '(SKP Belum Lengkap)';
                        let v = parseFloat(val);
                        if (isNaN(v) || v == 0) return '';
                        if (v <= 50) return '(Buruk)';
                        if (v <= 60) return '(Kurang)';
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

                    addIndikator(label) {
                        if (!this.perilaku[label]) this.perilaku[label] = [];
                        this.perilaku[label].push({ id: null, indikator: '', bobot_persen: 0, nilai: 0, is_from_admin: 0 });
                        
                        let items = this.perilaku[label];
                        let count = items.length;
                        let baseAvg = Math.floor(100 / count);
                        let remainder = 100 - (baseAvg * count);
                        items.forEach((item, idx) => {
                            item.bobot_persen = baseAvg + (idx < remainder ? 1 : 0);
                        });
                    },

                    removeIndikator(label, index) {
                        this.perilaku[label].splice(index, 1);
                        
                        let items = this.perilaku[label];
                        if (items.length > 0) {
                            let count = items.length;
                            let baseAvg = Math.floor(100 / count);
                            let remainder = 100 - (baseAvg * count);
                            items.forEach((item, idx) => {
                                item.bobot_persen = baseAvg + (idx < remainder ? 1 : 0);
                            });
                        }
                    },

                    recalculateBobot(label, changedIndex) {
                        let items = this.perilaku[label];
                        if (!items || items.length <= 1) return;
                        
                        let changedVal = parseInt(items[changedIndex].bobot_persen, 10) || 0;
                        if (changedVal > 100) changedVal = 100;
                        if (changedVal < 0) changedVal = 0;
                        items[changedIndex].bobot_persen = changedVal;
                        
                        let sum = items.reduce((s, item) => s + (parseInt(item.bobot_persen, 10) || 0), 0);
                        let diff = 100 - sum;
                        
                        if (diff === 0) return;
                        
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
                    },

                    get sumPerilaku() {
                        let sum = 0;
                        const labels = ['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'];
                        labels.forEach(label => {
                            sum += parseFloat(this.getAvgAspek(label)) || 0;
                        });
                        return sum;
                    },

                    get avgPerilaku() {
                        // Selalu bagi 6 sesuai jumlah indikator standar
                        return (this.sumPerilaku / 6);
                    },

                    get calcSkp60() {
                        return (parseFloat(this.realtimeSkp) * 0.6);
                    },

                    get calcPerilaku40() {
                        return (this.avgPerilaku * 0.4);
                    },

                    get finalScore() {
                        return (parseFloat(this.calcSkp60) + parseFloat(this.calcPerilaku40)).toFixed(2);
                    }
                }
            }
        </script>

        <div class="max-w-full mx-auto p-4 font-sans" x-data="skpTendikData()">

            {{-- 1. HEADER & FILTER PERIODE (PALING ATAS) --}}
            <div
                class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="bg-blue-600 p-3 rounded-xl shadow-lg shadow-blue-200">
                        <i class="fas fa-user-tie text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-gray-900">Capaian Kinerja Pegawai (Tendik)</h1>
                        <p class="text-sm text-gray-500 font-medium">Jabatan Sistem: <span
                                class="font-bold text-blue-600">{{ auth()->user()->getRoleNames()->first() ?? '-' }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                    <a href="{{ route('pegawai.skp-tendik.index') }}"
                        class="w-full md:w-auto text-center inline-flex items-center justify-center bg-gray-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-gray-700 transition-all shadow-lg text-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            <div class="space-y-3 mb-6">
                @if ($errors->any())
                    <div x-data="{ show: true }" x-show="show"
                        class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex justify-between items-center">
                        <div>
                            <p class="font-bold mb-1">Gagal Menyimpan Data:</p>
                            <ul class="list-disc ml-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button @click="show = false" class="text-red-500 hover:text-red-700 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                        class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold flex justify-between items-center"
                        role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative shadow-sm font-semibold flex justify-between items-center"
                        role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <button @click="show = false" class="text-red-500 hover:text-red-700 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
            </div>

            @if ($skpHeader)

                {{-- MONITORING PERSETUJUAN --}}
                <div class="mb-6 bg-white border border-gray-300 rounded-xl shadow-sm p-5">
                    <div class="mb-5 p-4 rounded-2xl border flex items-center justify-between {{ $statusBg }} border-gray-100 shadow-inner">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-white shadow-sm">
                                <i class="fas fa-info-circle {{ $statusColor }} text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] leading-none mb-1">Status Dokumen SKP</p>
                                <p class="text-sm font-black {{ $statusColor }} uppercase tracking-tight">{{ $statusDeskripsi }}</p>
                            </div>
                        </div>

                        @if ($skpHeader->catatan_penilai)
                            <div class="mt-2 p-3 bg-white rounded-lg border border-gray-200 text-sm shadow-sm">
                                <p class="font-bold text-gray-900 mb-1">Catatan dari Penilai:</p>
                                <p class="text-gray-600 italic">"{{ $skpHeader->catatan_penilai }}"</p>
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-tighter flex items-center gap-2">
                            <i class="fas fa-shield-alt text-blue-600"></i> Monitoring Persetujuan Target SKP
                        </h3>
                    </div>

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
                            
                            @if($isFinalized)
                                <p class="text-[9px] text-emerald-600 font-bold italic mt-1 uppercase">
                                    <i class="fas fa-check-double mr-1"></i> Target Difinalisasi
                                </p>
                            @else
                                <div class="mt-2">
                                    <button type="button" 
                                        onclick="if(confirm('Finalisasi Target SKP? Setelah difinalisasi, Anda tidak dapat mengubah rincian tupoksi hingga pimpinan melakukan review.')) { 
                                            document.getElementById('is_finalisasi').value = '1'; 
                                            document.getElementById('main-skp-form').submit(); 
                                        }"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-black py-2 rounded-lg shadow-lg shadow-blue-100 transition-all uppercase tracking-widest active:scale-95">
                                        <i class="fas fa-paper-plane mr-1"></i> Finalisasi Target
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Level 1: Atasan Pejabat Penilai --}}
                        <div class="p-3 rounded-lg border {{ $skpHeader->is_target_approved_atasan ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $skpHeader->is_target_approved_atasan ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $skpHeader->is_target_approved_atasan ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 1</span>
                            </div>
                            <p class="text-xs font-black {{ $skpHeader->is_target_approved_atasan ? 'text-emerald-700' : 'text-orange-700' }}">ATASAN PEJABAT PENILAI</p>
                            <p class="text-[9px] text-gray-500 font-bold truncate">
                                @if(!$isFinalized)
                                    Menunggu Finalisasi
                                @else
                                    {{ $skpHeader->is_target_approved_atasan ? 'Disetujui' : 'Menunggu Persetujuan' }}
                                @endif
                            </p>
                        </div>

                        {{-- Level 2: Pejabat Penilai --}}
                        <div class="p-3 rounded-lg border {{ $skpHeader->is_target_approved_penilai ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $skpHeader->is_target_approved_penilai ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $skpHeader->is_target_approved_penilai ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Level 2</span>
                            </div>
                            <p class="text-xs font-black {{ $skpHeader->is_target_approved_penilai ? 'text-emerald-700' : 'text-orange-700' }}">PEJABAT PENILAI</p>
                            <p class="text-[9px] text-gray-500 font-bold truncate">
                                @if(!$isFinalized)
                                    Menunggu Finalisasi
                                @else
                                    {{ $skpHeader->is_target_approved_penilai ? 'Disetujui' : 'Menunggu Persetujuan' }}
                                @endif
                            </p>
                        </div>

                        {{-- Level 3: Penilai Kegiatan --}}
                        @php
                            $totalActivities = $masterActivities->count();
                            $approvedActivities = $masterActivities->where('status_approval', 'approved')->count();
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
                                    {{ $approvedActivities }} / {{ $totalActivities }} Terverifikasi
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if(!$isFinalized)
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                        <div>
                            <h3 class="text-blue-800 font-bold text-sm uppercase tracking-tight">Langkah Penting: Finalisasi Target</h3>
                            <p class="text-[11px] text-blue-700">Silakan periksa kembali rincian tupoksi dan target SKP Anda. Klik tombol <b>"Finalisasi Target"</b> di atas agar Pimpinan dapat melakukan verifikasi dan persetujuan.</p>
                        </div>
                    </div>
                @endif

                {{-- 4. TABEL IDENTITAS FORMAL --}}
                @if ($skpHeader->pejabat_penilai_id)
                    <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden">
                        {{-- BARIS 1: PEGAWAI & PENILAI UTAMA --}}
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
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $user->profile->nama_lengkap ?? ($user->name ?? '-') }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $user->profile->nip ?? '-' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $user->profile->pangkat_golongan ?? '-' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $user->getRoleNames()->first() ?? '-' }}</div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $user->profile->unit_kerja ?? 'Fakultas Teknik' }}</div>
                                </div>
                            </div>

                            {{-- SISI KANAN: PEJABAT PENILAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black tracking-widest">II</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-emerald-800 min-w-0">
                                        <span class="truncate text-[10px] font-black tracking-tight">PEJABAT PENILAI</span>
                                    </div>
                                </div>

                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $penilai?->profile?->nama_lengkap ?? ($penilai?->name ?? '......................') }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $penilai?->profile?->nip ?? '......................' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $penilai?->profile?->pangkat_golongan ?? '......................' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $penilai ? $penilai->getRoleNames()->first() ?? 'Staf' : '......................' }}</div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $penilai?->profile?->unit_kerja ?? 'Fakultas Teknik' }}</div>
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
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $atasan?->profile?->nama_lengkap ?? ($atasan?->name ?? '......................') }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $atasan?->profile?->nip ?? '......................' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $atasan?->profile?->pangkat_golongan ?? '......................' }}</div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $atasan ? $atasan->getRoleNames()->first() ?? 'Staf' : '......................' }}</div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $atasan?->profile?->unit_kerja ?? 'Fakultas Teknik' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- FORM TUNGGAL UNTUK SELURUH CAPAIAN --}}
                <form id="main-skp-form" action="{{ route('pegawai.skp-tendik.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="header_id" value="{{ $skpHeader->id }}">
                    <input type="hidden" name="periode_id" value="{{ $periodeId }}">
                    <input type="hidden" name="is_finalisasi" id="is_finalisasi" value="0">

                    {{-- 3. TABEL MASTER TUPOKSI & OPERASIONAL --}}
                    @if ((isset($masterActivities) && $masterActivities->count() > 0) || !$isFinalized)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4 mt-8">
                            <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">📝 A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</h2>
                        </div>

                        <div class="flex justify-between items-end mb-2 ml-1">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                                <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Tupoksi & Operasional SKP</h3>
                            </div>
                            @if (!$isFinalized)
                                <button type="button" @click="createNewActivityRow()"
                                    class="px-4 py-1.5 bg-blue-600 text-white rounded text-[10px] font-black hover:bg-blue-700 transition shadow flex items-center gap-2 uppercase tracking-wider">
                                    <i class="fas fa-plus"></i> Tambah Tupoksi
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
                                            <th rowspan="2" class="px-3" style="width: 20%;">II. Kegiatan Tupoksi</th>
                                            <th rowspan="2" class="px-3" style="width: 22%;">III. Jenis Aktivitas Unit Kerja</th>
                                            <th rowspan="2" class="px-2" style="width: 8%;">Penilai</th>
                                            <th colspan="3" class="border-b border-slate-300 px-1" style="width: 9%;">Kategori</th>
                                            <th rowspan="2" class="px-2" style="width: 6%;">Periode</th>
                                            <th rowspan="2" class="px-1" style="width: 4%;">Freq</th>
                                            <th rowspan="2" class="px-1" style="width: 4%;">Dur</th>
                                            <th rowspan="2" class="px-1" style="width: 3%;">SDM</th>
                                            <th rowspan="2" class="px-3" style="width: 8%;">Vol Kerja</th>
                                            <th rowspan="2" class="px-3" style="width: 8%;">Status</th>
                                            @if (!$isFinalized)
                                                <th rowspan="2" class="px-2" style="width: 4%;">Aksi</th>
                                            @endif
                                        </tr>
                                        <tr class="bg-gray-100 text-gray-700">
                                            <th style="width: 3%;" class="text-[8px] px-0">Utama</th>
                                            <th style="width: 3%;" class="text-[8px] px-0">Prd</th>
                                            <th style="width: 3%;" class="text-[8px] px-0">Bkn</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tupoksi-container">
                                        @foreach ($masterActivities as $index => $item)
                                            <tr class="hover:bg-slate-50 transition-colors border-b border-gray-200">
                                                <td class="text-center font-bold text-slate-400 pt-3">
                                                    <div class="display-cell center text-[9px]">{{ $index + 1 }}</div>
                                                </td>
                                                <td>
                                                    <div class="display-cell font-semibold text-blue-800 uppercase">
                                                        {{ $item->role_name }}</div>
                                                </td>
                                                <td>
                                                    <div class="display-cell font-bold text-gray-800">{{ $item->tupoksi }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="display-cell text-gray-700 leading-snug">
                                                        {{ $item->jenis_aktivitas }}</div>
                                                </td>
                                                <td>
                                                    <div class="display-cell center font-semibold text-emerald-700">
                                                        {{ $item->penilai_role ?? '-' }}</div>
                                                </td>
                                                <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black">
                                                    {{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? '✓' : '' }}
                                                </td>
                                                <td class="checkbox-cell border-r border-slate-300 text-emerald-600 font-black">
                                                    {{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? '✓' : '' }}
                                                </td>
                                                <td class="checkbox-cell text-emerald-600 font-black">
                                                    {{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? '✓' : '' }}
                                                </td>
                                                <td class="text-center uppercase font-bold text-gray-500 text-xs">
                                                    {{ $item->periode }}</td>
                                                <td class="text-center font-bold">
                                                    {{ $item->frekuensi_per_tahun }}</td>
                                                <td class="text-center font-bold">
                                                    {{ $item->durasi_menit }}</td>
                                                <td class="text-center font-bold">
                                                    {{ $item->jumlah_sdm }}</td>
                                                <td class="bg-slate-50">
                                                    <div class="display-cell right font-black text-indigo-700">
                                                        {{ number_format($item->volume_kerja ?? $item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm, 0, ',', '.') }}
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle">
                                                    @if ($item->status_approval == 'approved')
                                                        <span class="bg-emerald-100 text-emerald-700 text-[8px] px-1.5 py-0.5 rounded font-bold uppercase whitespace-nowrap">Disetujui</span>
                                                    @elseif ($item->status_approval == 'rejected')
                                                        <span class="bg-red-100 text-red-700 text-[8px] px-1.5 py-0.5 rounded font-bold uppercase whitespace-nowrap" title="{{ $item->komentar ?? 'Ditolak' }}">Ditolak</span>
                                                    @else
                                                        <span class="bg-amber-100 text-amber-700 text-[8px] px-1.5 py-0.5 rounded font-bold uppercase whitespace-nowrap">Pending</span>
                                                    @endif
                                                </td>
                                                @if (!$isFinalized)
                                                    <td class="p-2 align-middle text-center border-l border-gray-200">
                                                        <button type="button"
                                                            onclick="if(confirm('Verifikasi 1: Apakah Anda yakin ingin menghapus kegiatan ini dari rencana SKP?')) { if(confirm('Verifikasi 2: Tindakan ini tidak dapat dibatalkan. Lanjutkan hapus permanen?')) { document.getElementById('form-delete-activity-{{ $item->id }}').submit(); } }"
                                                            class="bg-red-50 text-red-600 px-2 py-1.5 rounded flex items-center justify-center mx-auto hover:bg-red-100 transition shadow-sm border border-red-100"
                                                            title="Hapus">
                                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>


                    @endif

                    {{-- 4. TABEL UTAMA SKP --}}
                    @if ((isset($skpSaya) && count($skpSaya) > 0) || ($skpHeader->status == 'Draft' || $skpHeader->status == 'Target Diajukan'))
                        <div class="mb-2 ml-1 flex items-center gap-2">
                            <div class="w-1.5 h-4 bg-emerald-600 rounded-full"></div>
                            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Target dan
                                Realisasi SKP</h3>
                        </div>
                        <div class="bg-white overflow-hidden mb-8 border border-gray-300 shadow-md w-full rounded-xl">
                            <div class="overflow-x-auto w-full custom-scrollbar">
                                <table class="w-full min-w-[1000px] border-collapse text-[11px] border-none">
                                    <thead
                                        class="text-gray-900 font-bold text-center align-middle bg-gray-100 border-b border-gray-300">
                                        <tr>
                                            <th rowspan="2" class="border-r border-gray-300 p-2 w-8">NO</th>
                                            <th rowspan="2" class="border-r border-gray-300 p-2 min-w-[200px] uppercase">
                                                III. Kegiatan Tugas Jabatan</th>
                                            <th rowspan="2" class="border-r border-gray-300 p-2 w-12">AK</th>
                                            <th colspan="4" class="border-r border-b border-gray-300 p-2 uppercase">
                                                TARGET</th>

                                            @if($isFormLocked)
                                                <th rowspan="2" class="border-r border-gray-300 p-2 w-12">AK(Realisasi)</th>
                                                <th colspan="4"
                                                    class="border-r border-b border-gray-300 p-2 uppercase text-emerald-800">
                                                    REALISASI</th>
                                                <th rowspan="2" class="border-r border-gray-300 p-2 w-16">PENGHITUNGAN
                                                </th>
                                                <th rowspan="2" class="border-r border-gray-300 p-2 w-16">NILAI CAPAIAN
                                                </th>
                                            @else
                                                <th rowspan="2" class="border-r border-gray-300 p-2 w-16">AKSI</th>
                                            @endif
                                        </tr>
                                        <tr>
                                            <th class="border-r border-gray-300 p-1 w-16 uppercase text-[9px]">Kuant</th>
                                            <th class="border-r border-gray-300 p-1 w-12 uppercase text-[9px]">Kual</th>
                                            <th class="border-r border-gray-300 p-1 w-12 uppercase text-[9px]">Waktu</th>
                                            <th class="border-r border-gray-300 p-1 w-20 uppercase text-[9px]">Biaya</th>

                                            @if($isFormLocked)
                                                <th
                                                    class="border-r border-gray-300 p-1 w-48 uppercase text-[9px] text-emerald-800">
                                                    Kuantitas (Bukti)</th>
                                                <th
                                                    class="border-r border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800">
                                                    Kualitas</th>
                                                <th
                                                    class="border-r border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800">
                                                    Waktu</th>
                                                <th
                                                    class="border-r border-gray-300 p-1 w-24 uppercase text-[9px] text-emerald-800">
                                                    Biaya</th>
                                            @endif
                                        </tr>
                                    </thead>

                                    <tbody class="text-gray-800" id="skp-container">
                                        @foreach ($skpSaya as $induk)
                                            @php
                                                $nilai = $induk->penilaian;
                                                $totalAK = $induk->details->sum('ak');
                                                $totalKuantitas = $induk->details->sum('kuantitas_output');
                                                $avgKualitas = $induk->details->avg('kualitas_mutu') ?? 100;
                                                $maxWaktu = $induk->details->max('waktu') ?? 0;
                                                $totalBiaya = $induk->details->sum('biaya');
                                            @endphp

                                            <tr class="hover:bg-blue-50 transition-colors border-b border-gray-300 row-realisasi-master" 
                                                id="row-realisasi-{{ $induk->id }}"
                                                data-target-kuantitas="{{ $totalKuantitas }}"
                                                data-target-kualitas="{{ $avgKualitas }}"
                                                data-target-waktu="{{ $maxWaktu }}"
                                                data-target-biaya="{{ $totalBiaya }}">
                                                <td class="border-r border-gray-300 p-2 text-center font-bold bg-gray-50/50">
                                                    <div class="display-cell center text-[9px]">{{ $loop->iteration }}</div>
                                                </td>
                                                <td class="border-r border-gray-300 p-2 font-bold text-gray-900">
                                                    {!! nl2br(e($induk->tupoksi)) !!}</td>
                                                <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                    <input type="number" step="0.01" min="0"
                                                        name="realisasi[{{ $induk->id }}][target_ak]"
                                                        onkeydown="if(event.key==='-')event.preventDefault();"
                                                        value="{{ $totalAK != 0 ? number_format($totalAK, 2, '.', '') : '' }}"
                                                        class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-indigo-700 no-scroll"
                                                        {{ $isFormLocked ? 'readonly' : '' }}>
                                                </td>
                                                <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                    <input type="number" step="1" min="0"
                                                        name="realisasi[{{ $induk->id }}][target_kuantitas]"
                                                        onkeydown="if(event.key==='-')event.preventDefault();"
                                                        value="{{ $totalKuantitas != 0 ? $totalKuantitas : '' }}"
                                                        class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll"
                                                        {{ $isFormLocked ? 'readonly' : '' }}>
                                                </td>
                                                <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                    <div class="flex items-center">
                                                        <input type="number" step="1" min="0" max="100"
                                                            name="realisasi[{{ $induk->id }}][target_kualitas]"
                                                            onkeydown="if(event.key==='-')event.preventDefault();"
                                                            oninput="if(this.value > 100) this.value = 100;"
                                                            value="{{ $avgKualitas != 0 ? round($avgKualitas) : '' }}"
                                                            class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll"
                                                            {{ $isFormLocked ? 'readonly' : '' }}>
                                                        <span class="text-[10px] font-bold text-amber-700 pr-1">%</span>
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                    <div class="flex items-center">
                                                        <input type="number" step="1" min="1" max="12"
                                                            name="realisasi[{{ $induk->id }}][target_waktu]"
                                                            onkeydown="if(event.key==='-')event.preventDefault();"
                                                            oninput="if(this.value > 12) this.value = 12; if(this.value !== '' && this.value < 1) this.value = 1;"
                                                            value="{{ $maxWaktu != 0 ? $maxWaktu : '' }}"
                                                            class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll"
                                                            {{ $isFormLocked ? 'readonly' : 'required' }}>
                                                        <span class="text-[9px] font-bold text-amber-700 pr-1">bln</span>
                                                    </div>
                                                </td>
                                                <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                    <input type="number" step="1" min="0"
                                                        name="realisasi[{{ $induk->id }}][target_biaya]"
                                                        onkeydown="if(event.key==='-')event.preventDefault();"
                                                        value="{{ $totalBiaya != 0 ? $totalBiaya : '' }}"
                                                        class="w-full text-right text-[11px] border-none p-1 focus:ring-0 font-bold text-amber-700 no-scroll"
                                                        {{ $isFormLocked ? 'readonly' : '' }}>
                                                </td>

                                                {{-- KOLOM REALISASI (OTOMATIS / READONLY DI MENU INI) --}}
                                                @if($isFormLocked)
                                                    <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                        <input type="number" step="0.01" min="0" name="realisasi[{{ $induk->id }}][ak_realisasi]"
                                                            value="{{ isset($nilai->realisasi_ak) && $nilai->realisasi_ak != 0 ? number_format($nilai->realisasi_ak, 2, '.', '') : '' }}"
                                                            placeholder="0"
                                                            oninput="recalculateLiveRow({{ $induk->id }})"
                                                            class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-emerald-700 no-scroll bg-white"
                                                            {{ $canInputRealisasi ? '' : 'readonly' }}>
                                                    </td>
                                                    <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                        <div class="flex flex-col gap-1 px-1">
                                                            <input type="number" step="1" min="0"
                                                                name="realisasi[{{ $induk->id }}][kuantitas]"
                                                                onkeydown="if(event.key==='-')event.preventDefault();"
                                                                oninput="recalculateLiveRow({{ $induk->id }})"
                                                                value="{{ (isset($nilai->realisasi_kuantitas) && $nilai->realisasi_kuantitas != 0) ? $nilai->realisasi_kuantitas : '' }}"
                                                                placeholder="0"
                                                                class="w-full text-center text-[11px] border-none p-0 focus:ring-0 font-bold text-emerald-700 no-scroll bg-white"
                                                                {{ $canInputRealisasi ? '' : 'readonly' }}>

                                                            @if ($nilai && $nilai->bukti && is_array($nilai->bukti))
                                                                <div class="flex flex-wrap gap-1 justify-center">
                                                                    @foreach($nilai->bukti as $bIndex => $path)
                                                                        <a href="{{ route('pegawai.skp-tendik.download-bukti', ['path' => $path]) }}" target="_blank"
                                                                            class="inline-flex items-center gap-1 px-1 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors font-bold text-[8px] whitespace-nowrap"
                                                                            title="Lihat Bukti {{ $bIndex + 1 }}">
                                                                            <i class="fas fa-file-pdf"></i> {{ $bIndex + 1 }}
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            @if ($canInputRealisasi)
                                                                <div x-data="{ fileCount: 0 }" class="mt-1 w-full">
                                                                    <input type="file" id="file-bukti-{{ $induk->id }}" name="realisasi[{{ $induk->id }}][bukti][]" multiple class="hidden" @change="fileCount = $event.target.files.length">
                                                                    <label for="file-bukti-{{ $induk->id }}" class="cursor-pointer flex items-center justify-center w-full px-1 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[8px] font-semibold hover:bg-emerald-100 transition-colors">
                                                                        <i class="fas fa-upload mr-1"></i>
                                                                        <span x-text="fileCount > 0 ? fileCount + ' file dipilih' : 'Pilih File Bukti'"></span>
                                                                    </label>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                        <div class="flex items-center">
                                                            <input type="number" step="1" min="0" max="100"
                                                                name="realisasi[{{ $induk->id }}][kualitas]"
                                                                onkeydown="if(event.key==='-')event.preventDefault();"
                                                                oninput="if(this.value > 100) this.value = 100; recalculateLiveRow({{ $induk->id }})"
                                                                value="{{ (isset($nilai->realisasi_kualitas) && $nilai->realisasi_kualitas != 0) ? round($nilai->realisasi_kualitas) : '' }}"
                                                                placeholder="0"
                                                                class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-emerald-700 no-scroll bg-white"
                                                                {{ $canInputRealisasi ? '' : 'readonly' }}>
                                                            <span class="text-[10px] font-bold text-emerald-700 pr-1">%</span>
                                                        </div>
                                                    </td>
                                                    <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                        <div class="flex items-center">
                                                            <input type="number" step="1" min="1" max="12"
                                                                name="realisasi[{{ $induk->id }}][waktu]"
                                                                onkeydown="if(event.key==='-')event.preventDefault();"
                                                                oninput="if(this.value > 12) this.value = 12; if(this.value !== '' && this.value < 1) this.value = 1; recalculateLiveRow({{ $induk->id }})"
                                                                value="{{ (isset($nilai->realisasi_waktu) && $nilai->realisasi_waktu != 0) ? $nilai->realisasi_waktu : '' }}"
                                                                placeholder="0"
                                                                class="w-full text-center text-[11px] border-none p-1 focus:ring-0 font-bold text-emerald-700 no-scroll bg-white"
                                                                {{ $canInputRealisasi ? '' : 'readonly' }}>
                                                            <span class="text-[9px] font-bold text-emerald-700 pr-1">bln</span>
                                                        </div>
                                                    </td>
                                                    <td class="border-r border-gray-300 p-1 text-center bg-white">
                                                        <input type="number" step="0.01" min="0"
                                                            name="realisasi[{{ $induk->id }}][biaya]"
                                                            onkeydown="if(event.key==='-')event.preventDefault();"
                                                            oninput="recalculateLiveRow({{ $induk->id }})"
                                                            value="{{ isset($nilai->realisasi_biaya) && $nilai->realisasi_biaya != 0 ? number_format($nilai->realisasi_biaya, 2, '.', '') : '' }}"
                                                            placeholder="0"
                                                            class="w-full text-right text-[11px] border-none p-1 focus:ring-0 font-bold text-emerald-700 no-scroll bg-white"
                                                            {{ $canInputRealisasi ? '' : 'readonly' }}>
                                                    </td>

                                                    <td class="border-r border-gray-300 p-2 text-center font-bold text-gray-700 bg-gray-50/50 display-penghitungan">
                                                        {{ isset($nilai->nilai_penghitungan) ? number_format($nilai->nilai_penghitungan, 2, ',', '.') : '-' }}
                                                    </td>
                                                    <td class="p-2 text-center font-black text-indigo-700 bg-gray-50 display-capaian">
                                                        {{ isset($nilai->nilai_capaian) ? number_format($nilai->nilai_capaian, 2, ',', '.') : '-' }}
                                                    </td>
                                                @else
                                                    {{-- Empty cell for 'AKSI' column in Draft mode --}}
                                                    <td class="border-r border-gray-300 p-2 bg-gray-50"></td>
                                                @endif
                                            </tr>
                                        @endforeach

                                        <tr id="tugas-tambahan-marker" style="display:none;"></tr>

                                        {{-- HEADER TUGAS TAMBAHAN --}}
                                        <tr class="font-bold bg-gray-100 border-b border-gray-800">
                                            <td class="border-r border-gray-300 p-2 text-center font-bold text-gray-800">IV</td>
                                            <td colspan="{{ $isFormLocked ? '13' : '7' }}" class="p-2">
                                                <div class="flex justify-between items-center w-full">
                                                    <span class="font-black text-gray-900 uppercase">Tugas tambahan dan kreativitas</span>
                                                    <div class="flex items-center gap-2">
                                                        @if (isset($tugasTambahan) && $tugasTambahan->count() >= 7)
                                                            <span class="text-[10px] font-bold text-red-600 bg-red-100 px-2 py-1 rounded">Maks 7 Kegiatan</span>
                                                        @endif
                                                        @if (!$isFinalized || $canInputRealisasi)
                                                            <button type="button" @click="addNewTugasRow()"
                                                                class="bg-blue-600 text-white px-3 py-1 rounded text-[10px] font-bold hover:bg-blue-700 transition shadow-sm">
                                                                <i class="fas fa-plus mr-1"></i> Tambah Tugas Tambahan
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- ROW INPUT TUGAS TAMBAHAN (DYNAMIC) DENGAN KESELARASAN 7 KOLOM --}}
                                        @if (!$isFinalized || $canInputRealisasi)
                                            <template x-for="(row, index) in newTugasRows" :key="index">
                                                <tr class="bg-blue-50/10 border-b border-gray-300">
                                                    <td class="border-r border-gray-300 p-2 text-center align-middle font-bold text-gray-400 italic" x-text="index + 1"></td>
                                                    <td class="border-r border-gray-300 p-0 align-middle">
                                                        <input type="text" :name="`new_tugas[${index}][jenis]`" x-model="row.jenis"
                                                            class="table-input font-bold" required placeholder="Jenis (misal: Tugas Tambahan)">
                                                    </td>
                                                    <td class="border-r border-gray-300 p-0 align-middle text-center">
                                                        <input type="number" step="1" :name="`new_tugas[${index}][ak]`" x-model="row.ak"
                                                            class="table-input text-center font-bold text-indigo-700" placeholder="0" required>
                                                    </td>
                                                    <td colspan="4" class="border-r border-gray-300 p-0 align-middle">
                                                        <textarea :name="`new_tugas[${index}][kegiatan]`" x-model="row.kegiatan"
                                                            class="table-input" placeholder="Detail Kegiatan Tambahan" rows="2"
                                                            required oninput="autoResize(this)"></textarea>
                                                    </td>

                                                    @if($isFormLocked)
                                                        <td class="border-r border-gray-300 p-0 align-middle text-center">
                                                            <input type="number" step="1" :name="`new_tugas[${index}][ak_realisasi]`" x-model="row.ak_realisasi"
                                                                class="table-input text-center font-bold" placeholder="0">
                                                        </td>
                                                        <td colspan="4" class="border-r border-gray-300 p-0 align-top">
                                                            <div class="flex flex-col">
                                                                <textarea :name="`new_tugas[${index}][realisasi_kegiatan]`" x-model="row.realisasi_kegiatan"
                                                                    class="table-input" placeholder="" rows="2"
                                                                    oninput="autoResize(this)"></textarea>
                                                                <div class="px-1 pb-1">
                                                                    <input type="file" :name="`new_tugas[${index}][file_bukti]`"
                                                                        class="w-full text-[8px] file:mr-1 file:py-0.5 file:px-1 file:rounded file:border-0 file:text-[8px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="border-r border-gray-300 p-1 align-middle text-center bg-gray-50/50">
                                                            <input type="text" disabled placeholder="-" class="w-full text-center text-[10px] border-none bg-transparent cursor-not-allowed">
                                                        </td>
                                                    @endif

                                                    <td class="border-r border-gray-300 p-2 align-middle text-center">
                                                        <button type="button" @click="removeNewTugasRow(index)"
                                                            class="text-red-500 hover:text-red-700 transition active:scale-95" title="Hapus">
                                                            <i class="fas fa-trash-alt text-sm"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        @endif
                                        @if (isset($tugasTambahan) && $tugasTambahan->count() > 0)
                                            @foreach ($tugasTambahan as $tugas)
                                                <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300" id="row-tugas-item-{{ $tugas->id }}">
                                                    <td class="border-r border-gray-300 p-2 text-center align-top">
                                                        <div class="display-cell center text-[9px] font-bold text-gray-400">
                                                            {{ $loop->iteration }}</div>
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

                                                    @if($isFormLocked)
                                                        <td class="border-r border-gray-300 p-0 align-top text-emerald-700">
                                                            <input type="number" step="1" name="realisasi_tugas[{{ $tugas->id }}][ak_realisasi]"
                                                                value="{{ (isset($tugas->ak_realisasi) && $tugas->ak_realisasi != 0) ? round($tugas->ak_realisasi) : 1 }}"
                                                                placeholder="0"
                                                                oninput="recalculateTugasRow({{ $tugas->id }})"
                                                                class="table-input text-center font-bold text-emerald-700"
                                                                {{ $canInputRealisasi ? '' : 'readonly' }}>
                                                        </td>
                                                        <td colspan="4" class="border-r border-gray-300 p-0 align-top whitespace-normal">
                                                            <div class="flex flex-col">
                                                                <textarea name="realisasi_tugas[{{ $tugas->id }}][realisasi_kegiatan]"
                                                                    class="table-input"
                                                                    placeholder="" rows="2" {{ $canInputRealisasi ? '' : 'readonly' }} 
                                                                    oninput="autoResize(this); recalculateTugasRow({{ $tugas->id }})">{{ $tugas->realisasi_kegiatan }}</textarea>

                                                                @if ($tugas->realisasi_bukti)
                                                                    <div class="px-1 pb-1">
                                                                        <a href="{{ route('pegawai.skp-tendik.download-bukti', ['path' => $tugas->realisasi_bukti]) }}" target="_blank"
                                                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors font-bold text-[8px]">
                                                                            <i class="fas fa-file-pdf"></i> Lihat Bukti
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                                @if ($canInputRealisasi)
                                                                    <div class="px-1 pb-1">
                                                                        <input type="file" name="realisasi_tugas[{{ $tugas->id }}][file_bukti]" class="w-full text-[8px] file:mr-1 file:py-0.5 file:px-1 file:rounded file:border-0 file:text-[8px] file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="border-r border-gray-300 p-2 text-center font-bold align-middle bg-gray-50/50 display-penghitungan-tugas">
                                                            {{ isset($tugas->penghitungan) ? number_format($tugas->penghitungan, 2, ',', '.') : '0,00' }}
                                                        </td>
                                                    @endif

                                                    <td class="p-2 text-center bg-gray-50 align-middle">
                                                        @if($isFormLocked)
                                                            <div class="font-black text-indigo-700 mb-1 display-capaian-tugas">
                                                                {{ isset($tugas->nilai_capaian) ? number_format($tugas->nilai_capaian, 2, ',', '.') : '0,00' }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr class="border-b border-gray-300 bg-gray-50">
                                                <td class="border-r border-gray-300 p-2 text-center">-</td>
                                                <td colspan="{{ $isFormLocked ? '13' : '7' }}" class="p-4 text-center text-gray-400 italic font-semibold">
                                                    Belum ada tugas tambahan yang diinputkan.</td>
                                            </tr>
                                        @endif
                                    </tbody>

                                    <tfoot>
                                        <tr class="bg-gray-50 font-black">
                                            <td colspan="{{ $isFormLocked ? '13' : '7' }}" class="border-r border-gray-300 p-4 text-right pr-6 text-base bg-blue-50 text-blue-900 uppercase">
                                                NILAI CAPAIAN SKP TENDIK
                                            </td>
                                            <td class="p-3 text-center text-xl text-indigo-800 bg-blue-100 shadow-inner">
                                                <span x-text="realtimeSkp.toString().replace('.', ',')"></span>
                                                <div class="text-[10px] font-bold text-blue-600 mt-1 uppercase tracking-widest" x-text="realtimePredikat">
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- 6. TABEL EVALUASI PERILAKU KERJA --}}
                    @if (!$p_isLengkap)
                        <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-xl shadow-sm flex items-start gap-4">
                            <i class="fas fa-exclamation-triangle text-orange-500 mt-1 text-lg"></i>
                            <div>
                                <h3 class="text-orange-900 font-bold">Target SKP Belum Sepenuhnya Terealisasi!</h3>
                                <p class="text-sm text-orange-800">Anda dapat mengisi form Perilaku Kerja, namun <b>Nilai Akhir (Nilai Prestasi Kerja) tidak akan dihitung</b> sebelum Anda melengkapi seluruh realisasi kegiatan di menu SKP.</p>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white mb-8 w-full overflow-x-auto pb-4 pt-1">
                        <div class="border border-black rounded-xl overflow-hidden min-w-full inline-block align-middle shadow-sm">
                            <table class="w-full text-sm table-skp-custom bg-white border-none" style="min-width: 800px;">
                                <thead>
                                    <tr>
                                        <th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white">UNSUR YANG DINILAI</th>
                                        <th class="text-center font-bold bg-white w-32">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- A. SKP (60%) --}}
                                    <tr class="border-b border-gray-300">
                                        <td :rowspan="6 + 4" class="text-center font-bold align-middle w-12 bg-white text-base border-r border-gray-300">4.</td>
                                        <td colspan="2" class="font-bold bg-white italic border-r border-gray-800">a. Sasaran Kerja Pegawai (SKP)</td>
                                        <td class="text-center bg-white w-32 border-r border-gray-800 font-bold" x-text="realtimeSkp.toString().replace('.', ',')"></td>
                                        <td class="text-center bg-white w-16 border-r border-gray-800">x</td>
                                        <td class="text-center font-bold bg-white w-20 border-r border-gray-800">60%</td>
                                        <td class="text-center font-bold bg-white text-blue-700">
                                            <span x-text="(realtimeSkp * 0.6).toFixed(2).toString().replace('.', ',')"></span>
                                        </td>
                                    </tr>

                                    {{-- B. PERILAKU KERJA (40%) --}}
                                    @foreach (['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'] as $index => $label)
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
                                                            @if (!$isFinalized || $canInputRealisasi)
                                                                <button type="button" @click.stop="addIndikator('{{ $label }}')"
                                                                    class="bg-emerald-600 text-white text-[9px] px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-700 shadow-sm uppercase tracking-widest transition">
                                                                    <i class="fas fa-plus mr-1"></i> Tambah Indikator
                                                                </button>
                                                            @endif
                                                        </div>
                                                        <div class="space-y-3">
                                                            <template x-for="(item, i) in perilaku['{{ $label }}']" :key="i">
                                                                <div class="bg-white p-3 border border-emerald-200 rounded-xl shadow-sm relative group">
                                                                    <div class="flex items-start gap-4 w-full">
                                                                        <div class="flex-1 min-w-0">
                                                                            <label class="block text-[8px] font-bold text-gray-400 uppercase mb-1">Kegiatan / Bukti Pendukung</label>
                                                                            <textarea x-model="item.indikator" :name="`perilaku[${'{{ $label }}'}][${i}][indikator]`"
                                                                                class="w-full text-[10px] border-gray-300 rounded focus:ring-emerald-500 resize-y p-1.5 bg-gray-50/50" rows="2"
                                                                                :readonly="{{ $isFormLocked ? 'true' : 'false' }} && (!!item.id || !{{ $canInputRealisasi ? 'true' : 'false' }})" placeholder="Tuliskan indikator perilaku..." required></textarea>
                                                                        </div>
                                                                        <div class="w-20 shrink-0">
                                                                            <label class="block text-[8px] font-bold text-blue-500 uppercase mb-1 text-center">Persentase</label>
                                                                            <div class="relative">
                                                                                <input type="number" x-model.number="item.bobot_persen" :name="`perilaku[${'{{ $label }}'}][${i}][bobot_persen]`"
                                                                                    step="1" min="0" max="100"
                                                                                    class="w-full text-center px-1 py-1 pr-4 text-sm font-black text-blue-700 border-blue-300 rounded focus:ring-blue-500 no-scroll bg-white"
                                                                                    @change="recalculateBobot('{{ $label }}', i)"
                                                                                    {{ $isFormLocked ? 'readonly' : '' }}>
                                                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-blue-500">%</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="w-20 shrink-0">
                                                                            <label class="block text-[8px] font-bold text-emerald-500 uppercase mb-1 text-center">Nilai</label>
                                                                            <input type="number" x-model.number="item.nilai" :name="`perilaku[${'{{ $label }}'}][${i}][nilai]`"
                                                                                step="0.01" min="0" max="100"
                                                                                @input="if(item.nilai > 100) item.nilai = 100"
                                                                                class="w-full text-center px-1 py-1 text-sm font-black text-emerald-700 border-emerald-300 rounded focus:ring-emerald-500 no-scroll bg-white"
                                                                                {{ $canInputRealisasi ? '' : 'disabled' }}>
                                                                        </div>
                                                                        @if (!$isFinalized || $canInputRealisasi)
                                                                            <div class="shrink-0 pt-4">
                                                                                <button type="button" @click.stop="removeIndikator('{{ $label }}', i)"
                                                                                    class="text-red-500 hover:text-red-700 transition transform hover:scale-110"
                                                                                    title="Hapus">
                                                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                                                </button>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][id]`" x-model="item.id">
                                                                    <input type="hidden" :name="`perilaku[${'{{ $label }}'}][${i}][is_from_admin]`" x-model="item.is_from_admin">
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle bg-white border-r border-gray-800">
                                                <span class="font-bold text-lg text-emerald-800" x-text="parseFloat(getAvgAspek('{{ $label }}')).toFixed(2).replace('.', ',')"></span>
                                            </td>

                                            <td colspan="2" class="text-center align-middle bg-white text-emerald-700 font-bold text-[10px] uppercase tracking-tighter border-r border-gray-800" x-text="getPredicateNormal(getAvgAspek('{{ $label }}'))"></td>
                                            <td class="bg-grey-custom"></td>
                                        </tr>
                                    @endforeach

                                    {{-- FOOTER 7. JUMLAH --}}
                                    <tr class="border-b border-gray-300 font-bold">
                                        <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">7. Jumlah</td>
                                        <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800" x-text="sumPerilaku.toFixed(2).replace('.', ',')"></td>
                                        <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>

                                    {{-- FOOTER 8. NILAI RATA-RATA --}}
                                    <tr class="border-b border-gray-300 font-bold">
                                        <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">8. Nilai rata &ndash; rata</td>
                                        <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800" x-text="avgPerilaku.toFixed(2).replace('.', ',')"></td>
                                        <td colspan="2" class="text-center align-middle bg-white border-r border-gray-800">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>

                                    {{-- FOOTER 9. NILAI PERILAKU KERJA --}}
                                    <tr class="border-b border-gray-300 font-bold">
                                        <td class="bg-white p-3 uppercase text-[11px] border-r border-gray-800">9. Nilai Perilaku Kerja</td>
                                        <td class="text-center align-middle bg-white border-r border-gray-800 text-lg text-emerald-800" x-text="avgPerilaku.toFixed(2).replace('.', ',')"></td>
                                        <td class="text-center align-middle bg-white border-r border-gray-800 font-bold italic">x</td>
                                        <td class="text-center font-bold align-middle bg-white w-20 border-r border-gray-800">40%</td>
                                        <td class="text-center font-bold align-middle bg-white text-emerald-800 text-lg" x-text="calcPerilaku40.toFixed(2).replace('.', ',')"></td>
                                    </tr>

                                    {{-- KESIMPULAN NILAI PRESTASI KERJA --}}
                                    <tr class="border-t-[3px] border-double border-gray-800">
                                        <td colspan="6" class="text-center font-bold text-lg p-3 bg-white tracking-widest uppercase">NILAI PRESTASI KERJA</td>
                                        <td class="text-center font-bold text-lg p-3 bg-white text-indigo-800">(<span x-text="finalScore.toString().replace('.', ',')"></span>)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-center items-center gap-4 mt-6 mb-8 border-t border-gray-200 pt-6">
                        @if(!$isFinalized)
                            <button type="submit" form="main-skp-form" class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-black py-3 px-12 min-w-[250px] rounded-xl text-xs shadow-sm transition transform hover:scale-105 uppercase tracking-widest border border-blue-200">
                                <i class="fas fa-save mr-2 text-lg"></i> Simpan Draft
                            </button>
                        @endif

                        @if($skpHeader->status == 'Disetujui' && $canInputRealisasi)
                            <button type="submit" form="main-skp-form" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 px-12 min-w-[250px] rounded-xl text-xs shadow-md shadow-emerald-200 transition transform hover:scale-105 uppercase tracking-widest border border-emerald-500">
                                <i class="fas fa-save mr-2 text-lg"></i> Simpan Realisasi
                            </button>

                            @if (isset($isLengkap) && $isLengkap)
                                @php
                                    $pejabatName = $skpHeader->pejabatPenilai?->profile?->nama_lengkap ?? ($skpHeader->pejabatPenilai?->name ?? 'Pejabat Penilai');
                                @endphp
                                <button type="button"
                                    onclick="if(confirm('Ajukan Review Realisasi ke Pejabat Penilai ({{ $pejabatName }})?')) {
                                        document.getElementById('main-skp-form').action = '{{ route('pegawai.skp.ajukan', $skpHeader->id) }}';
                                        let methodInput = document.createElement('input');
                                        methodInput.type = 'hidden';
                                        methodInput.name = '_method';
                                        methodInput.value = 'PATCH';
                                        document.getElementById('main-skp-form').appendChild(methodInput);
                                        document.getElementById('main-skp-form').submit();
                                    }"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-12 min-w-[250px] rounded-xl text-xs shadow-md shadow-indigo-200 transition transform hover:scale-105 uppercase tracking-widest border border-indigo-500">
                                    <i class="fas fa-paper-plane mr-2 text-lg"></i> Ajukan Realisasi
                                </button>
                            @endif
                        @endif
                    </div>
                </form>

                {{-- FORM DELETE TUGAS TAMBAHAN --}}
                @if (isset($tugasTambahan))
                    @foreach ($tugasTambahan as $tugas)
                        <form id="form-delete-tugas-{{ $tugas->id }}" action="{{ route('pegawai.skp.tugas-tambahan.destroy', $tugas->id) }}" method="POST" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    @endforeach
                @endif

                {{-- FORM DELETE ACTIVITY --}}
                @if (isset($masterActivities))
                    @foreach ($masterActivities as $item)
                        <form id="form-delete-activity-{{ $item->id }}" action="{{ route('pegawai.skp.destroy-activity', $item->id) }}" method="POST" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    @endforeach
                @endif

            @else
                <div class="bg-white p-20 rounded-[3rem] border-2 border-dashed border-gray-200 text-center mt-8 shadow-inner">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-file-invoice text-4xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-900 font-black text-2xl uppercase tracking-tighter">Target SKP Belum Tersedia</p>
                    <p class="text-gray-400 text-sm mt-2 font-medium">Harap hubungi Administrator to perform generate target SKP jabatan Anda.</p>
                </div>
            @endif

        </div>

        <script>
            function preventMinus(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                }
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

            function recalcVolume(el) {
                const row = el.closest('.approval-row') || el.closest('form') || el.closest('tr');
                if (!row) return;

                const freqEl = row.querySelector('.inp-frekuensi') || row.querySelector('[name=\"frekuensi_per_tahun\"]');
                const durEl = row.querySelector('.inp-durasi') || row.querySelector('[name=\"durasi_menit\"]');
                const sdmEl = row.querySelector('.inp-sdm') || row.querySelector('[name=\"jumlah_sdm\"]');

                const freq = parseFloat(freqEl.value) || 0;
                const dur = parseFloat(durEl.value) || 0;
                const sdm = sdmEl.tagName === 'INPUT' ? (parseFloat(sdmEl.value) || 0) : (parseFloat(sdmEl.innerText) || 0);

                const volume = freq * dur * sdm;
                const volDisplay = row.querySelector('.vol-display');
                if (volDisplay) volDisplay.innerText = volume.toLocaleString('id-ID');
            }

            window.fetchTupoksiForNewRow = function(el, id) {
                const container = el.closest('tr');
                const tupoksiSelect = container.querySelector('select[name*=\"tupoksi_select\"]');
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

            window.fillTupoksiForNewRow = function(el, id) {
                if(!el.value) return;
                const data = JSON.parse(el.value);
                const container = el.closest('tr');

                container.querySelector('[name*="[tupoksi]"]').value = data.tupoksi;
                container.querySelector('[name*="[jenis_aktivitas]"]').value = data.jenis_aktivitas;
                container.querySelector('[name*="[periode]"]').value = data.periode.toLowerCase();
                container.querySelector('[name*="[frekuensi_per_tahun]"]').value = data.frekuensi_per_tahun;
                container.querySelector('[name*="[durasi_menit]"]').value = data.durasi_menit;

                const targetDesc = document.getElementById('table2-activity-' + id);
                if(targetDesc) {
                    targetDesc.value = data.tupoksi || '';
                    autoResize(targetDesc);
                }
                recalcVolume(container.querySelector('[name*="[frekuensi_per_tahun]"]'));
            }

            // Mencegah perubahan nilai pada input number saat di-scroll
            document.addEventListener('wheel', function(event) {
                if (document.activeElement.type === 'number') {
                    document.activeElement.blur();
                }
            }, {
                passive: false
            });
        </script>
    @endsection
</x-app-layout>
