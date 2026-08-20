<x-app-layout>
    @section('page-title', 'Detail SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let evaluasiChartInstance = null;

        window.addEventListener('beforeprint', function () {
            setTimeout(function () {
                const canvas = document.getElementById('evaluasiChartCanvas');
                const img = document.getElementById('evaluasiChartPrintImg');
                if (evaluasiChartInstance && canvas && img) {
                    img.src = canvas.toDataURL('image/png', 1.0);
                    canvas.style.display = 'none';
                    img.style.display = 'block';
                }
            }, 100);
        });

        window.addEventListener('afterprint', function () {
            const canvas = document.getElementById('evaluasiChartCanvas');
            const img = document.getElementById('evaluasiChartPrintImg');
            if (canvas && img) {
                img.style.display = 'none';
                canvas.style.display = 'block';
            }
        });
        
        function evaluasiData() {
            return {
                activeTab: 'skp',
                capaianKinerja: '{{ $skpKualitatif->skpHeader->capaian_kinerja_organisasi ?? '' }}',
                predikatKinerjaTersimpan: '{{ $skpKualitatif->skpHeader->predikat_kinerja ?? '' }}',
                distribusiNilai: @json($distribusiNilai ?? []),
                state: { utamaScores: {}, perilakuScores: {} },
                
                get rating() { 
                    let uVals = Object.values(this.state.utamaScores).filter(v => v !== null && v !== undefined);
                    if (uVals.length === 0) return '';
                    let sum = uVals.reduce((a, b) => a + b, 0);
                    let avg = sum / uVals.length;
                    if (avg >= 2.5) return 'DIATAS EKSPEKTASI'; 
                    else if (avg >= 1.5) return 'SESUAI EKSPEKTASI'; 
                    else return 'DIBAWAH EKSPEKTASI'; 
                }, 
                get ratingPerilaku() { 
                    let scores = Object.values(this.state.perilakuScores); 
                    let validScores = scores.filter(s => s !== undefined && s !== null && s !== '');
                    if (validScores.length === 0) return ''; 
                    let avg = validScores.reduce((a,b)=>a+b, 0) / validScores.length; 
                    if (avg >= 2.5) return 'DIATAS EKSPEKTASI'; 
                    else if (avg >= 1.5) return 'SESUAI EKSPEKTASI'; 
                    else return 'DIBAWAH EKSPEKTASI'; 
                }, 
                get predikatKinerja() { 
                    let h = this.rating; 
                    let p = this.ratingPerilaku; 
                    if (!h || !p) return ''; 
                    if (h === 'DIATAS EKSPEKTASI') { 
                        if (p === 'DIATAS EKSPEKTASI') return 'SANGAT BAIK'; 
                        if (p === 'SESUAI EKSPEKTASI') return 'BAIK'; 
                        return 'KURANG'; 
                    } else if (h === 'SESUAI EKSPEKTASI') { 
                        if (p === 'DIBAWAH EKSPEKTASI') return 'BUTUH PERBAIKAN'; 
                        return 'BAIK'; 
                    } else { 
                        if (p === 'DIATAS EKSPEKTASI') return 'BUTUH PERBAIKAN'; 
                        if (p === 'SESUAI EKSPEKTASI') return 'KURANG'; 
                        return 'SANGAT KURANG'; 
                    } 
                },
                
                init() {
                    this.$watch('activeTab', value => {
                        if (value === 'evaluasi') {
                            this.$nextTick(() => {
                                this.renderChart();
                            });
                        }
                    });
                    
                    this.$watch('capaianKinerja', () => {
                        if (this.activeTab === 'evaluasi') {
                            this.renderChart();
                        }
                    });
                },
                
                renderChart() {
                    const canvas = document.getElementById('evaluasiChartCanvas');
                    if (!canvas || !this.capaianKinerja) return;
                    
                    const ctx = canvas.getContext('2d');
                    
                    if (evaluasiChartInstance) {
                        evaluasiChartInstance.destroy();
                    }
                    
                    const data = this.distribusiNilai[this.capaianKinerja] || [0,0,0,0,0];
                    
                    evaluasiChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Sangat Kurang', 'Kurang/Misconduct', 'Butuh Perbaikan', 'Baik', 'Sangat Baik'],
                            datasets: [{
                                label: 'Pola Distribusi (' + this.capaianKinerja + ')',
                                data: data,
                                borderColor: 'rgba(99, 102, 241, 1)',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { borderDash: [2, 4] }
                                },
                                x: {
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
        <div class="p-6" x-data="evaluasiData()">
            <div class="flex justify-between items-center mb-6 no-print">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Target SKP Dosen Kualitatif</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pegawai.skp-dosen-kualitatif.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>


            <div class="flex space-x-1 mb-4 border-b border-gray-200 no-print">
                <button type="button" @click="activeTab = 'skp'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'skp', 'text-gray-500 hover:text-gray-700': activeTab !== 'skp'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                    SKP Pegawai
                </button>
                <button type="button" @click="activeTab = 'lampiran'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'lampiran', 'text-gray-500 hover:text-gray-700': activeTab !== 'lampiran'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                    Lampiran SKP
                </button>
                <button type="button" @click="activeTab = 'evaluasi'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'evaluasi', 'text-gray-500 hover:text-gray-700': activeTab !== 'evaluasi'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                    Evaluasi Pegawai
                </button>
                <button type="button" @click="activeTab = 'dokumen'" :class="{'border-b-2 border-blue-500 text-blue-600': activeTab === 'dokumen', 'text-gray-500 hover:text-gray-700': activeTab !== 'dokumen'}" class="px-4 py-2 font-bold text-sm uppercase tracking-wide">
                    Dokumen Evaluasi
                </button>
            </div>
            <style>
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
                .bg-light-gray {
                    background-color: #f3f4f6;
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

                </style>

            <div x-show="activeTab === 'skp'" class="area-cetak overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <div class="text-center font-bold mb-6 space-y-1">
                    <p>SASARAN KINERJA PEGAWAI</p>
                    <p>PENDEKATAN HASIL KERJA KUALITATIF</p>
                    <p>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>
                </div>
                <!-- IDENTITAS -->
                <table class="table-kualitatif mb-6">
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
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $user->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/GOL. RUANG</span> <span>{{ $user->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/ GOL. RUANG</span> <span>{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
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

                        {{-- A. UTAMA --}}
                        <tr>
                            <td class="text-center font-bold w-12 align-top">1</td>
                            <td colspan="3" class="align-top italic text-gray-800">
                                {{ $skpKualitatif->rencana_hasil_kerja ?? 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan...' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center w-12 border-t-0"></td>
                            <td colspan="3">
                                <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                                <div class="whitespace-pre-wrap">{{ $skpKualitatif->indikator_target }}</div>
                            </td>
                        </tr>

                        <!-- Baris Tambahan Default A. Utama -->
                        <tr>
                            <td class="text-center font-bold w-12 align-top">2</td>
                            <td colspan="3" class="text-gray-800 align-top italic">
                                Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi<br>
                                (Penugasan dari Rektor)
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center w-12 border-t-0"></td>
                            <td colspan="3" class="text-gray-800">
                                <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                                <div class="whitespace-pre-wrap">- Terlaksananya  tugas kepemimpinan dengan baik
- Terlaksananya pembinaan dosen, mahasiswa dan tenaga kependidikan dengan baik
- Meningkatnya persentase prodi yang unggul
- Meningkatnya mutu pembelajaran, penelitian dan pengabdian masyarakat</div>
                            </td>
                        </tr>

                        {{-- B. TAMBAHAN --}}
                        <tr>
                            <td colspan="4" class="font-bold bg-gray-50 p-2">
                                <span>B. TAMBAHAN</span>
                            </td>
                        </tr>
                        @forelse($tambahans as $index => $tambahan)
                            <tr>
                                <td class="text-center font-bold w-12 align-top pt-4">{{ $index + 1 }}</td>
                                <td colspan="3" class="align-top">
                                    <div class="whitespace-pre-wrap py-2">{{ $tambahan->rencana_hasil_kerja }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-bold w-12 align-top pt-4">1</td>
                                <td colspan="3" class="align-top text-gray-500 italic">
                                    Tidak ada target tambahan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <!-- PERILAKU KERJA -->
                    <tbody>
                        <tr class="bg-light-gray">
                            <td colspan="4" class="font-bold">PERILAKU KERJA*</td>
                        </tr>
                        @foreach($perilakus as $index => $perilaku)
                            <tr>
                                <td class="text-center font-bold w-12 align-top">{{ $index + 1 }}</td>
                                <td colspan="2" class="align-top">
                                    <div class="font-bold mb-1 {{ strtolower($perilaku->aspek) == 'kepemimpinan' ? 'italic' : '' }}">{{ $perilaku->aspek }}</div>
                                    <div class="whitespace-pre-wrap text-gray-700 pl-4">{!! nl2br(e($perilaku->indikator)) !!}</div>
                                </td>
                                <td class="align-top">
                                    <div class="font-bold mb-1">Ekspektasi Khusus Pimpinan:</div>
                                    <div class="whitespace-pre-wrap">{{ $perilaku->ekspektasi_pimpinan ?? '-' }}</div>
                                </td>
                            </tr>
                        @endforeach
                    
</tbody>
                </table>
            </div>
            <!-- End of SKP tab -->

            <!-- EVALUASI PEGAWAI TAB -->
            <div x-show="activeTab === 'evaluasi'" x-cloak class="area-cetak overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <form action="{{ route('pegawai.skp-dosen-kualitatif.store-realisasi', $skpKualitatif->skpHeader->id ?? 1) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <fieldset @if(strtolower($skpKualitatif->skpHeader->status ?? '') == 'draft') disabled @endif>
                <div class="text-center font-bold mb-4 space-y-1">
                    <p>SASARAN KINERJA PEGAWAI</p>
                    <p>PENDEKATAN HASIL KERJA KUALITATIF</p>
                    <p>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>
                </div>
                <div class="flex justify-between font-bold mb-4 uppercase text-sm">
                    <div>UNIVERSITAS MUHAMMADIYAH SURABAYA</div>
                    <div>PERIODE PENILAIAN: 1 Januari s.d. 31 Maret 2026</div>
                </div>
                <!-- IDENTITAS -->
                <table class="table-kualitatif mb-6">
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
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $user->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/GOL. RUANG</span> <span>{{ $user->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/ GOL. RUANG</span> <span>{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold">UNIT KERJA</span> Universitas Muhammadiyah Surabaya</td>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold">UNIT KERJA</span> Universitas Muhammadiyah Surabaya</td>
                        </tr>
                    </tbody>
                </table>

                @php $isRealisasiSaved = isset($realisasis) && $realisasis->count() > 0; @endphp
                <table class="table-kualitatif mb-6 w-full">
                    <tbody>
                        <tr>
                            <td class="p-4">
                                <div class="mb-6">
                                    <label class="font-bold text-sm block mb-2">CAPAIAN KINERJA ORGANISASI*</label>
                                    <div class="w-full border border-gray-200 bg-gray-100 rounded-md shadow-inner text-gray-700 py-2 px-3 min-w-[200px]"
                                         x-text="predikatKinerja ? predikatKinerja : 'Belum dinilai oleh Penilai'">
                                    </div>
                                </div>

                                <div>
                                    <div class="font-bold text-sm mb-2">POLA DISTRIBUSI:</div>
                                    <template x-if="capaianKinerja">
                                        <div class="relative w-full bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm" style="height: 220px; overflow: hidden;">
                                            <canvas id="evaluasiChartCanvas"></canvas>
                                            <img id="evaluasiChartPrintImg" style="display:none; width:100%; height:100%; object-fit:contain;">
                                        </div>
                                    </template>
                                    <template x-if="!capaianKinerja">
                                        <div class="relative w-full bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm flex items-center justify-center" style="height: 220px; overflow: hidden;">
                                            <span class="text-gray-500 text-sm">Belum dinilai oleh Penilai</span>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table class="table-kualitatif w-full mb-6">
                    <thead>
                        <tr class="bg-light-gray text-center text-xs">
                            <th class="w-[30%]">HASIL KERJA</th>
                            <th class="w-[30%]">REALISASI BERDASARKAN BUKTI DUKUNG</th>
                            <th class="w-[20%]">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
                            <th class="w-[10%]">HASIL KERJA</th>
                            <th class="w-[10%] hidden">ANGKA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50">A. UTAMA</td>
                        </tr>
                        @php
                            // Helper: menentukan label singkat & bermakna untuk tiap kotak upload
                            // berdasarkan kata kunci di teks sub-indikator (child).
                            $utamaLabel = function ($text, $urutan) {
                                $text = trim((string) $text);
                                if ($text === '') return 'Sub-Indikator ' . $urutan;
                                $t = strtolower($text);
                                $map = [
                                    'penelitian'  => 'Penelitian',
                                    'riset'      => 'Penelitian',
                                    'pengabdian' => 'Pengabdian',
                                    'pengajaran' => 'Pengajaran',
                                    'pembelajaran'=> 'Pengajaran',
                                    'pendidikan' => 'Pengajaran',
                                ];
                                foreach ($map as $kw => $label) {
                                    if (str_contains($t, $kw)) return $label;
                                }
                                // Fallback: pakai teks sub-indikator apa adanya (dipotong kalau kepanjangan)
                                return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
                            };
                        @endphp
                        @forelse($skpUtamas as $uIndex => $skpUtama)
                        @php
                            // Ambil hanya child yang checkbox "Lampiran"-nya dicentang.
                            // Tiap child ini = 1 kotak Upload Bukti terpisah.
                            $utamaChildren = ($skpUtama->acuanParent && $skpUtama->acuanParent->children)
                                ? $skpUtama->acuanParent->children->where('butuh_lampiran', true)->values()
                                : collect();
                        @endphp
                        <tr x-data="{
                            hasilKerjaMap: {
                                'Diatas Ekspektasi': {{ $hasilKerja[0] ?? 3 }},
                                'Sesuai Ekspektasi': {{ $hasilKerja[1] ?? 2 }},
                                'Dibawah Ekspektasi': {{ $hasilKerja[2] ?? 1 }}
                            },
                            selectedHasilKerja: '{{ $skpUtama->hasil_kerja ?? '' }}'
                        }" x-effect="state.utamaScores[{{ $uIndex }}] = hasilKerjaMap[selectedHasilKerja]">
                            <td class="align-top">
                                <div class="flex">
                                    <div class="font-bold mr-2">{{ $uIndex + 1 }}</div>
                                    <div>{{ $skpUtama->rencana_hasil_kerja }}</div>
                                </div>
                                <div class="mt-4 font-bold">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                                <div class="whitespace-pre-wrap">{{ $skpUtama->indikator_target }}</div>
                            </td>
                            <td class="align-top">
                                @if($utamaChildren->isNotEmpty())
                                    @foreach($utamaChildren as $cIdx => $child)
                                        @php
                                            // Ambil realisasi yang sudah tersimpan untuk child ini (pre-fill saat edit)
                                            $rChild = isset($realisasis) ? $realisasis->where('target_type', 'utama')->where('acuan_child_id', $child->id)->values() : collect();
                                            $childRow = $rChild->first();
                                            // Label yang ditampilkan, dengan prioritas:
                                            //   1) label_realisasi = teks yang diketik dosen (per-realisasi),
                                            //   2) label_lampiran  = label master dari acuan (checkbox kemarin),
                                            //   3) utamaLabel()    = auto-detect dari teks sub-indikator.
                                            $labelRealisasi = $childRow->label_realisasi ?? '';
                                            $cLabel = $childRow->label_realisasi
                                                ?? ($child->label_lampiran ?? $utamaLabel($child->indikator_kinerja, $cIdx + 1));
                                            $cData = $childRow
                                                ? '{ id: ' . $childRow->id . ', text: ' . json_encode($childRow->realisasi ?? '') . ', file: ' . json_encode($childRow->bukti_dukung_url) . ', filename: ' . json_encode($childRow->bukti_dukung_nama) . ' }'
                                                : '{ id: null, text: "", file: null, filename: "" }';
                                        @endphp
                                        <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md" x-data="{ item: {{ $cData }} }">
                                            <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5">
                                                <i class="fas fa-paperclip text-blue-600"></i>
                                                <span>{{ $cLabel }}</span>
                                            </div>
                                            {{-- Input textbox label lampiran per-realisasi (dosen bisa ketik sendiri).
                                                 Tidak mengubah label master di acuan, hanya menggantikan tampilan
                                                 untuk baris realisasi ini. Sebelum Simpan Pertama saja bisa diketik. --}}
                                            <input type="text" name="label_realisasi[utama][{{ $child->id }}]"
                                                   value="{{ $childRow->label_realisasi ?? '' }}" placeholder="{{ $cLabel }}"
                                                   class="w-full border rounded text-xs p-2 mb-2 {{ $isRealisasiSaved ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : 'bg-white border-gray-300' }}"
                                                   {{ $isRealisasiSaved ? 'disabled' : '' }}>
                                            <textarea x-model="item.text" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full rounded-md shadow-sm text-xs p-2.5 overflow-hidden resize-none {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-200' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi..." name="realisasi[utama][{{ $child->id }}]" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}>{{ $childRow->realisasi ?? '' }}</textarea>
                                            <div class="mt-3 bg-blue-50 p-2.5 rounded border border-blue-100">
                                                @if(!$isRealisasiSaved)
                                                <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                    <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                                </label>
                                                @endif
                                                <div x-show="item.file" class="mb-2">
                                                    <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                                </div>
                                                @if(!$isRealisasiSaved)
                                                <input type="file" name="bukti_dukung[utama][{{ $child->id }}]" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md">
                                                <p class="text-[10px] text-gray-500 mt-1 italic">* Wajib unggah minimal 1 bukti pendukung</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic">
                                    <i class="fas fa-info-circle mr-1"></i> Tidak wajib lampiran
                                </div>
                                @endif
                            </td>
                            <td class="align-top">
                                <textarea x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-xs p-2.5 text-gray-500 cursor-not-allowed overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Belum ada umpan balik dari atasan..." readonly disabled>{{ $skpUtama->umpan_balik ?? '' }}</textarea>
                            </td>
                            <td class="align-middle text-center">
                                <select x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-gray-100 rounded-md shadow-sm text-gray-500 cursor-not-allowed py-2" disabled>
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                    <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                    <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                </select>
                            </td>
                            <td class="align-middle text-center hidden">
                                <input type="number" :value="hasilKerjaMap[selectedHasilKerja]" readonly tabindex="-1" class="w-16 border-gray-200 bg-gray-50 rounded-md shadow-inner text-center font-bold text-gray-600 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 italic py-4">Belum ada data Kinerja Utama.</td>
                        </tr>
                        @endforelse
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50">B. TAMBAHAN</td>
                        </tr>
                        @forelse($tambahans as $index => $tambahan)
                        <tr x-data="{
                            hasilKerjaMap: {
                                'Diatas Ekspektasi': {{ $hasilKerja[0] ?? 3 }},
                                'Sesuai Ekspektasi': {{ $hasilKerja[1] ?? 2 }},
                                'Dibawah Ekspektasi': {{ $hasilKerja[2] ?? 1 }}
                            },
                            selectedHasilKerja: ''
                        }">
                            <td class="align-top">
                                <div class="flex">
                                    <div class="font-bold mr-2">{{ $index + 1 }}</div>
                                    <div class="whitespace-pre-wrap">{{ $tambahan->rencana_hasil_kerja }}</div>
                                </div>

                            </td>
                            @php
                                $rTambahan = isset($realisasis) ? $realisasis->where('target_type', 'tambahan')->where('target_id', $tambahan->id)->values() : collect();
                                $tData = $rTambahan->count() > 0 ? $rTambahan->map(function($r) { return ['id' => $r->id, 'text' => $r->realisasi, 'file' => $r->bukti_dukung_url, 'filename' => $r->bukti_dukung_nama]; })->toJson() : '[{ id: 1, text: "", file: null }]';
                            @endphp
                            <td class="align-top" x-data="{ items: {{ $tData }} }">
                                <template x-for="(item, index) in items" :key="item.id">
                                    <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md relative group">
                                        @if(!$isRealisasiSaved)
                                        <button @click="items.splice(index, 1)" x-show="items.length > 1" type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                        <div class="flex items-start gap-2 mb-3 pr-6">
                                            <span class="font-bold text-gray-700 mt-1" x-text="(index + 1) + '.'"></span>
                                            <textarea x-model="item.text" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full rounded-md shadow-sm text-xs p-2.5 overflow-hidden resize-none {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-200' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi..." :name="'realisasi[tambahan][' + {{ $tambahan->id }} + '][]'" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}></textarea>
                                        </div>
                                        <div class="ml-6 bg-blue-50 p-2.5 rounded border border-blue-100">
                                            @if(!$isRealisasiSaved)
                                            <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                            </label>
                                            @endif
                                            <div x-show="item.file" class="mb-2">
                                                <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                            </div>
                                            @if(!$isRealisasiSaved)
                                            <input type="file" :name="'bukti_dukung[tambahan][' + {{ $tambahan->id }} + '][]'" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md" multiple>
                                            <p class="text-[10px] text-gray-500 mt-1 italic">* Wajib unggah minimal 1 bukti pendukung</p>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                                @if(!$isRealisasiSaved)
                                <button @click="items.push({ id: Date.now(), text: '', file: null })" type="button" class="mt-2 text-xs text-blue-600 font-bold hover:text-blue-800 flex items-center gap-1 border border-blue-600 rounded px-3 py-1.5 hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-plus"></i> Tambah Realisasi
                                </button>
                                @endif
                            </td>
                            <td class="align-top">
                                <textarea x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-xs p-2.5 text-gray-500 cursor-not-allowed overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Belum ada umpan balik dari atasan..." readonly disabled>{{ $tambahan->umpan_balik ?? '' }}</textarea>
                            </td>
                            <td class="align-middle text-center">
                                <select x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-gray-100 rounded-md shadow-sm text-gray-500 cursor-not-allowed py-2" disabled>
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                    <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                    <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                </select>
                            </td>
                            <td class="align-middle text-center hidden">
                                <input type="number" :value="hasilKerjaMap[selectedHasilKerja]" readonly tabindex="-1" class="w-16 border-gray-200 bg-gray-50 rounded-md shadow-inner text-center font-bold text-gray-600 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 italic py-4">Tidak ada target tambahan.</td>
                        </tr>
                        @endforelse
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left">
                                <div class="mb-2">RATING HASIL KERJA*</div>
                                <textarea :value="rating" readonly tabindex="-1" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                            </td>
                        </tr>
                        </tbody>
                </table>

                <table class="table-kualitatif table-perilaku w-full mb-6" style="table-layout: fixed;">
                    <tbody>
                        <!-- PERILAKU KERJA SECTION -->
                        <tr class="bg-gray-50 text-xs border-t-2 border-b-2 border-gray-300">
                            <th colspan="2" class="p-2 text-left w-[45%]">PERILAKU KERJA</th>
                            <th class="p-2 text-center w-[25%] no-print">REALISASI PEGAWAI &amp; BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[20%]">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[10%]">HASIL KERJA</th>
                        </tr>
                        @php
                            // Helper label singkat untuk kotak upload perilaku (sama persis pola
                            // dengan utamaLabel di section A -- struktur datanya kompatibel).
                            $perilakuLabel = function ($text, $urutan) {
                                $text = trim((string) $text);
                                if ($text === '') return 'Sub-Indikator ' . $urutan;
                                $t = strtolower($text);
                                $map = [
                                    'penelitian'  => 'Penelitian',
                                    'riset'       => 'Penelitian',
                                    'pengabdian'  => 'Pengabdian',
                                    'pengajaran'  => 'Pengajaran',
                                    'pembelajaran'=> 'Pengajaran',
                                    'pendidikan'  => 'Pengajaran',
                                ];
                                foreach ($map as $kw => $label) {
                                    if (str_contains($t, $kw)) return $label;
                                }
                                return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
                            };
                        @endphp
                        @foreach($perilakus as $index => $perilaku)
                        <tr x-data="{
                            hasilKerjaMap: {
                                'Diatas Ekspektasi': {{ $hasilKerja[0] ?? 3 }},
                                'Sesuai Ekspektasi': {{ $hasilKerja[1] ?? 2 }},
                                'Dibawah Ekspektasi': {{ $hasilKerja[2] ?? 1 }}
                            },
                            selectedHasilKerja: '{{ $perilaku->hasil_kerja ?? '' }}'
                        }" x-effect="state.perilakuScores[{{ $index }}] = hasilKerjaMap[selectedHasilKerja]">
                            <td class="align-top">
                                <div class="flex">
                                    <div class="font-bold mr-2">{{ $index + 1 }}</div>
                                    <div>
                                        <div class="font-bold mb-1 {{ strtolower($perilaku->aspek) == 'kepemimpinan' ? 'italic' : '' }}">{{ $perilaku->aspek }}</div>
                                        <div class="whitespace-pre-wrap text-gray-700 pl-4">{!! nl2br(e($perilaku->indikator)) !!}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="align-top">
                                <div class="font-bold mb-1">Ekspektasi Khusus Pimpinan:</div>
                                <div class="whitespace-pre-wrap">{{ $perilaku->ekspektasi_pimpinan ?? '-' }}</div>
                            </td>
                            @php
                                // Ambil acuan parent yang cocok dengan aspek indikator ini,
                                // lalu filter hanya child (sub-indikator) yang checkbox "Lampiran"-nya
                                // dicentang. Tiap child = 1 kotak Upload Bukti terpisah (pola section A).
                                $perilakuChildren = collect();
                                $acuanForAspek = $perilakuAcuan->where('sasaran_kinerja', $perilaku->aspek)->first();
                                if ($acuanForAspek && $acuanForAspek->children) {
                                    $perilakuChildren = $acuanForAspek->children->where('butuh_lampiran', true)->values();
                                }
                            @endphp
                            <td class="align-top no-print">
                                @if($perilakuChildren->isNotEmpty())
                                    @foreach($perilakuChildren as $cIdx => $child)
                                        @php
                                            // Ambil realisasi tersimpan untuk child ini (pre-fill saat edit).
                                            $rChild = isset($realisasis) ? $realisasis->where('target_type', 'perilaku')->where('acuan_child_id', $child->id)->values() : collect();
                                            $childRow = $rChild->first();
                                            // Label yang ditampilkan, dengan prioritas:
                                            //   1) label_realisasi = teks yang diketik dosen (per-realisasi),
                                            //   2) label_lampiran  = label master dari acuan (checkbox kemarin),
                                            //   3) perilakuLabel()  = auto-detect dari teks sub-indikator.
                                            $labelRealisasi = $childRow->label_realisasi ?? '';
                                            $cLabel = $childRow->label_realisasi
                                                ?? ($child->label_lampiran ?? $perilakuLabel($child->indikator_kinerja, $cIdx + 1));
                                            $cData = $childRow
                                                ? '{ id: ' . $childRow->id . ', text: ' . json_encode($childRow->realisasi ?? '') . ', file: ' . json_encode($childRow->bukti_dukung_url) . ', filename: ' . json_encode($childRow->bukti_dukung_nama) . ' }'
                                                : '{ id: null, text: "", file: null, filename: "" }';
                                        @endphp
                                        <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md" x-data="{ item: {{ $cData }} }">
                                            <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5">
                                                <i class="fas fa-paperclip text-blue-600"></i>
                                                <span>{{ $cLabel }}</span>
                                            </div>
                                            {{-- Input textbox label lampiran per-realisasi (dosen bisa ketik sendiri).
                                                 Tidak mengubah label master di acuan, hanya menggantikan tampilan
                                                 untuk baris realisasi ini. Sebelum Simpan Pertama saja bisa diketik. --}}
                                            <input type="text" name="label_realisasi[perilaku][{{ $child->id }}]"
                                                   value="{{ $childRow->label_realisasi ?? '' }}" placeholder="{{ $cLabel }}"
                                                   class="w-full border rounded text-xs p-2 mb-2 {{ $isRealisasiSaved ? 'bg-gray-100 text-gray-500 border-gray-200 cursor-not-allowed' : 'bg-white border-gray-300' }}"
                                                   {{ $isRealisasiSaved ? 'disabled' : '' }}>
                                            <textarea x-model="item.text" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full rounded-md shadow-sm text-xs p-2.5 overflow-hidden resize-none {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-blue-500 focus:ring-blue-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi perilaku kerja..." name="realisasi[perilaku][{{ $child->id }}]" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}>{{ $childRow->realisasi ?? '' }}</textarea>
                                            <div class="mt-3 bg-blue-50 p-2.5 rounded border border-blue-100">
                                                @if(!$isRealisasiSaved)
                                                <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                    <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                                </label>
                                                @endif
                                                <div x-show="item.file" class="mb-2">
                                                    <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                                </div>
                                                @if(!$isRealisasiSaved)
                                                <input type="file" name="bukti_dukung[perilaku][{{ $child->id }}]" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md">
                                                <p class="text-[10px] text-gray-500 mt-1 italic">* Wajib unggah minimal 1 bukti pendukung</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic">
                                    <i class="fas fa-info-circle mr-1"></i> Tidak wajib lampiran
                                </div>
                                @endif
                            </td>
                            <td class="align-top">
                                <textarea x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-xs p-2.5 text-gray-500 cursor-not-allowed overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Belum ada umpan balik dari atasan..." readonly disabled>{{ $perilaku->umpan_balik ?? '' }}</textarea>
                            </td>
                            <td class="align-middle text-center">
                                <select x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-gray-100 rounded-md shadow-sm text-gray-500 cursor-not-allowed py-2" disabled>
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                    <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                    <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
<tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">RATING PERILAKU KERJA*</div>
                                <textarea :value="ratingPerilaku" readonly tabindex="-1" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">PREDIKAT KINERJA PEGAWAI*</div>
                                <textarea :value="predikatKinerja" readonly tabindex="-1" class="w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                            </td>
                        </tr>
</tbody>
                </table>
                </fieldset>
                @if(strtolower($skpKualitatif->skpHeader->status ?? '') != 'draft' && !$isRealisasiSaved)
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-save mr-2"></i> Simpan Realisasi Evaluasi
                    </button>
                </div>
                @endif
                </form>
            </div>

            <!-- BAGIAN LAMPIRAN SKP -->
            <div x-show="activeTab === 'lampiran'" x-cloak class="area-cetak overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <div class="text-center font-bold mb-6">
                    <p class="uppercase">LAMPIRAN SASARAN KINERJA PEGAWAI</p>
                </div>
                <table class="table-kualitatif w-full">
                    <tbody>
                        <!-- DUKUNGAN SUMBER DAYA -->
                        <tr class="bg-gray-50">
                            <td colspan="2" class="font-bold py-2 px-3">DUKUNGAN SUMBER DAYA (pegawai, sarpras, anggaran, metode)</td>
                        </tr>
                        @php $dukungans = $lampirans->where('jenis', 'dukungan_sumber_daya')->values(); @endphp
                        @forelse($dukungans as $index => $item)
                            <tr>
                                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                                <td class="whitespace-pre-wrap">{{ $item->deskripsi }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-bold w-12">1</td>
                                <td class="text-gray-500 italic">Belum ada dukungan sumber daya yang diinput.</td>
                            </tr>
                        @endforelse

                        <!-- SKEMA PERTANGGUNGJAWABAN -->
                        <tr class="bg-gray-50">
                            <td colspan="2" class="font-bold py-2 px-3 border-t-2 border-gray-300">SKEMA PERTANGGUNGJAWABAN</td>
                        </tr>
                        @php $skemas = $lampirans->where('jenis', 'skema_pertanggungjawaban')->values(); @endphp
                        @forelse($skemas as $index => $item)
                            <tr>
                                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                                <td class="whitespace-pre-wrap">{{ $item->deskripsi }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-bold w-12">1</td>
                                <td class="text-gray-500 italic">Belum ada skema pertanggungjawaban yang diinput.</td>
                            </tr>
                        @endforelse

                        <!-- KONSEKUENSI -->
                        <tr class="bg-gray-50">
                            <td colspan="2" class="font-bold py-2 px-3 border-t-2 border-gray-300">KONSEKUENSI</td>
                        </tr>
                        @php $konsekuensis = $lampirans->where('jenis', 'konsekuensi')->values(); @endphp
                        @forelse($konsekuensis as $index => $item)
                            <tr>
                                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                                <td class="whitespace-pre-wrap">{{ $item->deskripsi }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center font-bold w-12">1</td>
                                <td class="text-gray-500 italic">Belum ada konsekuensi yang diinput.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if(strtolower($skpKualitatif->skpHeader->status ?? '') != 'draft')
                <div class="mt-6 flex justify-end">
                    <button type="button" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-save mr-2"></i> Simpan Realisasi
                    </button>
                </div>
                @endif
            </div>
        
            <!-- DOKUMEN EVALUASI TAB -->
            <div x-show="activeTab === 'dokumen'" x-cloak class="area-cetak overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <div class="text-center font-bold mb-4 space-y-1">
                    <img src="{{ asset('images/umsuraicon.png') }}" alt="Logo UMSurabaya" class="h-32 mx-auto mb-4 object-contain">
                    <p>DOKUMEN EVALUASI KINERJA PEGAWAI</p>
                </div>
                <div class="flex justify-between font-bold mb-4 uppercase text-sm">
                    <div>
                        <p>PERIODE: TRIWULAN I</p>
                        <p>NAMA FAKULTAS (TIDAK DISINGKAT)</p>
                    </div>
                    <div class="text-right">
                        <p>PERIODE PENILAIAN:</p>
                        <p>1 JANUARI S.D. 31 MARET TAHUN 2026</p>
                    </div>
                </div>

                <table class="table-kualitatif w-full">
                    <tbody>
                        <!-- PEGAWAI YANG DINILAI -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="w-12 text-center">1</td>
                            <td colspan="2">PEGAWAI YANG DINILAI</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="w-64 font-bold">NAMA</td>
                            <td>{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NIP</td>
                            <td>{{ $user->profile->nip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">PANGKAT/GOL. RUANG</td>
                            <td>{{ $user->profile->pangkat_golongan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">JABATAN</td>
                            <td>{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">UNIT KERJA</td>
                            <td>Universitas Muhammadiyah Surabaya</td>
                        </tr>
                        
                        <!-- PEJABAT PENILAI KINERJA -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="w-12 text-center">2</td>
                            <td colspan="2">PEJABAT PENILAI KINERJA</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NAMA</td>
                            <td>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NIP</td>
                            <td>{{ $penilai->profile->nip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">PANGKAT/GOL. RUANG</td>
                            <td>{{ $penilai->profile->pangkat_golongan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">JABATAN</td>
                            <td>{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dekan' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">UNIT KERJA</td>
                            <td>Universitas Muhammadiyah Surabaya</td>
                        </tr>

                        @php $atasan = $skpKualitatif->skpHeader->atasanPejabatPenilai; @endphp
                        <!-- ATASAN PEJABAT PENILAI KINERJA -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="w-12 text-center">3</td>
                            <td colspan="2">ATASAN PEJABAT PENILAI KINERJA</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NAMA</td>
                            <td>{{ $atasan->profile->nama_lengkap ?? $atasan->name ?? 'Prof. Dr. Mundakir, S.Kep., Ns., M.Kep.' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NIP</td>
                            <td>{{ $atasan->profile->nip ?? '1974.0323.2005.01.1.002' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">PANGKAT/GOL. RUANG</td>
                            <td>{{ $atasan->profile->pangkat_golongan ?? 'Pembina IV.a' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">JABATAN</td>
                            <td>{{ $atasan ? ($atasan->roles->pluck('name')->implode(', ') ?: 'Rektor') : 'Rektor' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">UNIT KERJA</td>
                            <td>Universitas Muhammadiyah Surabaya</td>
                        </tr>

                        <!-- EVALUASI KINERJA -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="w-12 text-center">4</td>
                            <td colspan="2">EVALUASI KINERJA</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">CAPAIAN KINERJA ORGANISASI</td>
                            <td x-text="capaianKinerja ? capaianKinerja.toUpperCase() : '-'"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">PREDIKAT KINERJA PEGAWAI</td>
                            <td x-text="predikatKinerja || '-'"></td>
                        </tr>

                        <!-- CATATAN/ REKOMENDASI -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="w-12 text-center">5</td>
                            <td colspan="2">CATATAN/ REKOMENDASI</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="2" class="p-0">
                                <textarea class="w-full border-0 focus:ring-0 resize-none p-4" rows="4" readonly disabled>{{ $skpKualitatif->skpHeader->catatan_penilai ?? '' }}</textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
</div>
    @endsection
</x-app-layout>