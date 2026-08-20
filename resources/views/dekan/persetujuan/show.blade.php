<x-app-layout>
    @section('page-title', 'Persetujuan Target SKP')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            .table-skp-custom th, .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }
            .bg-grey-custom {
                background-color: #d1d5db !important;
            }
            .display-cell {
                width: 100%; min-height: 28px; padding: 2px 4px; display: flex; align-items: center; box-sizing: border-box; font-size: 9px; background: transparent; line-height: 1.1;
            }
            .display-cell.center { justify-content: center; text-align: center; }
            .display-cell.right { justify-content: flex-end; text-align: right; }
            .checkbox-cell { text-align: center; vertical-align: middle !important; font-size: 8px; }
            .table-input {
                width: 100%; max-width: 100%; min-width: 0; display: block; box-sizing: border-box; border: none !important; padding: 4px 4px; font-size: 9px; background: transparent; resize: none; overflow: hidden; min-height: 35px; white-space: pre-wrap; word-break: break-word; line-height: 1.1;
            }
            input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
            input[type=number] { -moz-appearance: textfield; }
            [x-cloak] { display: none !important; }
        </style>

        @php
            $userObj = auth()->user();
            $myRoles = $userObj->getRoleNames();
            $isBypassRole = $userObj->hasAnyRole(['Dekan', 'Rektorat', 'Rektor', 'Wadek 1', 'Wadek 2']);
            $isFinalized = (isset($header) && $header->status !== 'Draft');
            $total_chk = $activities->count();
            $appr_chk = $activities->where('status_approval', 'approved')->count();
            $isLevel3Approved = ($total_chk > 0 && $total_chk === $appr_chk);
            $isTargetFullyApproved = ($header->is_target_approved_atasan && $header->is_target_approved_penilai && $isLevel3Approved);
            $isFormLocked = isset($header) && in_array($header->status, ['Selesai', 'Disetujui']);

            $isLevel1 = ($header->penilai_akhir_id == $userObj->id);
            $isLevel2 = ($header->penilai_utama_id == $userObj->id);
            $isLevel3 = \App\Models\MasterSkpTendik::where('user_id', $header->user_id)->whereIn('penilai_role', $myRoles)->exists();
            $myUnapprovedCount = \App\Models\MasterSkpTendik::where('user_id', $header->user_id)->whereIn('penilai_role', $myRoles)->where('status_approval', '!=', 'approved')->count();

            $p_perilaku = [];
            $aspekLabels = ['Orientasi Pelayanan', 'Integritas', 'Komitmen', 'Disiplin', 'Kerjasama', 'Kepemimpinan'];
            foreach ($aspekLabels as $label) {
                $inds = isset($header) ? $header->perilakuIndicators->where('aspek', $label) : collect();
                $count = $inds->count();
                $p_perilaku[$label] = $inds->map(function($i) use ($count) {
                    return [
                        'id' => $i->id, 
                        'indikator' => $i->indikator, 
                        'nilai' => (float) $i->nilai, 
                        'bobot_persen' => $i->bobot_persen !== null ? (int) round($i->bobot_persen) : (int) round(100 / ($count ?: 1)),
                        'is_from_admin' => (bool) $i->is_from_admin
                    ];
                })->values()->toArray();
                if (empty($p_perilaku[$label])) { 
                    $p_perilaku[$label] = [['id' => null, 'indikator' => '', 'nilai' => 0, 'bobot_persen' => 100, 'is_from_admin' => false]]; 
                }
            }
            $p_skp = isset($header->nilai_akhir_skp) ? number_format((float)$header->nilai_akhir_skp, 2, '.', '') : '0.00';
            
            $masterJabatan = [
                1 => ['nama' => 'Rektor', 'ak' => 6], 2 => ['nama' => 'Pembantu/Wakil Rektor', 'ak' => 5], 3 => ['nama' => 'Dekan', 'ak' => 5],
                4 => ['nama' => 'Direktur Pasca Sarjana', 'ak' => 5], 5 => ['nama' => 'Ketua Sekolah Tinggi', 'ak' => 4],
                6 => ['nama' => 'Pembantu/ Wakil Ketua Sekolah Tinggi', 'ak' => 4], 7 => ['nama' => 'Direktur Politeknik', 'ak' => 4],
                8 => ['nama' => 'Pembantu/Wakil Direktur Politeknik', 'ak' => 4], 9 => ['nama' => 'Direktur Akademi', 'ak' => 4],
                10 => ['nama' => 'Pembantu/Wakil Dekan', 'ak' => 4], 11 => ['nama' => 'Asisten Direktur Pascasarjana', 'ak' => 4],
                12 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3], 13 => ['nama' => 'Ketua Jurusan/Bagian', 'ak' => 3],
                14 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3], 15 => ['nama' => 'Sekretaris Jurusan', 'ak' => 3], 16 => ['nama' => 'Kepala Laboratorium', 'ak' => 3],
            ];
        @endphp

        <div class="max-w-full mx-auto p-4 font-sans"
             x-data="{
                 isFinalized: {{ $isFinalized ? 'true' : 'false' }},
                 isTargetFullyApproved: {{ $isTargetFullyApproved ? 'true' : 'false' }},
                 skp: {{ $p_skp }},
                 perilaku: @js($p_perilaku),
                 masterBahanKuliah: @js($masterBahanKuliah),
                 masterKaryaIlmiah: @js($masterKaryaIlmiah),
                 masterAbdimas: @js($masterAbdimas),
                 masterJabatan: @js($masterJabatan),
                 openAspect: null,
                 perilakuCategories: [
                    { name: 'Orientasi Pelayanan', key: 'Orientasi Pelayanan', isOpen: true },
                    { name: 'Integritas', key: 'Integritas', isOpen: true },
                    { name: 'Komitmen', key: 'Komitmen', isOpen: true },
                    { name: 'Disiplin', key: 'Disiplin', isOpen: true },
                    { name: 'Kerjasama', key: 'Kerjasama', isOpen: true },
                    { name: 'Kepemimpinan', key: 'Kepemimpinan', isOpen: true }
                 ],
                 editTargetModal: false,
                 editSkp: { id: null, jenis: '', detail_kegiatan: [] },
                 openEditTargetModal(item) {
                     if (this.isFinalized) return;
                     this.editSkp = JSON.parse(JSON.stringify(item));
                     if (!this.editSkp.detail_kegiatan) this.editSkp.detail_kegiatan = [];
                     if (['melaksanakan_perkuliahan', 'membina_kemahasiswaan', 'unsur_penunjang'].includes(this.editSkp.jenis)) {
                         if (!Array.isArray(this.editSkp.detail_kegiatan)) { this.editSkp.detail_kegiatan = Object.values(this.editSkp.detail_kegiatan); }
                     }
                     if (this.editSkp.jenis === 'membimbing_seminar') {
                         if (Array.isArray(this.editSkp.detail_kegiatan)) { this.editSkp.detail_kegiatan = { genap: { jumlah_mahasiswa: 0 }, gasal: { jumlah_mahasiswa: 0 } }; }
                         else { if (!this.editSkp.detail_kegiatan.genap) this.editSkp.detail_kegiatan.genap = { jumlah_mahasiswa: 0 }; if (!this.editSkp.detail_kegiatan.gasal) this.editSkp.detail_kegiatan.gasal = { jumlah_mahasiswa: 0 }; }
                     }
                     if (this.editSkp.jenis === 'membimbing_kkn_pkl') {
                         if (Array.isArray(this.editSkp.detail_kegiatan) || Object.keys(this.editSkp.detail_kegiatan).length === 0) { this.editSkp.detail_kegiatan = { KKN: { genap: 0, gasal: 0 }, PKN: { genap: 0, gasal: 0 }, PKL: { genap: 0, gasal: 0 } }; }
                         else { ['KKN', 'PKN', 'PKL'].forEach(k => { if (!this.editSkp.detail_kegiatan[k]) this.editSkp.detail_kegiatan[k] = { genap: 0, gasal: 0 }; }); }
                     }
                     if (this.editSkp.jenis === 'membimbing_skripsi_thesis_disertasi') {
                         if (Array.isArray(this.editSkp.detail_kegiatan) || Object.keys(this.editSkp.detail_kegiatan).length === 0) {
                             this.editSkp.detail_kegiatan = { Skripsi: { genap: { utama: 0, penyerta: 0 }, gasal: { utama: 0, penyerta: 0 } }, Tesis: { genap: { utama: 0, penyerta: 0 }, gasal: { utama: 0, penyerta: 0 } }, Disertasi: { genap: { utama: 0, penyerta: 0 }, gasal: { utama: 0, penyerta: 0 } } };
                         } else { ['Skripsi', 'Tesis', 'Disertasi'].forEach(k => { if (!this.editSkp.detail_kegiatan[k]) { this.editSkp.detail_kegiatan[k] = { genap: { utama: 0, penyerta: 0 }, gasal: { utama: 0, penyerta: 0 } }; } }); }
                     }
                     if (this.editSkp.jenis === 'penguji_ujian_akhir') {
                         if (Array.isArray(this.editSkp.detail_kegiatan) || Object.keys(this.editSkp.detail_kegiatan).length === 0) { this.editSkp.detail_kegiatan = { genap: { ketua: 0, anggota: 0 }, gasal: { ketua: 0, anggota: 0 } }; }
                         else { if (!this.editSkp.detail_kegiatan.genap) this.editSkp.detail_kegiatan.genap = { ketua: 0, anggota: 0 }; if (!this.editSkp.detail_kegiatan.gasal) this.editSkp.detail_kegiatan.gasal = { ketua: 0, anggota: 0 }; }
                     }
                     this.editTargetModal = true;
                     this.calculateEditAK();
                 },
                 addMatkul(sem) { let list = this.editSkp.detail_kegiatan.filter(x => x.semester === sem); this.editSkp.detail_kegiatan.push({ mata_kuliah: 'MK' + (list.length + 1), sks: '', semester: sem, jumlah_kelas: 1, dosen_pengampu: 1 }); this.calculateEditAK(); },
                 removeDetail(idx) { this.editSkp.detail_kegiatan.splice(idx, 1); this.calculateEditAK(); },
                 addIndikatorKemahasiswaan() { this.editSkp.detail_kegiatan.push({ nama_kegiatan: 'Kegiatan ' + (this.editSkp.detail_kegiatan.length + 1), jumlah_genap: 0, jumlah_gasal: 0 }); this.calculateEditAK(); },
                 addPenunjang() { this.editSkp.detail_kegiatan.push({ nama_kegiatan: 'Penunjang ' + (this.editSkp.detail_kegiatan.length + 1), ak: 0 }); this.calculateEditAK(); },
                 calculateEditAK() {
                    let totalAK = 0; let totalKuant = 0; let totalWaktu = 0;
                    if (this.editSkp.jenis === 'melaksanakan_perkuliahan') {
                        let sksGenap = 0; let sksGasal = 0; let cntGenap = 0; let cntGasal = 0;
                        this.editSkp.detail_kegiatan.forEach(m => { let valSks = parseFloat(m.sks) || 0; if (m.semester === 'genap') { sksGenap += valSks; if (valSks > 0) cntGenap++; } else { sksGasal += valSks; if (valSks > 0) cntGasal++; } });
                        const calcAK = (sks) => { if (sks <= 0) return 0; return (sks >= 10 ? 5 : (sks * 0.5)) + (sks > 10 ? ((sks - 10) * 0.25) : 0); };
                        totalAK = calcAK(sksGenap) + calcAK(sksGasal); totalKuant = cntGenap + cntGasal; totalWaktu = (cntGenap > 0 ? 6 : 0) + (cntGasal > 0 ? 6 : 0);
                    } else if (this.editSkp.jenis === 'membimbing_kkn_pkl') {
                        let ak = 0; let knt = 0; let hasGenap = false; let hasGasal = false;
                        ['KKN', 'PKN', 'PKL'].forEach(k => { let g = parseFloat(this.editSkp.detail_kegiatan[k]?.genap) || 0; let s = parseFloat(this.editSkp.detail_kegiatan[k]?.gasal) || 0; if (g >= 1) { ak += 1; knt += 1; hasGenap = true; } if (s >= 1) { ak += 1; knt += 1; hasGasal = true; } });
                        totalAK = ak; totalKuant = knt; totalWaktu = (hasGenap ? 6 : 0) + (hasGasal ? 6 : 0);
                    } else if (this.editSkp.jenis === 'membimbing_skripsi_thesis_disertasi') {
                        let akTotal = 0; let kuantTotal = 0; let actGenap = false; let actGasal = false;
                        ['Skripsi', 'Tesis', 'Disertasi'].forEach(keg => {
                            let pembagi = (keg === 'Skripsi' ? 6 : (keg === 'Tesis' ? 3 : 2));
                            let gU = parseFloat(this.editSkp.detail_kegiatan[keg]?.genap?.utama) || 0; let gP = parseFloat(this.editSkp.detail_kegiatan[keg]?.genap?.penyerta) || 0;
                            let sU = parseFloat(this.editSkp.detail_kegiatan[keg]?.gasal?.utama) || 0; let sP = parseFloat(this.editSkp.detail_kegiatan[keg]?.gasal?.penyerta) || 0;
                            if (gU > 0 || gP > 0) actGenap = true; if (sU > 0 || sP > 0) actGasal = true;
                            akTotal += (gU / pembagi) + (gP / pembagi * 0.25); akTotal += (sU / pembagi) + (sP / pembagi * 0.25); kuantTotal += (gU + gP + sU + sP);
                        });
                        totalAK = akTotal; totalKuant = kuantTotal; totalWaktu = (actGenap ? 6 : 0) + (actGasal ? 6 : 0);
                    } else if (this.editSkp.jenis === 'membina_kemahasiswaan') {
                        let ak = 0; let knt = 0; let hasGenap = false; let hasGasal = false;
                        this.editSkp.detail_kegiatan.forEach(item => { let g = parseFloat(item.jumlah_genap) || 0; let s = parseFloat(item.jumlah_gasal) || 0; if (g > 0 || s > 0) { knt += 1; if (g > 0) { ak += 2; hasGenap = true; } if (s > 0) { ak += 2; hasGasal = true; } } });
                        totalAK = ak; totalKuant = knt; totalWaktu = (hasGenap ? 6 : 0) + (hasGasal ? 6 : 0);
                    } else if (this.editSkp.jenis === 'mengembangkan_bahan_kuliah') {
                        let ak = 0; let hasGenap = false; let hasGasal = false;
                        ['Genap', 'Gasal'].forEach(sem => { if (this.editSkp.detail_kegiatan[sem]) { Object.keys(this.masterBahanKuliah).forEach(keg => { let qty = parseFloat(this.editSkp.detail_kegiatan[sem][keg]) || 0; ak += qty * this.masterBahanKuliah[keg]; if (qty > 0) { if (sem === 'Genap') hasGenap = true; else hasGasal = true; } }); } });
                        totalAK = ak; totalKuant = (hasGenap ? 1 : 0) + (hasGasal ? 1 : 0); totalWaktu = (hasGenap ? 6 : 0) + (hasGasal ? 6 : 0);
                    } else if (this.editSkp.jenis === 'karya_ilmiah') {
                        let ak = 0; let knt = 0;
                        Object.keys(this.masterKaryaIlmiah).forEach(keg => { let qty = parseFloat(this.editSkp.detail_kegiatan[keg]) || 0; ak += qty * this.masterKaryaIlmiah[keg]; knt += qty; });
                        totalAK = ak; totalKuant = knt; totalWaktu = knt > 0 ? 12 : 0;
                    } else if (this.editSkp.jenis === 'pengabdian_kepada_masyarakat') {
                        let ak = 0; let knt = 0; let sumK = { 'A': 0, 'B': 0, 'C': 0 };
                        Object.keys(this.masterAbdimas).forEach(code => { Object.keys(this.masterAbdimas[code].items).forEach(keg => { let qty = parseFloat(this.editSkp.detail_kegiatan[code]?.[keg]) || 0; ak += qty * this.masterAbdimas[code].items[keg]; knt += qty; sumK[code] += qty; }); });
                        totalAK = ak; totalKuant = knt; let timeComp = (sumK['C'] >= 1 ? 12 : 0) + (sumK['B'] >= 1 ? 5 : 0) + (sumK['A'] >= 1 ? 12 : 0); totalWaktu = (timeComp >= 6) ? 12 : (sumK['B'] >= 1 ? 6 : 0);
                    } else if (this.editSkp.jenis === 'penguji_ujian_akhir') {
                        let gK = parseFloat(this.editSkp.detail_kegiatan.genap?.ketua) || 0; let gA = parseFloat(this.editSkp.detail_kegiatan.genap?.anggota) || 0;
                        let sK = parseFloat(this.editSkp.detail_kegiatan.gasal?.ketua) || 0; let sA = parseFloat(this.editSkp.detail_kegiatan.gasal?.anggota) || 0;
                        totalAK = (gK * 1) + (gA * 0.5) + (sK * 1) + (sA * 0.5); totalKuant = gK + gA + sK + sA; totalWaktu = ((gK + gA) > 0 ? 6 : 0) + ((sK + sA) > 0 ? 6 : 0);
                    } else if (this.editSkp.jenis === 'membimbing_seminar') {
                        let g = parseFloat(this.editSkp.detail_kegiatan.genap?.jumlah_mahasiswa) || 0; let s = parseFloat(this.editSkp.detail_kegiatan.gasal?.jumlah_mahasiswa) || 0;
                        totalAK = (g >= 1 ? 1 : 0) + (s >= 1 ? 1 : 0); totalKuant = g + s; totalWaktu = (g >= 1 ? 6 : 0) + (s >= 1 ? 6 : 0);
                    } else if (this.editSkp.jenis === 'unsur_penunjang') {
                        this.editSkp.detail_kegiatan.forEach(p => { totalAK += parseFloat(p.ak) || 0; if (p.ak > 0) totalKuant++; }); totalWaktu = totalKuant > 0 ? 12 : 0;
                    }
                    if (totalAK > 0) { this.editSkp.ak = totalAK.toFixed(2); this.editSkp.kuantitas_output = totalKuant; this.editSkp.waktu = totalWaktu || 6; }
                 },
                 getAvgAspek(label) { let items = this.perilaku[label] || []; if (items.length === 0) return 0; let sum = items.reduce((acc, curr) => acc + (parseFloat(curr.nilai) || 0), 0); return (sum / items.length).toFixed(2); },
                 addIndikator(label) { if(!this.perilaku[label]) this.perilaku[label] = []; this.perilaku[label].push({ id: null, indikator: '', nilai: 0, is_from_admin: false }); },
                 removeIndikator(label, index) { if (this.perilaku[label].length > 1) { this.perilaku[label].splice(index, 1); } },
                 get sumPerilaku() { let sum = 0; Object.keys(this.perilaku).forEach(label => { sum += parseFloat(this.getAvgAspek(label)); }); return sum; },
                 get avgPerilaku() { return (this.sumPerilaku / Object.keys(this.perilaku).length).toFixed(2); },
                 get calcSkp60() { return (this.skp * 0.6).toFixed(2); },
                 get calcPerilaku40() { return (this.avgPerilaku * 0.4).toFixed(2); },
                 get finalScore() { return (parseFloat(this.calcSkp60) + parseFloat(this.calcPerilaku40)).toFixed(2); },
                 getPredicateFinal() {
                    let s = parseFloat(this.finalScore);
                    if (s > 90) return 'Sangat Baik';
                    if (s > 75) return 'Baik';
                    if (s > 60) return 'Cukup';
                    if (s > 50) return 'Kurang';
                    return 'Buruk';
                 }
             }">

            {{-- 1. HEADER --}}
            <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4 text-left">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white shadow-lg"><i class="fas fa-user-check text-xl"></i></div>
                    <div>
                        <h1 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Persetujuan Target SKP</h1>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest text-left">Pegawai: <span class="text-blue-600">{{ $header->user->name }}</span> | Periode: <span class="text-gray-700">{{ $header->periode->tahun }}</span></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('dekan.persetujuan-skp') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-black transition uppercase tracking-widest"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
                </div>
            </div>

            {{-- BIODATA TABLE (2x1 Hierarchical) --}}
            <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-8 overflow-hidden">
                <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">
                    {{-- SISI KIRI: PEGAWAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0 font-black tracking-widest">I</div>
                            <div class="flex-1 px-3 flex items-center text-blue-800 min-w-0"><span class="truncate text-[10px] font-black tracking-tight uppercase">PEGAWAI YANG DINILAI</span></div>
                        </div>
                        @php $fieldsI = [['1','Nama',$header->user->profile->nama_lengkap ?? $header->user->name],['2','NIP / NIDN',$header->user->profile->nip ?? '-'],['3','Pangkat/Gol',$header->user->profile->pangkat_golongan ?? '-'],['4','Jabatan',$header->user->profile->jabatan_pekerjaan ?? ($header->user->getRoleNames()->first() ?? '-')],['5','Unit Kerja',$header->user->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']]; @endphp
                        @foreach($fieldsI as $f)
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">{{ $f[0] }}</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter text-left">{{ $f[1] }}</div>
                            <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs">{{ $f[2] }}</div>
                        </div>
                        @endforeach
                    </div>
                    {{-- SISI KANAN: PEJABAT PENILAI --}}
                    <div class="flex-1 flex flex-col min-w-0 bg-white">
                        <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                            <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black tracking-widest">II</div>
                            <div class="flex-1 px-3 flex items-center justify-between text-emerald-800 min-w-0">
                                <span class="truncate text-[10px] font-black tracking-tight uppercase">PEJABAT PENILAI</span>
                                @if($header && $header->periode)<span class="text-white bg-emerald-600 px-2.5 py-1.5 rounded-lg text-[9px] shadow-lg tracking-wider ml-2 font-black uppercase">{{ $header->periode->tahun }}</span>@endif
                            </div>
                        </div>
                        @php $fieldsII = [['1','Nama',$header->pejabatPenilai->profile->nama_lengkap ?? ($header->pejabatPenilai->name ?? '-')],['2','NIP / NIDN',$header->pejabatPenilai->profile->nip ?? '-'],['3','Pangkat/Gol',$header->pejabatPenilai->profile->pangkat_golongan ?? '-'],['4','Jabatan',$header->pejabatPenilai->profile->jabatan_pekerjaan ?? ($header->pejabatPenilai->getRoleNames()->first() ?? '-')],['5','Unit Kerja',$header->pejabatPenilai->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']]; @endphp
                        @foreach($fieldsII as $f)
                        <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                            <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">{{ $f[0] }}</div>
                            <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter text-left">{{ $f[1] }}</div>
                            <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">{{ $f[2] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                {{-- BAWAH: ATASAN PEJABAT PENILAI (Full Width) --}}
                <div class="bg-white flex flex-col min-w-0">
                    <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                        <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0 font-black tracking-widest">III</div>
                        <div class="flex-1 px-3 flex items-center text-indigo-800 min-w-0"><span class="truncate text-[10px] font-black tracking-tight uppercase">ATASAN PEJABAT PENILAI</span></div>
                    </div>
                    @php 
                        $atasan = $header->atasanPejabatPenilai ?? $header->penilaiAkhir;
                        $fieldsIII = [['1','Nama',$atasan->profile->nama_lengkap ?? ($atasan->name ?? '-')],['2','NIP / NIDN',$atasan->profile->nip ?? '-'],['3','Pangkat/Gol',$atasan->profile->pangkat_golongan ?? '-'],['4','Jabatan',$atasan->profile->jabatan_pekerjaan ?? ($atasan->getRoleNames()->first() ?? '-')],['5','Unit Kerja',$atasan->profile->unit_kerja ?? 'Universitas Muhammadiyah Surabaya']]; 
                    @endphp
                    @foreach($fieldsIII as $f)
                    <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                        <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">{{ $f[0] }}</div>
                        <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter text-left">{{ $f[1] }}</div>
                        <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">{{ $f[2] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <form action="{{ route('dekan.persetujuan-skp.approve', $header->id) }}" method="POST">
                @csrf
                {{-- MONITORING --}}
                <div class="mb-6 bg-white border border-gray-300 rounded-xl shadow-sm p-5">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-tighter flex items-center gap-2"><i class="fas fa-shield-alt text-blue-600"></i> Monitoring Persetujuan Target SKP</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        {{-- Level 0: PEGAWAI --}}
                        <div class="p-3 rounded-lg border {{ $isFinalized ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isFinalized ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $isFinalized ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase text-gray-500">Level 0</span>
                            </div>
                            <p class="text-xs font-black {{ $isFinalized ? 'text-emerald-700' : 'text-orange-700' }}">PEGAWAI</p>
                            <p class="text-[9px] font-bold italic mt-1 uppercase">{{ $isFinalized ? 'Target Difinalisasi' : 'Menunggu Finalisasi' }}</p>
                        </div>

                        {{-- Level 1: PEJABAT PENILAI --}}
                        <div class="p-3 rounded-lg border {{ $header->is_target_approved_penilai ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_penilai ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $header->is_target_approved_penilai ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase text-gray-500">Level 1</span>
                            </div>
                            <p class="text-xs font-black {{ $header->is_target_approved_penilai ? 'text-emerald-700' : 'text-orange-700' }}">PEJABAT PENILAI</p>
                            <p class="text-[9px] text-gray-500 font-bold mb-2">@if(!$isFinalized) Menunggu Finalisasi @else {{ $header->is_target_approved_penilai ? 'Disetujui' : 'Menunggu Persetujuan' }} @endif</p>
                            
                            @if($isLevel2 && $isFinalized && !$header->is_target_approved_penilai)
                                <button type="submit" name="action" value="approve" 
                                    onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI TARGET SKP ini sebagai PEJABAT PENILAI?')"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-1.5 px-2 rounded-lg text-[9px] uppercase transition-all shadow-md active:scale-95">
                                    <i class="fas fa-check-circle mr-1"></i> Setujui Target SKP
                                </button>
                            @endif
                        </div>

                        {{-- Level 2: ATASAN PEJABAT PENILAI --}}
                        <div class="p-3 rounded-lg border {{ $header->is_target_approved_atasan ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $header->is_target_approved_atasan ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $header->is_target_approved_atasan ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase text-gray-500">Level 2</span>
                            </div>
                            <p class="text-xs font-black {{ $header->is_target_approved_atasan ? 'text-emerald-700' : 'text-orange-700' }}">ATASAN PEJABAT PENILAI</p>
                            <p class="text-[9px] text-gray-500 font-bold mb-2">@if(!$isFinalized) Menunggu Finalisasi @else {{ $header->is_target_approved_atasan ? 'Disetujui' : 'Menunggu Persetujuan' }} @endif</p>

                            @if($isLevel1 && $isFinalized && !$header->is_target_approved_atasan)
                                @if(!$header->is_target_approved_penilai)
                                    <span class="text-[8px] font-black text-orange-600 uppercase bg-orange-50 py-1.5 px-2 rounded-lg border border-orange-200 block w-full text-center">Menunggu Pejabat Penilai</span>
                                @else
                                    <button type="submit" name="action" value="approve" 
                                        onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI TARGET SKP ini sebagai ATASAN PEJABAT PENILAI?')"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-1.5 px-2 rounded-lg text-[9px] uppercase transition-all shadow-md active:scale-95">
                                        <i class="fas fa-check-circle mr-1"></i> Setujui Target SKP
                                    </button>
                                @endif
                            @endif
                        </div>

                        {{-- Level 3: PENILAI KEGIATAN --}}
                        <div class="p-3 rounded-lg border {{ $isLevel3Approved ? 'bg-emerald-50 border-emerald-200' : 'bg-orange-50 border-orange-200' }}">
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2 {{ $isLevel3Approved ? 'bg-emerald-500 text-white' : 'bg-orange-400 text-white' }}">
                                    <i class="fas {{ $isLevel3Approved ? 'fa-check' : 'fa-clock' }} text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black uppercase text-gray-500">Level 3</span>
                            </div>
                            <p class="text-xs font-black {{ $isLevel3Approved ? 'text-emerald-700' : 'text-orange-700' }}">PENILAI KEGIATAN</p>
                            <p class="text-[9px] text-gray-500 font-bold mb-2">@if(!$isFinalized) Menunggu Finalisasi @else {{ $appr_chk }} / {{ $total_chk }} Terverifikasi @endif</p>
                        </div>
                    </div>
                </div>

                {{-- TABLE 1 --}}
                <div class="mb-2 ml-1 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                        <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Rincian Tupoksi & Beban Kerja</h3>
                    </div>
                    
                    @if(($isLevel1 || $isLevel2) && $isFinalized && $activities->where('status_approval', '!=', 'approved')->count() > 0)
                        <div class="flex items-center gap-2">
                            <button type="submit" name="approve_all_tupoksi" value="1" 
                                onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI SELURUH kegiatan tupoksi di Tabel 1?')"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-1.5 px-3 rounded-lg text-[9px] uppercase transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                                <i class="fas fa-check-double"></i> Setujui Semua
                            </button>
                            <button type="submit" name="reject_all_tupoksi" value="1" 
                                onclick="return confirm('Apakah Anda yakin ingin MENOLAK & MENGHAPUS SELURUH kegiatan tupoksi di Tabel 1? Tindakan ini akan mengosongkan daftar rincian.')"
                                class="bg-rose-600 hover:bg-rose-700 text-white font-black py-1.5 px-3 rounded-lg text-[9px] uppercase transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                                <i class="fas fa-trash-sweep"></i> Tolak Semua
                            </button>
                        </div>
                    @endif
                </div>
                <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-8">
                    <table class="w-full table-fixed border-collapse bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 h-9 uppercase text-[9px] font-black"><th rowspan="2" style="width: 3%;">No</th><th rowspan="2" style="width: 12%;" class="border-l">I. Jabatan/Bidang</th><th rowspan="2" style="width: 20%;" class="border-l">II. Kegiatan Tupoksi</th><th rowspan="2" style="width: 20%;" class="border-l">III. Jenis Aktivitas</th><th rowspan="2" style="width: 8%;" class="border-l">Penilai</th><th colspan="3" style="width: 9%;" class="border-l border-b">Kategori</th><th rowspan="2" style="width: 6%;" class="border-l">Periode</th><th rowspan="2" style="width: 4%;" class="border-l">Freq</th><th rowspan="2" style="width: 4%;" class="border-l">Dur</th><th rowspan="2" style="width: 3%;" class="border-l">SDM</th><th rowspan="2" style="width: 6%;" class="border-l">Vol Kerja</th><th rowspan="2" style="width: 8%;" class="border-l">Aksi</th></tr>
                            <tr class="bg-gray-100 text-gray-700 text-[7px] font-bold uppercase"><th style="width: 3%;" class="border-l">Utama</th><th style="width: 3%;" class="border-l">Prd</th><th style="width: 3%;" class="border-l">Bkn</th></tr>
                        </thead>
                        <tbody id="tupoksi-container">
                            @forelse($activities as $index => $item)
                                <tr class="hover:bg-slate-50 transition-colors border-t border-gray-200 text-[9px]">
                                    <td class="text-center font-bold text-slate-400 pt-3 border-r"><div class="display-cell center">{{ $index + 1 }}</div></td>
                                    <td class="border-r"><div class="display-cell font-semibold text-blue-800 uppercase">{{ $item->role_name }}</div></td>
                                    <td class="border-r"><div class="display-cell font-bold text-gray-800">{{ $item->tupoksi }}</div></td>
                                    <td class="border-r"><div class="display-cell text-gray-700 italic">{{ $item->jenis_aktivitas }}</div></td>
                                    <td class="border-r text-center uppercase text-[8px] font-semibold text-emerald-700"><div class="display-cell center">{{ $item->penilai_role ?? '-' }}</div></td>
                                    <td class="checkbox-cell border-r text-emerald-600 font-black">{{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? '✓' : '' }}</td>
                                    <td class="checkbox-cell border-r text-emerald-600 font-black">{{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? '✓' : '' }}</td>
                                    <td class="checkbox-cell border-r text-emerald-600 font-black">{{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? '✓' : '' }}</td>
                                    <td class="border-r"><div class="display-cell center uppercase font-bold text-gray-500">{{ $item->periode }}</div></td>
                                    <td class="border-r"><div class="display-cell center font-bold">{{ $item->frekuensi_per_tahun }}</div></td>
                                    <td class="border-r"><div class="display-cell center font-bold">{{ $item->durasi_menit }}</div></td>
                                    <td class="border-r"><div class="display-cell center font-bold">{{ $item->jumlah_sdm }}</div></td>
                                    <td class="bg-slate-50 border-r"><div class="display-cell right font-black text-indigo-700">{{ number_format(($item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm), 0, ',', '.') }}</div></td>
                                    <td class="p-2 text-center border-l align-middle">
                                        @if(!$isFinalized) <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded shadow-sm opacity-60"><i class="fas fa-lock mr-1"></i> Waiting Finalization</span>
                                        @else
                                            @if($item->status_approval === 'approved') 
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[8px] font-black text-emerald-600 uppercase bg-emerald-50 py-1 px-2 rounded border border-emerald-200 block w-full mb-1">Disetujui</span>
                                                    <span class="text-[7px] text-emerald-800 font-bold italic tracking-tighter leading-none">Oleh: {{ $item->approver->profile->nama_lengkap ?? ($item->approver->name ?? 'Pimpinan') }}</span>
                                                </div>
                                            @elseif($item->status_approval === 'rejected') 
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[8px] font-black text-red-600 uppercase bg-red-50 py-1 px-2 rounded border border-red-200 block w-full mb-1">Ditolak</span>
                                                    <span class="text-[7px] text-red-800 font-bold italic tracking-tighter leading-none">Oleh: {{ $item->approver->profile->nama_lengkap ?? ($item->approver->name ?? 'Pimpinan') }}</span>
                                                </div>
                                            @else
                                                <div class="flex flex-col gap-1">
                                                    @if(strtoupper($item->penilai_role) === 'ATASAN PEJABAT PENILAI' && !$header->is_target_approved_penilai)
                                                        <span class="text-[8px] font-black text-orange-600 uppercase bg-orange-50 py-1 px-2 rounded border border-orange-200 block">Menunggu Pejabat Penilai</span>
                                                    @else
                                                        @if($myRoles->contains($item->penilai_role) || Auth::user()->hasRole('Super Admin') || ($isLevel1 && strtoupper($item->penilai_role) === 'ATASAN PEJABAT PENILAI') || ($isLevel2 && strtoupper($item->penilai_role) === 'PEJABAT PENILAI'))
                                                            <button type="submit" name="approve_tupoksi" value="{{ $item->id }}" 
                                                                onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI kegiatan ini?')"
                                                                class="bg-emerald-600 text-white text-[8px] py-1 px-2 rounded font-black uppercase hover:bg-emerald-700 transition shadow-sm">Setujui</button>
                                                            
                                                            <button type="submit" name="reject_tupoksi" value="{{ $item->id }}"
                                                                onclick="return confirm('Apakah Anda yakin ingin MENOLAK & MENGHAPUS kegiatan ini? Tindakan ini tidak dapat dibatalkan.')"
                                                                class="bg-rose-600 text-white text-[8px] py-1 px-2 rounded font-black uppercase hover:bg-rose-700 transition shadow-sm">Tolak</button>
                                                        @else <span class="text-[8px] font-black text-orange-600 uppercase bg-orange-50 py-1 px-2 rounded border border-orange-200 block">Pending</span> @endif
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="14" class="p-4 text-center text-gray-400 italic font-medium">Belum ada rincian tupoksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(!$isFinalized)
                    <div class="mt-2 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-300 w-full mb-10">
                        <button type="button" id="btn-add-tupoksi" class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg text-xs font-black hover:bg-blue-700 shadow-lg flex items-center justify-center gap-2"><i class="fas fa-plus"></i> Tambah Baris Tupoksi</button>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight italic">Menambahkan baris rincian tupoksi secara manual</div>
                    </div>
                @endif

                {{-- TABLE 2 --}}
                <div class="mb-2 ml-1 flex items-center gap-2"><div class="w-1.5 h-4 bg-emerald-600 rounded-full"></div><h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 2: Target & Realisasi SKP Dosen</h3></div>
                <div class="bg-white border border-gray-300 rounded-xl shadow-md overflow-hidden mb-8 w-full">
                    <div class="bg-gray-100 border-b border-gray-300 p-3 flex justify-between items-center"><span class="font-black text-sm uppercase tracking-tighter">TARGET DAN REALISASI SKP DOSEN</span></div>
                    <div class="overflow-x-auto w-full">
                            <table class="w-full min-w-[1000px] border-collapse text-[11px] border-none bg-white">
                                <thead class="text-gray-900 font-bold text-center bg-gray-50 border-b border-gray-300">
                                    <tr>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-8">NO</th>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-auto uppercase">I. Kegiatan Tugas Jabatan</th>
                                        <th rowspan="2" class="border border-gray-300 p-2 w-10">AK</th>
                                        <th colspan="4" class="border border-gray-300 p-2 uppercase text-blue-800">TARGET</th>
                                        @if($isTargetFullyApproved)
                                            <th rowspan="2" class="border border-gray-300 p-2 w-10 border-l-2 border-l-gray-400">AK</th>
                                            <th colspan="4" class="border border-gray-300 p-2 uppercase text-emerald-800">REALISASI</th>
                                            <th rowspan="2" class="border border-gray-300 p-2 w-16">PENGHITUNGAN</th>
                                            <th rowspan="2" class="border border-gray-300 p-2 w-16">NILAI CAPAIAN</th>
                                        @endif
                                        <th rowspan="2" class="p-2 w-16 bg-gray-100 border border-gray-300">AKSI</th>
                                    </tr>
                                    <tr>
                                        <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-blue-700">Kuant</th>
                                        <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-blue-700">Kual</th>
                                        <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-blue-700">Waktu</th>
                                        <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-blue-700">Biaya</th>

                                        @if($isTargetFullyApproved)
                                            <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800 border-l-2 border-l-gray-400">Kuant</th>
                                            <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-emerald-800">Kual</th>
                                            <th class="border border-gray-300 p-1 w-12 uppercase text-[9px] text-emerald-800">Waktu</th>
                                            <th class="border border-gray-300 p-1 w-16 uppercase text-[9px] text-emerald-800">Biaya</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800">
                                    @php
                                        $kategoriOrder = [
                                            "Pelaksanaan Pendidikan" => "PELAKSANAAN PENDIDIKAN",
                                            "Pelaksanaan Penelitian" => "PELAKSANAAN PENELITIAN",
                                            "Pelaksanaan Pengabdian Kepada Masyarakat" => "PELAKSANAAN PENGABDIAN KEPADA MASYARAKAT",
                                            "Menduduki Jabatan Di Perguruan Tinggi" => "MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI",
                                            "Unsur Penunjang" => "UNSUR PENUNJANG"
                                        ];
                                        $mappingKegiatan = [
                                            'melaksanakan_perkuliahan' => ['nama' => 'Melaksanakan perkuliahan', 'satuan' => 'mata kuliah'],
                                            'membimbing_seminar' => ['nama' => 'Membimbing mahasiswa seminar', 'satuan' => 'mahasiswa'],
                                            'membimbing_kkn_pkl' => ['nama' => 'Membimbing mahasiswa KKN, Praktek Kerja Nyata, Praktek Kerja Lapangan', 'satuan' => 'kegiatan'],
                                            'membimbing_skripsi_thesis_disertasi' => ['nama' => 'Membimbing dan ikut membimbing dalam menghasilkan skripsi, thesis, disertasi', 'satuan' => 'kegiatan'],
                                            'penguji_ujian_akhir' => ['nama' => 'Menjadi penguji Ujian Akhir baik sebagai ketua maupun anggota', 'satuan' => 'kegiatan'],
                                            'membina_kemahasiswaan' => ['nama' => 'Membina kegiatan kemahasiswaan di bidang akademik dan kemahasiswaan', 'satuan' => 'kegiatan'],
                                            'mengembangkan_bahan_kuliah' => ['nama' => 'Mengembangkan bahan kuliah', 'satuan' => 'bahan'],
                                            'karya_ilmiah' => ['nama' => 'Menghasilkan karya ilmiah', 'satuan' => 'karya'],
                                            'pengabdian_kepada_masyarakat' => ['nama' => 'Melaksanakan kegiatan Pengabdian Kepada Masyarakat', 'satuan' => 'kegiatan'],
                                            'menduduki_jabatan_pimpinan' => ['nama' => 'MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI', 'satuan' => 'Jabatan'],
                                            'unsur_penunjang' => ['nama' => 'Unsur Penunjang', 'satuan' => 'kegiatan'],
                                        ];

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
                                        $nomor = 1;
                                    @endphp

                                    @foreach($kategoriOrder as $katDb => $katLabel)
                                        @php $items = $skpDosen->where('kategori', $katDb); @endphp
                                        @if($items->count() > 0)
                                            <tr class="font-bold bg-gray-50 border-b border-gray-300">
                                                <td class="border-r border-gray-300 p-2 text-center"></td>
                                                <td colspan="{{ $isTargetFullyApproved ? 14 : 7 }}" class="p-2 font-black uppercase text-gray-900 italic">{{ $katLabel }}</td>
                                            </tr>
                                            @foreach($items as $item)
                                                @php
                                                    $nilai = $item->penilaian;
                                                    $jenis = strtolower(trim($item->jenis));
                                                    $satuan = isset($mappingKegiatan[$jenis]) ? $mappingKegiatan[$jenis]['satuan'] : 'kegiatan';
                                                    $labelKegiatan = isset($mappingKegiatan[$jenis]) ? e($mappingKegiatan[$jenis]['nama']) : nl2br(e($item->kegiatan));
                                                @endphp

                                                @if (str_contains($jenis, 'jabatan') || str_contains($jenis, 'pimpinan'))
                                                    @php
                                                        $detail = is_string($item->detail_kegiatan) ? json_decode($item->detail_kegiatan, true) : $item->detail_kegiatan;
                                                        $hasGenap = !empty($detail['kode_genap']) && isset($masterJabatan[$detail['kode_genap']]);
                                                        $hasGasal = !empty($detail['kode_gasal']) && isset($masterJabatan[$detail['kode_gasal']]);
                                                        $tahunSkp = $skpHeader->periode->tahun ?? date('Y');
                                                        $labelGenap = 'Semester Genap ' . ($tahunSkp - 1) . '/' . $tahunSkp;
                                                        $labelGasal = 'Semester Gasal ' . $tahunSkp . '/' . ($tahunSkp + 1);
                                                    @endphp

                                                    @if ($hasGenap || $hasGasal)
                                                        @php $currentNomor = $nomor++; @endphp
                                                        <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-300 {{ (!$isFinalized || Auth::user()->hasRole('Super Admin')) ? 'cursor-pointer group' : '' }}"
                                                            @if(!$isFinalized || Auth::user()->hasRole('Super Admin')) @click="openEditTargetModal(@js($item))" @endif>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top relative">
                                                                {{ $currentNomor }}
                                                                @if(!$isFinalized || Auth::user()->hasRole('Super Admin'))
                                                                    <div class="absolute inset-y-0 left-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                                                @endif
                                                            </td>
                                                            <td class="border-r border-gray-300 p-2 align-top text-gray-900 uppercase font-black text-left">
                                                                <div class="flex flex-col text-left">
                                                                    <span>{!! isset($mappingKegiatan[$item->jenis]) ? e($mappingKegiatan[$item->jenis]['nama']) : 'MENDUDUKI JABATAN PIMPINAN DI PERGURUAN TINGGI' !!}</span>
                                                                    @if(!$isFinalized || Auth::user()->hasRole('Super Admin'))
                                                                        <span class="text-[7px] text-blue-500 font-bold uppercase mt-1 opacity-0 group-hover:opacity-100 transition-opacity text-left"><i class="fas fa-edit mr-1"></i> Klik untuk edit target</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td colspan="{{ $isTargetFullyApproved ? 13 : 6 }}" class="bg-gray-50"></td>
                                                        </tr>

                                                        @if ($hasGenap)
                                                            @php $jabatanGenap = $masterJabatan[$detail['kode_genap']]; @endphp
                                                            <tr class="bg-blue-50/30 border-b border-gray-300 font-bold italic text-[9px]">
                                                                <td class="border-r border-gray-300"></td>
                                                                <td colspan="{{ $isTargetFullyApproved ? 14 : 7 }}" class="p-2 text-blue-900 pl-6">{{ $labelGenap }}</td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300">
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-[10px] text-gray-400">{{ $currentNomor }}.1</td>
                                                                <td class="border-r border-gray-300 p-2 align-top text-gray-800 pl-8 font-medium">{{ $jabatanGenap['nama'] }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">{{ number_format($jabatanGenap['ak'], 2, ',', '.') }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">1 {{ $satuan }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">{{ round($item->kualitas_mutu) }}%</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">6 bln</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">-</td>
                                                                @if($isTargetFullyApproved)
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white text-emerald-700 font-bold border-l-2 border-l-gray-400">{{ number_format(optional($nilai)->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ number_format(optional($nilai)->realisasi_kuantitas ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ round(optional($nilai)->realisasi_kualitas ?? 0) }}%</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ optional($nilai)->realisasi_waktu ?? 0 }} bln</td>
                                                                    <td class="border-r border-gray-200 p-1 text-center align-middle bg-white font-bold">-</td>
                                                                    <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-semibold">{{ number_format(optional($nilai)->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-bold">{{ number_format(optional($nilai)->nilai_capaian ?? 0, 2, ',', '.') }}</td>
                                                                @endif
                                                                <td class="p-2 text-center bg-gray-50 align-middle">
                                                                    @if(!$isTargetFullyApproved || $isFormLocked)
                                                                        <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded-sm cursor-not-allowed uppercase tracking-tighter"><i class="fas fa-lock mr-1"></i> Locked</span>
                                                                    @else
                                                                        <a href="{{ route('pegawai.skp-dosen.jabatan', $item->id) }}" class="bg-rose-600 text-white text-[8px] p-1 rounded block mb-1 uppercase font-bold shadow-sm hover:bg-rose-700 transition">Realisasi</a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif

                                                        @if ($hasGasal)
                                                            @php
                                                                $jabatanGasal = $masterJabatan[$detail['kode_gasal']];
                                                                $subNumberGasal = $hasGenap ? 2 : 1;
                                                            @endphp
                                                            <tr class="bg-blue-50/30 border-b border-gray-300 font-bold italic text-[9px]">
                                                                <td class="border-r border-gray-300"></td>
                                                                <td colspan="{{ $isTargetFullyApproved ? 14 : 7 }}" class="p-2 text-blue-900 pl-6">{{ $labelGasal }}</td>
                                                            </tr>
                                                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-300">
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-[10px] text-gray-400">{{ $currentNomor }}.{{ $subNumberGasal }}</td>
                                                                <td class="border-r border-gray-300 p-2 align-top text-gray-800 pl-8 font-medium">{{ $jabatanGasal['nama'] }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">{{ number_format($jabatanGasal['ak'], 2, ',', '.') }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">1 {{ $satuan }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">{{ round($item->kualitas_mutu) }}%</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">6 bln</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">-</td>
                                                                @if($isTargetFullyApproved)
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white text-emerald-700 font-bold border-l-2 border-l-gray-400">{{ number_format(optional($nilai)->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ number_format(optional($nilai)->realisasi_kuantitas ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ round(optional($nilai)->realisasi_kualitas ?? 0) }}%</td>
                                                                    <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ optional($nilai)->realisasi_waktu ?? 0 }} bln</td>
                                                                    <td class="border-r border-gray-200 p-1 text-center align-middle bg-white font-bold">-</td>
                                                                    <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-semibold">{{ number_format(optional($nilai)->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                                                    <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-bold">{{ number_format(optional($nilai)->nilai_capaian ?? 0, 2, ',', '.') }}</td>
                                                                @endif
                                                                <td class="p-2 text-center bg-gray-50 align-middle">
                                                                    @if(!$isTargetFullyApproved || $isFormLocked)
                                                                        <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded-sm cursor-not-allowed uppercase tracking-tighter"><i class="fas fa-lock mr-1"></i> Locked</span>
                                                                    @else
                                                                        <a href="{{ route('pegawai.skp-dosen.jabatan', $item->id) }}" class="bg-rose-600 text-white text-[8px] p-1 rounded block mb-1 uppercase font-bold shadow-sm hover:bg-rose-700 transition">Realisasi</a>
                                                                    @endif
                                                            </tr>
                                                        @endif
                                                    @else
                                                        <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-300">
                                                            <td class="border-r border-gray-300 p-2 text-center align-top">{{ $nomor++ }}</td>
                                                            <td class="border-r border-gray-300 p-2 align-top text-gray-900 font-medium">{!! $labelKegiatan !!}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">{{ number_format($item->ak, 2, ',', '.') }}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">{{ $item->kuantitas_output }} {{ $satuan }}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">{{ round($item->kualitas_mutu) }}%</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">{{ $item->waktu }} bln</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">-</td>
                                                            @if($isTargetFullyApproved)
                                                                <td class="border-r border-gray-300 p-1 text-center align-middle bg-white text-emerald-700 font-bold border-l-2 border-l-gray-400">{{ number_format(optional($nilai)->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                                                <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ number_format(optional($nilai)->realisasi_kuantitas ?? 0, 2, ',', '.') }}</td>
                                                                <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ round(optional($nilai)->realisasi_kualitas ?? 0) }}%</td>
                                                                <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ optional($nilai)->realisasi_waktu ?? 0 }} bln</td>
                                                                <td class="border-r border-gray-200 p-1 text-center align-middle bg-white font-bold">-</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-semibold">{{ number_format(optional($nilai)->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                                                <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-bold">{{ number_format(optional($nilai)->nilai_capaian ?? 0, 2, ',', '.') }}</td>
                                                            @endif
                                                            <td class="p-2 text-center bg-gray-50 align-middle">
                                                                @if(!$isTargetFullyApproved || $isFormLocked)
                                                                    <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded-sm cursor-not-allowed uppercase tracking-tighter"><i class="fas fa-lock mr-1"></i> Locked</span>
                                                                @else
                                                                    <a href="{{ route('pegawai.skp-dosen.jabatan', $item->id) }}" class="bg-rose-600 text-white text-[8px] p-1 rounded block mb-1 uppercase font-bold shadow-sm hover:bg-rose-700 transition">Realisasi</a>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @else
                                                    <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-300 {{ (!$isFinalized || Auth::user()->hasRole('Super Admin')) ? 'cursor-pointer group' : '' }}"
                                                        @if(!$isFinalized || Auth::user()->hasRole('Super Admin')) @click="openEditTargetModal(@js($item))" @endif>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top relative">
                                                            {{ $nomor++ }}
                                                            @if(!$isFinalized || Auth::user()->hasRole('Super Admin'))
                                                                <div class="absolute inset-y-0 left-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                                            @endif
                                                        </td>
                                                        <td class="border-r border-gray-300 p-2 align-top text-gray-900 font-medium">
                                                            <div class="flex flex-col">
                                                                <span>{!! $labelKegiatan !!}</span>
                                                                @if(!$isFinalized || Auth::user()->hasRole('Super Admin'))
                                                                    <span class="text-[7px] text-blue-500 font-bold uppercase mt-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-edit mr-1"></i> Klik untuk edit target</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold">{{ number_format($item->ak, 2, ',', '.') }}</td>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">{{ $item->kuantitas_output }} {{ $satuan }}</td>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">{{ round($item->kualitas_mutu) }}%</td>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top whitespace-nowrap font-semibold">{{ $item->waktu }} bln</td>
                                                        <td class="border-r border-gray-300 p-2 text-center align-top font-semibold">{{ number_format($item->biaya ?? 0, 0, ',', '.') }}</td>

                                                        @if($isTargetFullyApproved)
                                                            <td class="border-r border-gray-300 p-1 text-center align-middle bg-white text-emerald-700 font-bold border-l-2 border-l-gray-400">{{ number_format(optional($nilai)->realisasi_ak ?? 0, 2, ',', '.') }}</td>
                                                            <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ number_format(optional($nilai)->realisasi_kuantitas ?? 0, 2, ',', '.') }}</td>
                                                            <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ round(optional($nilai)->realisasi_kualitas ?? 0) }}%</td>
                                                            <td class="border-r border-gray-300 p-1 text-center align-middle bg-white font-bold">{{ optional($nilai)->realisasi_waktu ?? 0 }} bln</td>
                                                            <td class="border-r border-gray-200 p-1 text-center align-middle bg-white font-bold">{{ number_format(optional($nilai)->realisasi_biaya ?? 0, 0, ',', '.') }}</td>

                                                            <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-semibold">{{ number_format(optional($nilai)->nilai_penghitungan ?? 0, 2, ',', '.') }}</td>
                                                            <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-bold">{{ number_format(optional($nilai)->nilai_capaian ?? 0, 2, ',', '.') }}</td>
                                                        @endif
                                                        <td class="p-2 text-center bg-gray-50 align-middle border-l border-gray-200">
                                                            @if(!$isTargetFullyApproved || $isFormLocked)
                                                                <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded-sm cursor-not-allowed uppercase tracking-tighter"><i class="fas fa-lock mr-1"></i> Locked</span>
                                                            @else
                                                                @php
                                                                    $isPendidikan = ($katDb === 'Pelaksanaan Pendidikan');
                                                                    $isPerkuliahan = ($jenis === 'melaksanakan_perkuliahan');
                                                                    $isSeminar = ($jenis === 'membimbing_seminar');
                                                                    $isBimbingan = ($jenis === 'membimbing_skripsi_thesis_disertasi');
                                                                    $isPkl = ($jenis === 'membimbing_kkn_pkl');
                                                                    $isPenguji = ($jenis === 'penguji_ujian_akhir');
                                                                    $isKemahasiswaan = ($jenis === 'membina_kemahasiswaan');
                                                                    $isAbdimas = ($jenis === 'pengabdian_kepada_masyarakat');
                                                                    $isBahan = ($jenis === 'mengembangkan_bahan_kuliah');
                                                                    $isKaryaIlmiah = ($jenis === 'karya_ilmiah');
                                                                    $isPenunjang = ($jenis === 'unsur_penunjang');

                                                                    $routeRealisasi = null;
                                                                    if($isPerkuliahan) $routeRealisasi = 'pegawai.skp-dosen.perkuliahan';
                                                                    elseif($isSeminar) $routeRealisasi = 'pegawai.skp-dosen.seminar';
                                                                    elseif($isBimbingan) $routeRealisasi = 'pegawai.skp-dosen.bimbing';
                                                                    elseif($isPkl) $routeRealisasi = 'pegawai.skp-dosen.pkl';
                                                                    elseif($isPenguji) $routeRealisasi = 'pegawai.skp-dosen.penguji';
                                                                    elseif($isKemahasiswaan) $routeRealisasi = 'pegawai.skp-dosen.kemahasiswaan';
                                                                    elseif($isAbdimas) $routeRealisasi = 'pegawai.skp-dosen.abdimas';
                                                                    elseif($isBahan) $routeRealisasi = 'pegawai.skp-dosen.bahan';
                                                                    elseif($isKaryaIlmiah) $routeRealisasi = 'pegawai.skp-dosen.karya-ilmiah';
                                                                    elseif($isPenunjang) $routeRealisasi = 'pegawai.skp-dosen.penunjang';
                                                                @endphp
                                                                @if($routeRealisasi)
                                                                    <a href="{{ route($routeRealisasi, $item->id) }}" class="bg-blue-600 text-white text-[8px] p-1 rounded block mb-1 uppercase font-bold shadow-sm">Realisasi</a>
                                                                @else
                                                                    <span class="text-[8px] text-gray-400 italic">Input Langsung</span>
                                                                @endif
                                                            @endif
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
                                        <td colspan="{{ $isTargetFullyApproved ? 14 : 7 }}" class="p-2 font-black uppercase text-gray-900">Tugas Tambahan & Kreativitas</td>
                                    </tr>
                                    @foreach($tugasTambahan as $item)
                                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-200 tt-row">
                                            <td class="border-r border-gray-300 p-2 text-center align-top tt-num text-[9px] font-bold text-slate-400">{{ $nomor++ }}</td>
                                            <td class="border-r border-gray-300 p-2 align-top text-gray-900 whitespace-normal leading-tight font-medium tt-kegiatan text-left">
                                                <div class="flex flex-col text-left">
                                                    @php
                                                        $ttTitle = ($item->tugas_tambahan && $item->tugas_tambahan !== '...') ? $item->tugas_tambahan : ($item->kegiatan ?: '-');
                                                        $ttDetail = ($item->detail_tupoksi && $item->detail_tupoksi !== '-') ? $item->detail_tupoksi : '';
                                                    @endphp
                                                    <span class="text-[10px] font-bold text-gray-800 leading-tight">{{ $ttTitle }}</span>
                                                </div>
                                            </td>
                                            <td class="border-r border-gray-300 p-2 text-center align-top text-blue-700 font-bold tt-ak text-[9px]">{{ number_format($item->ak > 0 ? $item->ak : 1, 2, ',', '.') }}</td>
                                            <td colspan="4" class="border-r border-gray-300 p-2 align-top text-gray-600 italic text-[9px] text-left">
                                                {{ $ttDetail ?: '-' }}
                                            </td>

                                            @if($isTargetFullyApproved)
                                                <td class="border-r border-gray-300 p-1 text-center align-middle bg-white text-emerald-700 font-bold border-l-2 border-l-gray-400 text-[9px]">
                                                    <input type="number" step="0.01" name="tugas_tambahan_realisasi[{{ $item->id }}][ak_realisasi]" 
                                                        value="{{ $item->realisasi_ak > 0 ? $item->realisasi_ak : $item->ak }}" 
                                                        class="w-full text-center text-[10px] border border-gray-300 rounded focus:ring-emerald-500 font-bold text-emerald-700"
                                                        {{ $isFormLocked || !$isTargetFullyApproved ? 'readonly' : '' }}>
                                                </td>
                                                
                                                {{-- Detail Realisasi (Menggantikan Kuantitas & Kualitas) --}}
                                                <td colspan="2" class="border-r border-gray-200 p-1 bg-white">
                                                    <textarea name="tugas_tambahan_realisasi[{{ $item->id }}][detail]" 
                                                        class="w-full text-[9px] border border-gray-300 rounded p-1 focus:ring-blue-500" 
                                                        placeholder="Sebutkan detail capaian..."
                                                        {{ $isFormLocked || !$isTargetFullyApproved ? 'readonly' : '' }}>{{ $item->realisasi_kegiatan }}</textarea>
                                                </td>

                                                {{-- Upload Bukti (Menggantikan Waktu) --}}
                                                <td class="border-r border-gray-200 p-1 bg-white text-center">
                                                    @if($item->realisasi_bukti)
                                                        <a href="{{ asset('storage/' . $item->realisasi_bukti) }}" target="_blank" class="text-blue-600 hover:underline text-[8px] font-bold block mb-1">
                                                            <i class="fas fa-file-alt"></i> Lihat Bukti
                                                        </a>
                                                    @endif
                                                    <input type="file" name="tugas_tambahan_realisasi[{{ $item->id }}][bukti]" 
                                                        class="w-full text-[8px] border border-gray-200 rounded file:bg-gray-100 file:border-none file:text-[8px] file:font-bold"
                                                        {{ $isFormLocked || !$isTargetFullyApproved ? 'disabled' : '' }}>
                                                </td>

                                                {{-- Input Biaya --}}
                                                <td class="border-r border-gray-200 p-1 bg-white">
                                                    <input type="number" name="tugas_tambahan_realisasi[{{ $item->id }}][biaya]" 
                                                        value="{{ $item->realisasi_biaya ?? 0 }}" 
                                                        class="w-full text-center text-[10px] border border-gray-300 rounded focus:ring-blue-500"
                                                        {{ $isFormLocked || !$isTargetFullyApproved ? 'readonly' : '' }}>
                                                </td>

                                                <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-semibold border-l border-gray-200 text-[9px]">{{ number_format($item->realisasi_ak > 0 ? $item->realisasi_ak : $item->ak, 2, ',', '.') }}</td>
                                                <td class="border-r border-gray-300 p-2 text-center align-middle text-indigo-700 font-bold text-[9px]">{{ number_format($item->realisasi_ak > 0 ? $item->realisasi_ak : $item->ak, 2, ',', '.') }}</td>
                                            @endif
                                            <td class="p-2 text-center bg-gray-50 align-middle">
                                                @if(!$isTargetFullyApproved || $isFormLocked)
                                                    <span class="inline-flex items-center justify-center w-full bg-gray-400 text-white text-[7px] font-black py-1 px-1 rounded-sm cursor-not-allowed uppercase tracking-tighter"><i class="fas fa-lock mr-1"></i> Locked</span>
                                                @else
                                                    <span class="text-[8px] text-gray-400 italic">Auto Synced</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-gray-300">
                                    <tr>
                                        <td colspan="{{ $isTargetFullyApproved ? 13 : 6 }}" class="border-r border-gray-300 p-3 text-right font-black text-sm uppercase text-gray-900 bg-gray-50">Nilai Capaian SKP</td>
                                        @if($isTargetFullyApproved)
                                            <td class="border-r border-gray-300 p-3 text-center font-black text-base align-middle bg-white text-indigo-700">{{ number_format($nilaiAkhirSkp ?? 0, 2, ',', '.') }}</td>
                                        @endif
                                        <td class="bg-gray-100 border-l border-gray-200"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                </div>

                {{-- SECTION B: PERILAKU --}}
                <div class="bg-white mb-8 mt-10 w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">B. Penyusunan Target Perilaku Kerja</h3>
                        </div>
                        <div class="w-full overflow-hidden rounded-xl border border-black shadow-sm">
                            <table class="w-full border-collapse text-[11px] table-skp-custom bg-white border-none">
                                <thead>
                                    <tr><th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white p-3">UNSUR YANG DINILAI</th><th class="text-center font-bold bg-white w-32 p-3">JUMLAH</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td :rowspan="perilakuCategories.length + 4" class="text-center font-bold align-middle w-12 bg-white text-base border-r border-gray-300 font-black">4.</td>
                                        <td colspan="2" class="font-bold bg-white p-3 border-r border-gray-300">a. Sasaran Kerja Pegawai (SKP)</td>
                                        <td class="text-center bg-white w-12 border-r border-gray-300" x-text="skp"></td>
                                        <td class="text-center bg-white w-24 border-r border-gray-300">x</td>
                                        <td class="text-center font-bold bg-white w-20 border-r border-gray-300">60%</td>
                                        <td class="text-center font-black bg-indigo-50 p-3 text-indigo-700 text-lg shadow-inner"><span x-text="calcSkp60"></span></td>
                                    </tr>
                                    <template x-for="(cat, catIndex) in perilakuCategories" :key="catIndex">
                                        <tr>
                                            <template x-if="catIndex === 0"><td :rowspan="perilakuCategories.length + 3" class="font-bold align-middle w-48 bg-white px-4 border-r border-gray-300 uppercase tracking-tighter">b. Perilaku Kerja</td></template>
                                            <td class="align-top bg-white border-r border-gray-300 p-0">
                                                <div class="flex justify-between items-center mb-1 cursor-pointer p-3" @click="openAspect === cat.name ? openAspect = null : openAspect = cat.name">
                                                    <span x-text="(catIndex + 1) + '. ' + cat.name" class="mt-1 text-[11px] font-black uppercase text-gray-800"></span>
                                                    <div class="text-gray-400 transition-transform duration-200" :class="openAspect === cat.name ? 'rotate-180' : ''"><i class="fas fa-chevron-down text-[8px]"></i></div>
                                                </div>
                                                <div class="flex flex-col gap-2 mt-2 px-3 pb-3" x-show="openAspect === cat.name">
                                                    <template x-for="(item, i) in perilaku[cat.name]" :key="i">
                                                        <div class="bg-white p-3 rounded-xl border border-gray-300 shadow-sm hover:shadow-md transition-all mb-2">
                                                            <div class="flex flex-row items-center gap-3 w-full">
                                                                <div class="flex items-center gap-2 flex-1">
                                                                    <div class="w-6 h-6 shrink-0 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black" x-text="i + 1"></div>
                                                                    <textarea x-model="item.indikator" :name="`perilaku[${cat.name}][${i}][indikator]`" 
                                                                        class="w-full text-[10px] border-gray-100 rounded-xl focus:ring-blue-500 p-2.5 bg-gray-50 font-medium" rows="2" placeholder="Nama Indikator..."
                                                                        :readonly="(isFinalized && !isTargetFullyApproved) || {{ $isFormLocked ? 'true' : 'false' }}"></textarea>
                                                                </div>
                                                                <div class="flex flex-col items-center shrink-0 w-24 gap-1">
                                                                    <label class="text-[8px] font-black text-blue-900 uppercase">Persentase</label>
                                                                    <div class="flex items-center bg-blue-50 px-2 py-1 rounded-lg border border-blue-100 w-full justify-center">
                                                                        <input type="number" x-model.number="item.bobot_persen" :name="`perilaku[${cat.name}][${i}][bobot_persen]`"
                                                                            class="w-12 h-6 text-center text-[10px] font-bold border-gray-300 rounded focus:ring-blue-500 p-1"
                                                                            min="0" max="100" step="1"
                                                                            :readonly="isFinalized || {{ $isFormLocked ? 'true' : 'false' }}"
                                                                            @input="item.bobot_persen = Math.round(item.bobot_persen); let sum = 0; perilaku[cat.name].forEach(p => sum += parseInt(p.bobot_persen) || 0); if(sum > 100) { alert('Total persentase tidak boleh lebih dari 100%'); item.bobot_persen = 100 - (sum - (parseInt(item.bobot_persen) || 0)); }">
                                                                        <span class="text-[9px] font-bold text-blue-800 ml-1">%</span>
                                                                    </div>
                                                                </div>
                                                                <div class="flex flex-col items-center shrink-0 w-24 gap-1" x-show="isFinalized">
                                                                    <label class="text-[8px] font-black text-gray-400 uppercase">Nilai (0-100)</label>
                                                                    <input type="number" x-model.number="item.nilai" :name="`perilaku[${cat.name}][${i}][nilai]`" step="0.01" max="100"
                                                                        @input="if(item.nilai > 100) item.nilai = 100"
                                                                        class="w-full text-center py-1 h-8 text-[11px] font-black border-blue-100 rounded-lg bg-white text-blue-700 shadow-inner" 
                                                                        :readonly="(isFinalized && !isTargetFullyApproved) || {{ $isFormLocked ? 'true' : 'false' }}">
                                                                </div>
                                                                <button type="button" @click="removeIndikator(cat.name, i)" 
                                                                    x-show="!isFinalized"
                                                                    class="text-red-400 hover:text-red-600 shrink-0 ml-1">
                                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                                </button>
                                                            </div>
                                                            <input type="hidden" :name="`perilaku[${cat.name}][${i}][id]`" x-model="item.id">
                                                            <input type="hidden" :name="`perilaku[${cat.name}][${i}][is_from_admin]`" x-model="item.is_from_admin">
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="addIndikator(cat.name)" 
                                                        x-show="!isFinalized"
                                                        class="bg-blue-600 text-white text-[9px] px-3 py-1.5 rounded-lg font-black hover:bg-blue-700 shadow-lg uppercase transition-all active:scale-95">
                                                        <i class="fas fa-plus mr-1"></i> Tambah Indikator
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle bg-white border-r border-gray-300 font-black text-base" x-text="getAvgAspek(cat.name) || '-'"></td>
                                            <td colspan="2" class="text-center align-middle bg-white border-r border-gray-300">-</td>
                                            <td class="bg-grey-custom border-l border-black"></td>
                                        </tr>
                                    </template>
                                    <tr><td class="bg-white p-3 font-bold border-r border-gray-300">7. Jumlah</td><td class="text-center align-middle bg-white p-3 font-black text-base text-gray-700 border-r border-gray-300" x-text="sumPerilaku"></td><td colspan="2" class="text-center align-middle bg-white p-3 text-gray-400 font-bold border-r border-gray-300">-</td><td class="bg-grey-custom border-l border-black"></td></tr>
                                    <tr><td class="bg-white p-3 font-bold border-r border-gray-300">8. Nilai rata – rata</td><td class="text-center align-middle bg-white p-3 font-black text-base text-gray-700 border-r border-gray-300" x-text="avgPerilaku"></td><td colspan="2" class="text-center align-middle bg-white p-3 text-gray-400 font-bold border-r border-gray-300">-</td><td class="bg-grey-custom border-l border-black"></td></tr>
                                    <tr><td class="font-bold bg-white p-3 text-indigo-900 border-r border-gray-300">9. Nilai Perilaku Kerja</td><td class="text-center align-middle bg-white p-3 font-black text-base text-indigo-700 border-r border-gray-300" x-text="avgPerilaku"></td><td class="text-center align-middle bg-white p-3 font-bold text-gray-400 text-base border-r border-gray-300">x</td><td class="text-center font-bold align-middle bg-white p-3 text-indigo-900 text-base border-r border-gray-300">40%</td><td class="text-center font-black bg-indigo-50 p-3 text-indigo-700 text-lg shadow-inner border-l border-gray-300" x-text="calcPerilaku40"></td></tr>
                                    <tr class="bg-indigo-700 text-white">
                                        <td colspan="6" class="border border-indigo-800 p-6 text-center font-black text-xl uppercase tracking-[0.3em]">NILAI PRESTASI KERJA</td>
                                        <td class="border border-indigo-800 p-3 text-center bg-indigo-800 align-middle shadow-2xl">
                                            <div class="font-black text-3xl mb-1" x-text="finalScore"></div>
                                            <div class="font-black text-[10px] uppercase tracking-widest text-indigo-100" x-text="getPredicateFinal(finalScore)"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(!$isFinalized)
                        <div class="flex justify-center mt-4 mb-8">
                            <button type="submit" class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold py-2.5 px-16 rounded-xl text-xs shadow-sm transition transform hover:scale-105 uppercase tracking-wider">
                                <i class="fas fa-save mr-2"></i> Simpan Perubahan Draft
                            </button>
                        </div>
                    @endif

            </form>

            {{-- MODAL EDIT TARGET DETAIL --}}
            <div x-show="editTargetModal" 
                 class="fixed inset-0 z-[9999] overflow-y-auto"
                 style="display: none;"
                 x-cloak>
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="editTargetModal = false"></div>
                    <div class="inline-block w-full max-w-4xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle"
                         @click.away="editTargetModal = false">
                        <form action="{{ route('pegawai.skp-dosen.update-target-detail') }}" method="POST">
                            @csrf
                            <input type="hidden" name="skp_id" :value="editSkp.id">
                            <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <div class="flex flex-col text-left">
                                    <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest">Form Perubahan Rencana Kerja</span>
                                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-tighter" x-text="editSkp.kegiatan"></h3>
                                </div>
                                <button type="button" @click="editTargetModal = false" class="w-8 h-8 rounded-full bg-white text-gray-400 hover:text-red-500 transition-all flex items-center justify-center shadow-sm">
                                    <i class="fas fa-times text-sm"></i>
                                </button>
                            </div>
                            <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                                <template x-if="editSkp.jenis === 'melaksanakan_perkuliahan'">
                                    <div class="space-y-6">
                                        <template x-for="sem in ['genap', 'gasal']" :key="sem">
                                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center text-left">
                                                    <span class="font-black text-[9px] uppercase tracking-widest text-gray-500" x-text="'Semester ' + sem"></span>
                                                    <button type="button" @click="addMatkul(sem)" class="bg-blue-600 text-white text-[8px] px-3 py-1 rounded-lg font-black uppercase shadow-lg shadow-blue-100 hover:bg-blue-700 transition">+ Tambah Matkul</button>
                                                </div>
                                                <div class="overflow-x-auto text-left">
                                                    <table class="w-full text-[10px]">
                                                        <thead class="bg-gray-50 border-b border-gray-200">
                                                            <tr>
                                                                <th class="p-3 text-left font-black uppercase text-gray-400 text-[8px]">Mata Kuliah (Auto)</th>
                                                                <th class="p-3 w-24 text-center font-black uppercase text-gray-400 text-[8px]">SKS</th>
                                                                <th class="p-3 w-10"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="(m, idx) in editSkp.detail_kegiatan" :key="idx">
                                                                <template x-if="m.semester === sem">
                                                                    <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                                                                        <td class="p-2">
                                                                            <input type="text" x-model="m.mata_kuliah" :name="`detail[${idx}][mata_kuliah]`" readonly class="w-full border-gray-100 bg-gray-50 rounded-lg text-[10px] font-bold text-gray-500 cursor-not-allowed px-2" placeholder="Nama Mata Kuliah...">
                                                                        </td>
                                                                        <td class="p-2">
                                                                            <input type="number" x-model.number="m.sks" @input="calculateEditAK()" :name="`detail[${idx}][sks]`" class="w-full text-center border-gray-200 rounded-lg text-[10px] font-bold text-blue-700">
                                                                        </td>
                                                                        <td class="p-2 text-center">
                                                                            <button type="button" @click="removeDetail(idx)" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                                                        </td>
                                                                        <input type="hidden" :name="`detail[${idx}][jumlah_kelas]`" value="1"><input type="hidden" :name="`detail[${idx}][dosen_pengampu]`" value="1"><input type="hidden" :name="`detail[${idx}][semester]`" :value="sem">
                                                                    </tr>
                                                                </template>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'membimbing_seminar'">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="p-4 bg-orange-50 rounded-xl border border-orange-100 shadow-sm text-left font-black">
                                                <label class="block text-[10px] font-black uppercase text-orange-800 mb-2">Semester Genap</label>
                                                <div class="flex items-center gap-3"><span class="text-[9px] font-bold text-orange-600 uppercase">Jumlah Mahasiswa</span><input type="number" x-model.number="editSkp.detail_kegiatan.genap.jumlah_mahasiswa" @input="calculateEditAK()" class="w-20 text-center border-orange-200 rounded-lg text-sm font-black text-orange-700 focus:ring-orange-500"></div>
                                                <input type="hidden" name="detail[genap][jumlah_mahasiswa]" :value="editSkp.detail_kegiatan.genap.jumlah_mahasiswa">
                                            </div>
                                            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 shadow-sm text-left font-black">
                                                <label class="block text-[10px] font-black uppercase text-emerald-800 mb-2">Semester Gasal</label>
                                                <div class="flex items-center gap-3"><span class="text-[9px] font-bold text-emerald-600 uppercase">Jumlah Mahasiswa</span><input type="number" x-model.number="editSkp.detail_kegiatan.gasal.jumlah_mahasiswa" @input="calculateEditAK()" class="w-20 text-center border-emerald-200 rounded-lg text-sm font-black text-emerald-700 focus:ring-emerald-500"></div>
                                                <input type="hidden" name="detail[gasal][jumlah_mahasiswa]" :value="editSkp.detail_kegiatan.gasal.jumlah_mahasiswa">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'membimbing_kkn_pkl'">
                                    <div class="space-y-4">
                                        <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
                                            <table class="w-full text-[10px]">
                                                <thead class="bg-gray-50 border-b border-gray-200"><tr class="font-black uppercase text-gray-400 text-[8px]"><th class="p-3 text-left">Kegiatan</th><th class="p-3 w-32 text-center">Semester Genap</th><th class="p-3 w-32 text-center">Semester Gasal</th></tr></thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    <template x-for="keg in ['KKN', 'PKN', 'PKL']" :key="keg">
                                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                                            <td class="p-3 font-black text-gray-700" x-text="keg"></td>
                                                            <td class="p-2"><div class="flex flex-col gap-1"><span class="text-[7px] font-black text-gray-400 uppercase text-center">Mhs</span><input type="number" x-model.number="editSkp.detail_kegiatan[keg].genap" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-blue-700 bg-blue-50/30"><input type="hidden" :name="`detail[${keg}][genap]`" :value="editSkp.detail_kegiatan[keg].genap"></div></td>
                                                            <td class="p-2"><div class="flex flex-col gap-1"><span class="text-[7px] font-black text-gray-400 uppercase text-center">Mhs</span><input type="number" x-model.number="editSkp.detail_kegiatan[keg].gasal" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-emerald-700 bg-emerald-50/30"><input type="hidden" :name="`detail[${keg}][gasal]`" :value="editSkp.detail_kegiatan[keg].gasal"></div></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'membimbing_skripsi_thesis_disertasi'">
                                    <div class="space-y-6">
                                        <template x-for="keg in ['Skripsi', 'Tesis', 'Disertasi']" :key="keg">
                                            <div class="border border-yellow-200 rounded-xl overflow-hidden shadow-sm">
                                                <div class="bg-yellow-50 px-4 py-2 border-b border-yellow-200 flex justify-between items-center text-left"><span class="font-black text-[10px] uppercase tracking-widest text-yellow-800" x-text="keg"></span><span class="text-[8px] font-bold text-yellow-600 italic uppercase" x-text="'*Setiap ' + (keg === 'Skripsi' ? 6 : (keg === 'Tesis' ? 3 : 2)) + ' mhs = 1 SKS'"></span></div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 divide-x divide-yellow-100">
                                                    <template x-for="sem in ['genap', 'gasal']" :key="sem">
                                                        <div class="p-4 space-y-3">
                                                            <div class="bg-gray-100 p-1.5 rounded-lg text-center font-black text-[9px] uppercase text-gray-600 tracking-tighter" x-text="'Semester ' + sem"></div>
                                                            <div class="space-y-3">
                                                                <div><label class="block text-[8px] font-black text-gray-400 uppercase mb-1">Pembimbing Utama (Mhs)</label><input type="number" x-model.number="editSkp.detail_kegiatan[keg][sem].utama" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-blue-700 bg-gray-50/50"><input type="hidden" :name="`detail[${keg}][${sem}][utama]`" :value="editSkp.detail_kegiatan[keg][sem].utama"></div>
                                                                <div><label class="block text-[8px] font-black text-gray-400 uppercase mb-1">Pembimbing Penyerta (Mhs)</label><input type="number" x-model.number="editSkp.detail_kegiatan[keg][sem].penyerta" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-emerald-700 bg-gray-50/50"><input type="hidden" :name="`detail[${keg}][${sem}][penyerta]`" :value="editSkp.detail_kegiatan[keg][sem].penyerta"></div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'penguji_ujian_akhir'">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4 text-left">
                                            <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 shadow-sm text-left font-black">
                                                <label class="block text-[10px] font-black uppercase text-blue-800 mb-3 text-left">Semester Genap</label>
                                                <div class="space-y-3">
                                                    <div><span class="text-[8px] font-black text-blue-400 uppercase mb-1 block text-left">Ketua Penguji (Mhs)</span><input type="number" x-model.number="editSkp.detail_kegiatan.genap.ketua" @input="calculateEditAK()" class="w-full text-center border-blue-200 rounded-lg text-sm font-black text-blue-700 bg-white"><input type="hidden" name="detail[genap][ketua]" :value="editSkp.detail_kegiatan.genap.ketua"></div>
                                                    <div><span class="text-[8px] font-black text-blue-400 uppercase mb-1 block text-left">Anggota Penguji (Mhs)</span><input type="number" x-model.number="editSkp.detail_kegiatan.genap.anggota" @input="calculateEditAK()" class="w-full text-center border-blue-200 rounded-lg text-sm font-black text-blue-700 bg-white"><input type="hidden" name="detail[genap][anggota]" :value="editSkp.detail_kegiatan.genap.anggota"></div>
                                                </div>
                                            </div>
                                            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 shadow-sm text-left font-black">
                                                <label class="block text-[10px] font-black uppercase text-emerald-800 mb-3 text-left">Semester Gasal</label>
                                                <div class="space-y-3">
                                                    <div><span class="text-[8px] font-black text-emerald-400 uppercase mb-1 block text-left">Ketua Penguji (Mhs)</span><input type="number" x-model.number="editSkp.detail_kegiatan.gasal.ketua" @input="calculateEditAK()" class="w-full text-center border-blue-200 rounded-lg text-sm font-black text-emerald-700 bg-white"><input type="hidden" name="detail[gasal][ketua]" :value="editSkp.detail_kegiatan.gasal.ketua"></div>
                                                    <div><span class="text-[8px] font-black text-emerald-400 uppercase mb-1 block text-left">Anggota Penguji (Mhs)</span><input type="number" x-model.number="editSkp.detail_kegiatan.gasal.anggota" @input="calculateEditAK()" class="w-full text-center border-blue-200 rounded-lg text-sm font-black text-emerald-700 bg-white"><input type="hidden" name="detail[gasal][anggota]" :value="editSkp.detail_kegiatan.gasal.anggota"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'membina_kemahasiswaan'">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center bg-indigo-50 p-3 rounded-xl border border-indigo-100 text-left">
                                            <span class="text-[9px] font-black uppercase text-indigo-700 tracking-widest text-left">Rincian Kegiatan Pembinaan</span>
                                            <button type="button" @click="addIndikatorKemahasiswaan()" class="bg-indigo-600 text-white text-[8px] px-3 py-1 rounded-lg font-black uppercase shadow-lg shadow-indigo-100">+ Tambah Baris</button>
                                        </div>
                                        <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
                                            <table class="w-full text-[10px]">
                                                <thead class="bg-gray-50 border-b border-gray-200 font-black"><tr class="font-black uppercase text-gray-400 text-[8px]"><th class="p-3 text-left">Kegiatan</th><th class="p-3 w-32 text-center text-blue-600">Semester Genap</th><th class="p-3 w-32 text-center text-emerald-600">Semester Gasal</th><th class="p-3 w-10"></th></tr></thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    <template x-for="(item, i) in editSkp.detail_kegiatan" :key="i">
                                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                                            <td class="p-2"><input type="text" x-model="item.nama_kegiatan" readonly class="w-full border-transparent bg-transparent text-[10px] font-black text-gray-700 cursor-not-allowed px-1" :name="`detail[${i}][nama_kegiatan]`"></td>
                                                            <td class="p-2"><input type="number" x-model.number="item.jumlah_genap" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-blue-700 bg-blue-50/30" :name="`detail[${i}][jumlah_genap]`"></td>
                                                            <td class="p-2"><input type="number" x-model.number="item.jumlah_gasal" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-emerald-700 bg-emerald-50/30" :name="`detail[${i}][jumlah_gasal]`"></td>
                                                            <td class="p-2 text-center"><button type="button" @click="removeDetail(i)" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i></button></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'mengembangkan_bahan_kuliah'">
                                    <div class="space-y-6">
                                        <template x-for="sem in ['Genap', 'Gasal']" :key="sem">
                                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm text-left">
                                                <div class="bg-cyan-50 px-4 py-2 border-b border-cyan-100 font-black text-[10px] text-cyan-800 uppercase text-left" x-text="'Semester ' + sem"></div>
                                                <table class="w-full text-[10px]">
                                                    <thead class="bg-gray-50 border-b border-gray-200 font-black text-gray-400 uppercase text-[8px]"><tr><th class="p-3 text-left">Jenis Bahan</th><th class="p-3 w-20 text-center">Tarif</th><th class="p-3 w-24 text-center">Jumlah</th></tr></thead>
                                                    <tbody>
                                                        @foreach($masterBahanKuliah as $keg => $ak)
                                                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50"><td class="p-2 font-bold text-gray-700 text-left">{{ $keg }}</td><td class="p-2 text-center text-gray-500 font-bold">{{ $ak }}</td><td class="p-2"><input type="number" :name="`detail[${sem}][{{ $keg }}]`" x-model.number="editSkp.detail_kegiatan[sem] ? editSkp.detail_kegiatan[sem]['{{ $keg }}'] : 0" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-[10px] font-bold text-blue-700"></td></tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'karya_ilmiah'">
                                    <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm text-left">
                                        <table class="w-full text-[10px]">
                                            <thead class="bg-gray-50 border-b border-gray-200 font-black text-gray-400 uppercase text-[8px]"><tr><th class="p-3 text-left">Jenis Karya Ilmiah</th><th class="p-3 w-20 text-center">Tarif</th><th class="p-3 w-24 text-center">Jumlah</th></tr></thead>
                                            <tbody>
                                                @foreach($masterKaryaIlmiah as $keg => $ak)
                                                    <tr class="border-b border-gray-100 last:border-0 hover:bg-pink-50/10"><td class="p-2 font-bold text-gray-700 text-left">{{ $keg }}</td><td class="p-2 text-center text-gray-500 font-bold">{{ $ak }}</td><td class="p-2"><input type="number" :name="`detail[{{ $keg }}]`" x-model.number="editSkp.detail_kegiatan['{{ $keg }}']" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-[10px] font-bold text-blue-700"></td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'pengabdian_kepada_masyarakat'">
                                    <div class="space-y-6">
                                        @foreach($masterAbdimas as $code => $group)
                                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm text-left">
                                                <div class="bg-red-50 px-4 py-2 border-b border-red-100 font-black text-[10px] text-red-800 uppercase text-left">{{ $code }}. {{ $group['title'] }}</div>
                                                <table class="w-full text-[10px]">
                                                    <thead class="bg-gray-50 border-b border-gray-200 font-black text-gray-400 uppercase text-[8px]"><tr><th class="p-3 text-left">Keterangan</th><th class="p-3 w-20 text-center">Tarif</th><th class="p-3 w-24 text-center">Jumlah</th></tr></thead>
                                                    <tbody>
                                                        @foreach($group['items'] as $keg => $ak)
                                                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50"><td class="p-2 font-bold text-gray-700 text-left">{{ $keg }}</td><td class="p-2 text-center text-gray-500 font-bold">{{ $ak }}</td><td class="p-2"><input type="number" :name="`detail[{{ $code }}][{{ $keg }}]`" x-model.number="editSkp.detail_kegiatan['{{ $code }}'] ? editSkp.detail_kegiatan['{{ $code }}']['{{ $keg }}'] : 0" @input="calculateEditAK()" class="w-full text-center border-gray-200 rounded-lg text-[10px] font-bold text-blue-700"></td></tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'menduduki_jabatan_pimpinan'">
                                    <div class="space-y-6">
                                        <template x-for="sem in ['genap', 'gasal']" :key="sem">
                                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm text-left font-black">
                                                <div class="bg-slate-700 text-white px-4 py-2 border-b border-slate-600 flex justify-between items-center text-left font-black"><span class="font-black text-[10px] uppercase tracking-widest text-left" x-text="'Semester ' + sem"></span><div class="flex items-center gap-2"><span class="text-[8px] italic opacity-75 uppercase">Kode Jabatan:</span><input type="number" :name="`detail[kode_${sem}]`" x-model.number="editSkp.detail_kegiatan['kode_'+sem]" @input="calculateEditAK()" class="w-12 text-center border-none rounded bg-white text-gray-900 text-[10px] font-black h-6"></div></div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <table class="w-full text-[10px]">
                                                        <thead class="sticky top-0 bg-slate-500 text-white font-black uppercase text-[8px]"><tr><th class="p-2 w-12 text-center border border-slate-400">Kode</th><th class="p-2 text-left border border-slate-400">Jabatan</th><th class="p-2 w-16 text-center border border-slate-400">AK</th></tr></thead>
                                                        <tbody>
                                                            @foreach($masterJabatan as $code => $data)
                                                                <tr class="border-b border-gray-100 last:border-0 hover:bg-yellow-50/50" :class="editSkp.detail_kegiatan['kode_'+sem] == {{ $code }} ? 'bg-yellow-100/50' : ''"><td class="p-2 text-center font-bold text-gray-400 border-r border-gray-100">{{ $code }}</td><td class="p-2 font-bold text-gray-700 text-left">{{ $data['nama'] }}</td><td class="p-2 text-center font-black text-emerald-600 border-l border-gray-100">{{ $data['ak'] }}</td></tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="editSkp.jenis === 'unsur_penunjang'">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded-xl border border-gray-200 text-left">
                                            <span class="text-[9px] font-black uppercase text-gray-500 tracking-widest text-left">Rincian Kegiatan Penunjang</span>
                                            <button type="button" @click="addPenunjang()" class="bg-slate-600 text-white text-[8px] px-3 py-1 rounded-lg font-black uppercase shadow-lg">+ Tambah Baris</button>
                                        </div>
                                        <div class="overflow-hidden border border-gray-200 rounded-xl text-left">
                                            <table class="w-full text-[10px]">
                                                <thead class="bg-gray-50 border-b border-gray-200 font-black uppercase text-gray-400 text-[8px]"><tr><th class="p-3 text-left font-black">Nama Kegiatan</th><th class="p-3 w-24 text-center font-black">AK</th><th class="p-3 w-10"></th></tr></thead>
                                                <tbody>
                                                    <template x-for="(item, i) in editSkp.detail_kegiatan" :key="i">
                                                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/30 transition-colors">
                                                            <td class="p-2"><input type="text" x-model="item.nama_kegiatan" :name="`detail[${i}][nama_kegiatan]`" readonly class="w-full border-transparent bg-transparent text-[10px] font-black text-gray-700 cursor-not-allowed px-1" placeholder="Contoh: Panitia Seminar..."></td>
                                                            <td class="p-2"><input type="number" step="0.01" x-model.number="item.ak" @input="calculateEditAK()" :name="`detail[${i}][ak]`" class="w-full text-center border-gray-200 rounded-lg text-sm font-black text-blue-700 bg-blue-50/30"></td>
                                                            <td class="p-2 text-center"><button type="button" @click="removeDetail(i)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt text-xs"></i></button></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>

                                {{-- SUMMARY TARGET FIELDS --}}
                                <div class="mt-8 grid grid-cols-2 md:grid-cols-5 gap-4 bg-blue-50 p-5 rounded-2xl border border-blue-100 shadow-inner text-left font-black">
                                    <div x-show="!['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" class="col-span-full mb-2 border-b border-blue-200 pb-1 flex justify-between items-center">
                                        <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-blue-600"></div><span class="text-[10px] font-black uppercase text-blue-900 tracking-widest text-left">Validasi Parameter Target SKP</span></div>
                                        <span class="text-[8px] text-blue-600 font-bold italic bg-white px-2 py-0.5 rounded-full border border-blue-100 shadow-sm uppercase tracking-tighter text-left"><i class="fas fa-info-circle mr-1"></i> Data di bawah ini akan tampil di tabel utama</span>
                                    </div>
                                    <div x-show="!['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" class="text-left">
                                        <label class="block text-[8px] font-black text-blue-800 uppercase mb-1 text-left">Angka Kredit (AK)</label>
                                        <input type="number" step="0.01" name="ak" x-model="editSkp.ak" :readonly="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" :class="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis) ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'" class="w-full border-blue-200 rounded-lg text-sm font-black text-center shadow-sm focus:ring-blue-500 text-blue-700">
                                    </div>
                                    <div x-show="!['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" class="text-left">
                                        <label class="block text-[8px] font-black text-blue-800 uppercase mb-1 text-left">Kuantitas/Output</label>
                                        <input type="number" name="kuantitas" x-model="editSkp.kuantitas_output" :readonly="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" :class="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis) ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'" class="w-full border-blue-200 rounded-lg text-sm font-black text-center shadow-sm focus:ring-blue-500">
                                    </div>
                                    <div x-show="!['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" class="text-left">
                                        <label class="block text-[8px] font-black text-blue-800 uppercase mb-1 text-left">Kualitas (%)</label>
                                        <input type="number" name="kualitas" x-model="editSkp.kualitas_mutu" class="w-full border-blue-200 rounded-lg text-sm font-black text-center bg-white shadow-sm focus:ring-blue-500">
                                    </div>
                                    <div x-show="!['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" class="text-left">
                                        <label class="block text-[8px] font-black text-blue-800 uppercase mb-1 text-left">Waktu (Bulan)</label>
                                        <input type="number" name="waktu" x-model="editSkp.waktu" :readonly="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis)" :class="['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl', 'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan', 'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat', 'menduduki_jabatan_pimpinan', 'unsur_penunjang'].includes(editSkp.jenis) ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'" class="w-full border-blue-200 rounded-lg text-sm font-black text-center shadow-sm focus:ring-blue-500">
                                    </div>
                                    <div class="text-left">
                                        <label class="block text-[8px] font-black text-blue-800 uppercase mb-1 text-left">Biaya (Rp)</label>
                                        <input type="number" name="biaya" x-model="editSkp.biaya" class="w-full border-blue-200 rounded-lg text-sm font-black text-center bg-white shadow-sm focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                                <button type="button" @click="editTargetModal = false" class="px-6 py-2.5 rounded-xl font-bold text-gray-500 uppercase text-[10px] hover:bg-gray-200 transition-all tracking-widest">Batal</button>
                                <button type="submit" class="px-10 py-2.5 bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] shadow-xl shadow-blue-200 hover:bg-blue-800 transition-all transform hover:scale-105 active:scale-95 tracking-widest">
                                    <i class="fas fa-check-circle mr-2"></i> Update Target Rencana Kerja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.addEventListener('wheel', function(event) { if (document.activeElement.type === 'number') { document.activeElement.blur(); } });
                let newRowIdx = 0; const tupoksiContainer = document.getElementById('tupoksi-container'); const btnAddTupoksi = document.getElementById('btn-add-tupoksi'); const emptyMsg = document.getElementById('empty-row-msg'); const availableBidang = @js($userBidangs ?? []); const penilaiRoles = @js($penilaiRoles ?? []); const userJabatan = @js($userJabatan ?? ''); const isFinalized = @js($isFinalized ?? false); const tugasTambahanBody = document.getElementById('tugas-tambahan-body');
                window.syncTupoksiToTugasTambahan = function(idx, data) {
                    let row = document.getElementById(`sync-tt-${idx}`); if (!row) { row = document.createElement('tr'); row.id = `sync-tt-${idx}`; row.className = "hover:bg-gray-50 transition-colors border-b border-gray-200 bg-blue-50/5 tt-row"; tugasTambahanBody.appendChild(row); }
                    row.innerHTML = `<td class='border-r p-2 text-center align-top text-blue-400 font-bold italic text-[9px]'>NEW</td><td class='border-r p-2 align-top text-gray-900 text-left font-bold'>${data.tupoksi || '...'}</td><td class='border-r p-2 text-center align-top text-blue-700 font-bold text-[9px]'>1.00</td><td colspan='4' class='border-r p-2 align-top text-gray-600 italic text-[9px] text-left'>${data.jenis_aktivitas || '-'}</td><td class='p-2 text-center bg-gray-50 align-middle'><span class='text-[8px] text-gray-400 italic'>Auto Synced</span></td>`;
                };
                window.fetchTupoksiByBidang = function(el) {
                    const tr = el.closest('tr'); const tupoksiSelect = tr.querySelector('.tupoksi-select'); const bidang = el.value; if (bidang === '') { tupoksiSelect.innerHTML = '<option value=\"\">-- Pilih Tupoksi --</option>'; return; }
                    tupoksiSelect.innerHTML = '<option value=\"\">Memuat...</option>'; fetch('/lookup/daftar-tupoksi/bidang/' + encodeURIComponent(bidang)).then(res => res.json()).then(data => { tupoksiSelect.innerHTML = '<option value=\"\">-- Pilih Tupoksi --</option>'; data.forEach(item => { const option = document.createElement('option'); option.value = item.tupoksi; option.text = item.tupoksi; option.dataset.full = JSON.stringify(item); tupoksiSelect.appendChild(option); }); });
                };
                window.fillTupoksiData = function(el) {
                    const tr = el.closest('tr'); const idx = tr.dataset.idx; const selectedOption = el.options[el.selectedIndex]; if (!selectedOption || !selectedOption.dataset.full) return;
                    try {
                        const data = JSON.parse(selectedOption.dataset.full); tr.querySelector('.jenis-textarea').value = data.jenis_aktivitas || '-'; tr.querySelector('.penilai-select').value = data.penilai_role || 'Dekan'; tr.querySelector('.freq-input').value = data.frekuensi_per_tahun || 1; tr.querySelector('.dur-input').value = data.durasi_menit || 0; tr.querySelector('.sdm-input').value = data.jumlah_sdm || 1; calcVolIndex(tr.querySelector('.freq-input'));
                    } catch (e) { console.error('Error parsing tupoksi data', e); }
                };
                window.calcVolIndex = function(el) {
                    const tr = el.closest('tr'); const freq = parseFloat(tr.querySelector('.freq-input').value) || 0; const dur = parseFloat(tr.querySelector('.dur-input').value) || 0; const sdm = parseFloat(tr.querySelector('.sdm-input').value) || 1; tr.querySelector('.vol-display').innerText = (freq * dur * sdm).toLocaleString('id-ID');
                    if (tr.querySelector('.tupoksi-select').value) { syncTupoksiToTugasTambahan(tr.dataset.idx, { role_name: tr.querySelector('.bidang-select').value, tupoksi: tr.querySelector('.tupoksi-select').value, jenis_aktivitas: tr.querySelector('.jenis-textarea').value || '-', sdm: sdm }); }
                };
                function reindexRows() { const rows = tupoksiContainer.querySelectorAll('tr:not(.hidden)'); rows.forEach((row, i) => { const numCell = row.querySelector('.row-num div') || row.querySelector('.row-num'); if (numCell) numCell.innerText = i + 1; }); if (rows.length > 0 && emptyMsg) emptyMsg.closest('tr').classList.add('hidden'); }
                window.removeNewRow = function(el) { const tr = el.closest('tr'); const idx = tr.dataset.idx; tr.remove(); const syncRow = document.getElementById(`sync-tt-${idx}`); if (syncRow) syncRow.remove(); reindexRows(); };
                if (btnAddTupoksi) {
                    btnAddTupoksi.addEventListener('click', function() {
                        const idx = newRowIdx++; const tr = document.createElement('tr'); tr.className = 'hover:bg-blue-50 transition-colors border-t border-gray-200 bg-blue-50/10'; tr.dataset.idx = idx;
                        let bidangOpts = '<option value=\"\">-- Pilih Bidang --</option>'; availableBidang.forEach(b => { bidangOpts += `<option value=\"${b}\" ${b === userJabatan ? 'selected' : ''}>${b}</option>`; });
                        let penilaiOpts = '<option value=\"\">-- Pilih Penilai --</option>'; penilaiRoles.forEach(r => { penilaiOpts += `<option value=\"${r.name || r}\">${r.name || r}</option>`; });
                        tr.innerHTML = `<td class='text-center text-[9px] font-bold text-blue-400 pt-3 border-r row-num'>${tupoksiContainer.querySelectorAll('tr').length + 1}</td><td class='border-r p-1'><select name='tupoksi_new[${idx}][role_name]' required onchange='fetchTupoksiByBidang(this)' class='table-input font-semibold text-blue-800 uppercase bg-white border border-blue-100 rounded bidang-select text-[9px]'>${bidangOpts}</select></td><td class='border-r p-1'><select name='tupoksi_new[${idx}][tupoksi]' required class='table-input tupoksi-select text-[8px] bg-blue-50 border border-blue-100 rounded' onchange='fillTupoksiData(this)'><option value=''>-- Pilih Tupoksi --</option></select></td><td class='border-r p-1'><textarea name='tupoksi_new[${idx}][jenis_aktivitas]' class='table-input text-gray-700 italic bg-white border border-blue-100 rounded jenis-textarea text-[9px]' placeholder='Jenis Aktivitas...'></textarea></td><td class='border-r p-1'><select name='tupoksi_new[${idx}][penilai_role]' required class='table-input font-semibold text-emerald-700 bg-white border border-blue-100 rounded penilai-select text-[9px]'>${penilaiRoles.map(r=>`<option value=\"${r.name||r}\">${r.name||r}</option>`).join('')}</select></td><td class='checkbox-cell border-r text-center align-middle'><input type='checkbox' name='tupoksi_new[${idx}][kategori][]' value='Utama' checked class='w-3 h-3 text-blue-600 rounded'></td><td class='checkbox-cell border-r text-center align-middle'><input type='checkbox' name='tupoksi_new[${idx}][kategori][]' value='Periodik' class='w-3 h-3 text-blue-600 rounded'></td><td class='checkbox-cell border-r text-center align-middle'><input type='checkbox' name='tupoksi_new[${idx}][kategori][]' value='Bukan Tupoksi' class='w-3 h-3 text-blue-600 rounded'></td><td class='border-r p-1'><select name='tupoksi_new[${idx}][periode]' class='table-input center uppercase font-bold text-gray-500 bg-white border border-blue-100 rounded text-[9px]'><option value='Tahunan'>Tahunan</option><option value='Semester'>Semester</option><option value='Bulanan'>Bulanan</option></select></td><td class='border-r p-1'><input type='number' name='tupoksi_new[${idx}][freq]' value='1' class='table-input center font-bold bg-white border border-blue-100 rounded freq-input text-[9px]' oninput='calcVolIndex(this)'></td><td class='border-r p-1'><input type='number' name='tupoksi_new[${idx}][dur]' value='0' class='table-input center font-bold bg-white border border-blue-100 rounded dur-input text-[9px]' oninput='calcVolIndex(this)'></td><td class='border-r p-1'><input type='number' name='tupoksi_new[${idx}][sdm]' value='1' class='table-input center font-bold bg-white border border-blue-100 rounded sdm-input text-[9px]' oninput='calcVolIndex(this)'></td><td class='bg-blue-50 border-r'><div class='display-cell right font-black text-blue-700 vol-display text-[9px]'>0</div></td><td class='p-2 text-center align-middle'><button type='button' onclick='removeNewRow(this)' class='text-red-500 hover:text-red-700 transition'><i class='fas fa-trash-alt'></i></button></td>`;
                        tupoksiContainer.appendChild(tr); if (tr.querySelector('.bidang-select').value) fetchTupoksiByBidang(tr.querySelector('.bidang-select')); syncTupoksiToTugasTambahan(idx, { role_name: userJabatan, tupoksi: '', sdm: 1, ak: 0 }); reindexRows();
                    });
                }
            });
        </script>
    @endsection
</x-app-layout>
