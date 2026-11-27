<x-app-layout>
    @section('page-title', 'Detail SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <style>
        /* Ctrl+P dimatikan: saat print langsung dari halaman ini (format lama),
           sembunyikan seluruh isi dan tampilkan pesan pengarah ke tombol Print.
           Cetak resmi hanya lewat tombol Print (halaman print baru). */
        #print-legacy-blocker { display: none; }
        @media print {
            @page { size: A4 portrait; }
            body * { display: none !important; }
            #print-legacy-blocker {
                display: block !important;
                position: fixed;
                inset: 0;
                padding: 60px 24px;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 16px;
                font-weight: bold;
                color: #000;
                text-align: center;
            }
        }
    </style>
    <div id="print-legacy-blocker">Gunakan tombol Print di halaman untuk mencetak SKP.</div>
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
                    let scores = Object.values(this.state.utamaScores);
                    let validScores = scores.filter(s => s !== undefined && s !== null && s !== '');
                    if (validScores.length === 0) return '';
                    let avg = validScores.reduce((a,b)=>a+b, 0) / validScores.length;
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
                    <a :href="'{{ route('pegawai.skp-dosen-kualitatif.print', $skpKualitatif->skpHeader->id) }}?tab=' + activeTab" target="_blank" href="{{ route('pegawai.skp-dosen-kualitatif.print', $skpKualitatif->skpHeader->id) }}?tab=skp" class="no-print inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-print mr-2"></i> Print
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

                @media print {
                    /* HANYA tab yang sedang aktif di layar yang dicetak.
                       Panel non-aktif tetap mengikuti kondisi Alpine
                       (inline display:none dari x-show + x-cloak), jadi
                       tidak ada rule yang memaksa panel tersembunyi
                       menjadi tampil saat print. */
                    #print-area [x-cloak] { display: none !important; }

                    /* Elemen pengganti khusus cetak (.print-only) tersembunyi
                       di layar; hanya tampil di dalam @media print (rule
                       display:block untuk print ada di dalam blok ini). */
                    #print-area .print-only { display: none !important; }

                    /* Kontrol form tidak dicetak: input (file/number/hidden),
                       select hasil kerja, tombol aksi. Textarea tidak
                       disembunyikan — digaya ulang jadi teks polos di bawah. */
                    #print-area form input,
                    #print-area form select,
                    #print-area form button,
                    #print-area .no-print { display: none !important; }

                    /* Elemen pengganti khusus cetak (.print-only): tampil saat
                       print (tersembunyi oleh rule di awal blok ini). Dipakai
                       untuk nilai hasil kerja (menggantikan select) dan
                       rating/predikat (menggantikan textarea yang nilainya
                       terikat via atribut :value Alpine sehingga tidak ikut
                       tercetak sebagai isi textarea). */
                    #print-area .print-only {
                        display: block !important;
                        white-space: pre-wrap;
                        overflow-wrap: break-word;
                        word-break: break-word;
                    }

                    /* Textarea rating/predikat (nilai terikat :value Alpine,
                        bukan isi server) disembunyikan saat print — sudah
                        diwakili elemen .print-only di sampingnya. */
                    #print-area .print-hidden { display: none !important; }

                    /* Textarea (umpan balik, realisasi tambahan, rating, predikat)
                       tampil sebagai teks polos: tanpa bingkai, tanpa scrollbar,
                       tanpa batas tinggi sehingga seluruh isinya tercetak. */
                    #print-area form textarea {
                        border: none !important;
                        box-shadow: none !important;
                        background: transparent !important;
                        padding: 0 !important;
                        resize: none !important;
                        min-height: 0 !important;
                        max-height: none !important;
                        height: auto !important;
                        overflow: visible !important;
                    }

                    /* Placeholder textarea kosong (mis. "Tuliskan umpan balik...")
                       tidak dicetak. */
                    #print-area form textarea::placeholder {
                        color: transparent !important;
                    }

                    /* Kartu realisasi dipoloskan (tanpa kartu/shadow) supaya
                       menyatu dengan tabel dokumen resmi. Antar kartu
                       dirapatkan dan kartu tidak terpotong di tengah
                       halaman (berpindah utuh bila tidak muat). */
                    #print-area .realisasi-card-penilai {
                        border: none !important;
                        box-shadow: none !important;
                        background: transparent !important;
                        padding: 0 !important;
                        margin-bottom: 6px !important;
                        /* break-inside DIHAPUS: avoid pada kartu realisasi yang TINGGI
                           memaksa seluruh baris pindah utuh ke halaman berikutnya dan
                           menyisakan area tabel kosong besar di halaman sebelumnya.
                           Dengan auto, baris panjang terpecah wajar di dalam kartu
                           (label + isi kartu tetap mengalir, header tabel berulang). */
                        page-break-inside: auto;
                        break-inside: auto;
                    }

                    /* Ikon (paperclip/file) di kartu realisasi tidak dicetak;
                       judul subbab tetap tampil sebagai teks. */
                    #print-area .realisasi-card-penilai i { display: none !important; }

                    /* Tautan/identitas berkas bukti & kotak biru bukti tidak
                       dicetak (data & tautan tetap utuh di layar). Selector
                       div[x-show].bukti-meta-print lebih spesifik dari rule
                       display:block di atas supaya link berkas tetap tersembunyi. */
                    #print-area div[x-show].bukti-meta-print,
                    #print-area .bukti-meta-print { display: none !important; }

                    /* Kartu realisasi kosong disembunyikan seluruhnya (judul ikut
                       hilang) tanpa menggeser struktur tabel lain. */
                    #print-area .realisasi-card-empty { display: none !important; }

                    /* Kotak 'Tidak wajib lampiran' & placeholder kekosongan tidak
                       dicetak. */
                    #print-area .lampiran-optional-print,
                    #print-area .empty-placeholder-print { display: none !important; }

                    /* Bingkai pembungkus tab tidak ikut tercetak. */
                    #print-area div[x-show] {
                        border: none !important;
                        box-shadow: none !important;
                    }

                    /* 6) Proporsi kolom cetak TABEL EVALUASI UTAMA
                       (Hasil Kerja 36% | Realisasi 24% | Umpan Balik 26% |
                       Hasil Kerja/nilai 14%). Selektor dibatasi ke tabel yang
                       memiliki <thead> DAN berisi kartu realisasi — tabel
                       identitas (tanpa kartu) dan tabel Perilaku Kerja
                       (header di <tbody>, tanpa <thead>) tidak terdampak.
                       Kolom ke-5 "ANGKA" tetap tersembunyi via class Tailwind
                       `hidden`; karena display:none kolom itu tidak menyumbang
                       lebar, maka 36+24+26+14 = 100% tetap pas. */
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) thead th:nth-child(1),
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) tbody td:nth-child(1) { width: 36% !important; }
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) thead th:nth-child(2),
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) tbody td:nth-child(2) { width: 24% !important; }
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) thead th:nth-child(3),
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) tbody td:nth-child(3) { width: 26% !important; }
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) thead th:nth-child(4),
                    #print-area table.table-kualitatif:has(thead):has(.realisasi-card-penilai) tbody td:nth-child(4) { width: 14% !important; }

                    /* 6b) Pemenggalan halaman: tabel & baris boleh menyambung
                       antar halaman (baris panjang berlanjut wajar); judul
                       tabel tidak terpisah dari baris pertamanya. */
                    #print-area table.table-kualitatif,
                    #print-area table.table-kualitatif tr { page-break-inside: auto; }
                    #print-area table.table-kualitatif thead { page-break-inside: avoid; break-inside: avoid; }
                    #print-area table.table-kualitatif thead th { page-break-after: avoid; break-after: avoid; }

                    /* 6b-2) Judul grup (A. UTAMA / B. TAMBAHAN / RATING /
                       PREDIKAT) tidak boleh tercetak sendirian di dasar
                       halaman tanpa isi setelahnya. Baris judul dipaksa
                       menyatu dengan baris berikutnya: bila sisa ruang
                       tidak cukup, judul ikut pindah ke halaman berikutnya.
                       Baris data tetap boleh terpecah (rule di atas),
                       jadi tidak ada ruang kosong besar akibat baris
                       tinggi dipindah utuh. */
                    #print-area table.table-kualitatif tr:has(+ tr) td[colspan]:only-child {
                        page-break-after: avoid;
                        break-after: avoid;
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

                    /* 6b-3) Penyatuan judul grup/header dengan baris data pertama:
                       Chromium sering mengabaikan break-after:avoid ANTAR baris
                       tabel, tetapi menghormati break-inside:avoid pada grup
                       <tbody>. Judul (A. UTAMA, B. TAMBAHAN) dan header tabel
                       Perilaku Kerja ditempatkan dalam <tbody> tersendiri
                       (class keep-with-next-print, sama-sama berisi judul + baris
                       data pertama) sehingga keduanya berpindah BERSAMA ke
                       halaman berikutnya bila ruang tidak cukup — judul tidak
                       pernah tercetak sendirian di dasar halaman. Baris data
                       BERIKUTNYA tetap boleh terpecah wajar (tbody lain tidak
                       ber-avoid). Tampilan layar tidak berubah: di layar semua
                       tbody dirender identik tanpa garis pemisah. */
                    #print-area table.table-kualitatif tbody.keep-with-next-print {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

                    /* 6c) Teks panjang membungkus di dalam sel — tidak
                       meluber/melebarkan kolom. */
                    #print-area table.table-kualitatif td { overflow-wrap: break-word; }

                    /* 7) Kartu realisasi di sel B. TAMBAHAN dipoloskan juga
                       (struktur sama, hanya belum ber-class penilai). */
                    #print-area .area-cetak td .rounded-lg,
                    #print-area td .rounded-lg {
                        border: none !important;
                        box-shadow: none !important;
                        background: transparent !important;
                        padding: 0 !important;
                        border-radius: 0 !important;
                    }

                    /* 8) Blok PREDIKAT KINERJA + POLA DISTRIBUSI dicetak
                       apa adanya (ukuran, tinggi, jarak, posisi sama dengan
                       tampilan desain terkini — TIDAK dipadatkan). Kotak
                       220px & canvas tetap mengikuti layout. */
                    #print-area td.predikat-pola-td div:has(> canvas) {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

                    /* 9) Pemangkasan sisa ruang vertikal agar tabel terakhir
                       tidak meluber 1 halaman ekstra yang isinya hanya garis
                       tepi & margin (halaman kosong di ekor PDF). Padding
                       pembungkus tab dan jarak antar tabel dirapatkan hanya
                       saat cetak — tampilan layar tidak berubah. */
                    #print-area div[x-show] {
                        padding: 0 !important;
                        margin-bottom: 0 !important;
                    }
                    #print-area table.table-kualitatif { margin-bottom: 10px !important; }
                    #print-area .table-kualitatif:last-of-type { margin-bottom: 0 !important; }

                    /* 10) Border bawah baris tabel yang terpotong pindah halaman:
                          Chromium mode border-collapse:collapse TIDAK menggambar
                          border bawah fragmen baris saat baris panjang terbelah
                          di batas halaman (tabel terlihat "tak selesai").
                          Solusi khusus cetak: pakai model border terpisah
                          (separate) dengan border per-sel — setiap fragmen
                          sel menggambar border sendiri sehingga potongan
                          halaman selalu tertutup garis rapi. Tanpa efek di
                          layar (hanya @media print). */
                    #print-area table.table-kualitatif {
                        border-collapse: separate !important;
                        border-spacing: 0 !important;
                    }
                    #print-area table.table-kualitatif td,
                    #print-area table.table-kualitatif th {
                        border-bottom: 1px solid #000 !important;
                        border-right: 1px solid #000 !important;
                        border-left: 1px solid #000 !important;
                        border-top: none !important;
                    }
                    #print-area table.table-kualitatif thead th,
                    #print-area table.table-kualitatif tr:first-child th,
                    #print-area table.table-kualitatif tr:first-child td {
                        border-top: 1px solid #000 !important;
                    }
                }
            </style>

            <div x-show="activeTab === 'skp'" class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
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
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $bawahan->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/GOL. RUANG</span> <span>{{ $bawahan->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/ GOL. RUANG</span> <span>{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
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
            <div x-show="activeTab === 'evaluasi'" x-cloak class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <form id="evaluasi-form" action="{{ route('penilai.evaluasi.verify-kualitatif', $skpKualitatif->skpHeader->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <fieldset @if(in_array(strtolower($skpKualitatif->skpHeader->status ?? ''), ['selesai', 'ditolak', 'draft'])) disabled @endif>
                <div class="text-center font-bold mb-4 space-y-1">
                    <p>SASARAN KINERJA PEGAWAI</p>
                    <p>PENDEKATAN HASIL KERJA KUALITATIF</p>
                    <p>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>
                </div>
                <div class="flex justify-between font-bold mb-4 uppercase text-sm">
                    <div>UNIVERSITAS MUHAMMADIYAH SURABAYA</div>
                    <div>PERIODE PENILAIAN: {{ $skpKualitatif->skpHeader->periode->periode_penilaian_label ?? '-' }}</div>
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
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold">NAMA</span> <span>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $bawahan->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold">NIP</span> <span>{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/GOL. RUANG</span> <span>{{ $bawahan->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold">PANGKAT/ GOL. RUANG</span> <span>{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold">JABATAN</span> <span>{{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
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

                <table class="table-kualitatif mb-6 w-full">
                    <tbody>
                        <tr>
                            <td class="p-4 predikat-pola-td">
                                <div class="mb-6">
                                    <label class="font-bold text-sm block mb-2">PREDIKAT KINERJA PEGAWAI</label>
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
                    </tbody>
                    {{-- Baris judul grup & baris data pertama direkatkan saat cetak:
                         Chromium mengabaikan break-after:avoid antar <tr>, tetapi
                         menghormati page-break-inside:avoid pada <tbody> — judul
                         tidak pernah tercetak sendirian di dasar halaman. --}}
                    <tbody class="keep-with-next-print">
                        @php
                            // Helper label singkat untuk kotak bukti dukung A. UTAMA
                            // (pola sama persis dengan $utamaLabel di view Dosen).
                            $utamaLabel = function ($text, $urutan) {
                                $text = trim((string) $text);
                                if ($text === '') return 'Sub-Indikator ' . $urutan;
                                $t = strtolower($text);
                                $map = [
                                    'penelitian'   => 'Penelitian',
                                    'riset'        => 'Penelitian',
                                    'pengabdian'   => 'Pengabdian',
                                    'pengajaran'   => 'Pengajaran',
                                    'pembelajaran' => 'Pengajaran',
                                    'pendidikan'   => 'Pengajaran',
                                ];
                                foreach ($map as $kw => $label) {
                                    if (str_contains($t, $kw)) return $label;
                                }
                                return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
                            };
                        @endphp
                        @forelse($skpUtamas as $uIndex => $skpUtama)
                            @php
                                // Kotak upload = SNAPSHOT (skp_kualitatifs.lampiran_snapshot),
                                // bukan live dari master Acuan. Fallback live: row lama tanpa
                                // snapshot & parent masih ada (grandfathered).
                                $snapshotItems = $skpUtama->lampiran_snapshot;
                                if ($snapshotItems === null && $skpUtama->acuanParent && $skpUtama->acuanParent->children) {
                                    $utamaChildren = $skpUtama->acuanParent->children->where('butuh_lampiran', true)->values();
                                } else {
                                    $utamaChildren = collect($snapshotItems ?? [])->map(function ($item) {
                                        $label = $item['label'] ?? null;
                                        if (!is_null($item['child_id'] ?? null)) {
                                            $child = \App\Models\SkpKualitatifAcuanChild::find($item['child_id']);
                                            if ($child) {
                                                return (object) [
                                                    'id' => $child->id,
                                                    'indikator_kinerja' => $child->indikator_kinerja,
                                                    'label_lampiran' => $label,
                                                ];
                                            }
                                        }
                                        return (object) ['id' => $item['child_id'] ?? null, 'indikator_kinerja' => '', 'label_lampiran' => $label];
                                    })->values();
                                }
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
                                                $rChild = isset($realisasis) ? $realisasis->where('target_type', 'utama')->where('acuan_child_id', $child->id)->values() : collect();
                                                $childRow = $rChild->first();
                                                $cLabel = $childRow->label_realisasi ?? ($child->label_lampiran ?? $utamaLabel($child->indikator_kinerja, $cIdx + 1));
                                            @endphp
                                            <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm realisasi-card-penilai @if(!$childRow || trim((string) $childRow->realisasi) === '') realisasi-card-empty @endif">
                                                <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5">
                                                    <i class="fas fa-paperclip text-blue-600"></i>
                                                    <span>{{ $cLabel }}</span>
                                                </div>
                                                <div class="whitespace-pre-wrap break-words max-h-40 overflow-y-auto text-gray-800 text-xs mb-2">{{ \App\Support\RealisasiRingkas::ringkas(trim((string) ($childRow->realisasi ?? ''))) ?: '-' }}</div>
                                                @if($childRow && $childRow->bukti_dukung_url)
                                                    <div class="bukti-meta-print">
                                                        <a href="{{ asset($childRow->bukti_dukung_url) }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                                            <i class="fas fa-file mr-1"></i> {{ $childRow->bukti_dukung_nama ?? 'Lihat Bukti' }}
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-400 italic bukti-meta-print">Tidak ada file bukti</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic lampiran-optional-print">
                                            <i class="fas fa-info-circle mr-1"></i> Tidak wajib lampiran
                                        </div>
                                    @endif
                                </td>
                                <td class="align-top">
                                    <textarea name="umpan_balik[utama_{{ $skpUtama->id }}]" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full border-gray-300 bg-white rounded-md shadow-sm text-xs p-2.5 text-gray-800 focus:ring-blue-500 focus:border-blue-500 overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Tuliskan umpan balik...">{{ $skpUtama->umpan_balik ?? '' }}</textarea>
                                </td>
                                <td class="align-middle text-center">
                                    <select name="hasil_kerja[utama_{{ $skpUtama->id }}]" x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-white rounded-md shadow-sm text-gray-800 border-gray-300 py-2">
                                        <option value="" selected>-- Pilih --</option>
                                        <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                        <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                        <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                    </select>
                                    {{-- Teks khusus cetak: mengikuti nilai Alpine selectedHasilKerja
                                        (select disembunyikan saat print; nilainya tetap tercetak). --}}
                                    <span class="print-only font-bold" x-text="selectedHasilKerja || '-'"></span>
                                </td>
                                <td class="align-middle text-center hidden">
                                    <input type="number" :value="hasilKerjaMap[selectedHasilKerja]" readonly tabindex="-1" class="w-16 border-gray-200 bg-gray-50 rounded-md shadow-inner text-center font-bold text-gray-600 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none">
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-placeholder-print">
                                <td colspan="5" class="text-center text-gray-500 italic py-4">Belum ada data Kinerja Utama.</td>
                            </tr>
                        @endforelse
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50">B. TAMBAHAN</td>
                        </tr>
                    </tbody>
                    <tbody class="keep-with-next-print">
                        @forelse($tambahans as $index => $tambahan)
                        <tr x-data="{
                            hasilKerjaMap: {
                                'Diatas Ekspektasi': {{ $hasilKerja[0] ?? 3 }},
                                'Sesuai Ekspektasi': {{ $hasilKerja[1] ?? 2 }},
                                'Dibawah Ekspektasi': {{ $hasilKerja[2] ?? 1 }}
                            },
                            selectedHasilKerja: '{{ $tambahan->hasil_kerja ?? '' }}'
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
                                    <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md relative group realisasi-card-penilai @if($rTambahan->count() === 0 || trim((string) ($rTambahan->first()->realisasi ?? '')) === '') realisasi-card-empty @endif">
                                        
                                        <div class="flex items-start gap-2 mb-3 pr-6">
                                            <span class="font-bold text-gray-700 mt-1" x-text="(index + 1) + '.'"></span>
                                            <textarea x-model="item.text" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full border-gray-300 rounded-md shadow-sm text-xs p-2.5 focus:ring-blue-500 focus:border-blue-500 overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi..." :name="'realisasi[tambahan][' + {{ $tambahan->id }} + '][]'" readonly></textarea>
                                        </div>
                                        <div class="ml-6 bg-blue-50 p-2.5 rounded border border-blue-100 bukti-meta-print">
                                            
                                            <div x-show="item.file" class="mb-2">
                                                <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                            </div>
                                            
                                            
                                        </div>
                                    </div>
                                </template>
                                
                            </td>
                            <td class="align-top">
                                <textarea name="umpan_balik[tambahan_{{ $tambahan->id }}]" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full border-gray-300 bg-white rounded-md shadow-sm text-xs p-2.5 text-gray-800 focus:ring-blue-500 focus:border-blue-500 overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Tuliskan umpan balik...">{{ $tambahan->umpan_balik ?? '' }}</textarea>
                            </td>
                            <td class="align-middle text-center">
                                <select name="hasil_kerja[tambahan_{{ $tambahan->id }}]" x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-white rounded-md shadow-sm text-gray-800 border-gray-300 py-2">
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                    <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                    <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                </select>
                                {{-- Teks khusus cetak: mengikuti nilai Alpine selectedHasilKerja. --}}
                                <span class="print-only font-bold" x-text="selectedHasilKerja || '-'"></span>
                            </td>
                            <td class="align-middle text-center hidden">
                                <input type="number" :value="hasilKerjaMap[selectedHasilKerja]" readonly tabindex="-1" class="w-16 border-gray-200 bg-gray-50 rounded-md shadow-inner text-center font-bold text-gray-600 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none">
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-placeholder-print">
                            <td colspan="5" class="text-center text-gray-500 italic py-4">Tidak ada target tambahan.</td>
                        </tr>
                        @endforelse
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left">
                                <div class="mb-2">RATING HASIL KERJA*</div>
                                <textarea :value="rating" readonly tabindex="-1" class="print-hidden rating-penilai-print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                {{-- Teks khusus cetak: textarea di atas memakai binding :value
                                    (DOM property, bukan innerHTML) sehingga tidak ikut tercetak;
                                    nilainya digantikan elemen print-only berikut. --}}
                                <span class="print-only font-bold" x-text="rating || '-'"></span>
                            </td>
                        </tr>
                        </tbody>
                </table>

                <table class="table-kualitatif w-full mb-6" style="table-layout: fixed;">
                    {{-- Header PERILAKU KERJA ditaruh di <thead> asli: Chromium
                         mengulang thead di setiap halaman lanjutan saat cetak dan
                         rule 6b (thead page-break-inside:avoid + th page-break-after:avoid)
                         mencegah header tercetak sendirian di dasar halaman. --}}
                    <thead>
                        <!-- PERILAKU KERJA SECTION -->
                        <tr class="bg-gray-50 text-xs border-t-2 border-b-2 border-gray-300">
                            <th colspan="2" class="p-2 text-left w-[45%]">PERILAKU KERJA</th>
                            <th class="p-2 text-center w-[25%]">REALISASI PEGAWAI &amp; BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[20%]">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[10%]">HASIL KERJA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Helper label singkat untuk kotak bukti dukung Section D
                            // (pola sama persis dengan $perilakuLabel di view Dosen).
                            $perilakuLabel = function ($text, $urutan) {
                                $text = trim((string) $text);
                                if ($text === '') return 'Sub-Indikator ' . $urutan;
                                $t = strtolower($text);
                                $map = [
                                    'penelitian'   => 'Penelitian',
                                    'riset'        => 'Penelitian',
                                    'pengabdian'   => 'Pengabdian',
                                    'pengajaran'   => 'Pengajaran',
                                    'pembelajaran' => 'Pengajaran',
                                    'pendidikan'   => 'Pengajaran',
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
                                // Matching dinormalisasi (trim + mb_strtolower) agar kotak upload
                                // tidak hilang saat sasaran_kinerja master diedit kapitalisasi/spasi-nya.
                                $perilakuChildren = collect();
                                $acuanForAspek = $perilakuAcuan->first(fn($p) => trim(mb_strtolower($p->sasaran_kinerja ?? '')) === trim(mb_strtolower($perilaku->aspek ?? '')));
                                if ($acuanForAspek && $acuanForAspek->children) {
                                    $perilakuChildren = $acuanForAspek->children->where('butuh_lampiran', true)->values();
                                }
                            @endphp
                            <td class="align-top">
                                @if($perilakuChildren->isNotEmpty())
                                    @foreach($perilakuChildren as $cIdx => $child)
                                        @php
                                            $rChild = isset($realisasis) ? $realisasis->where('target_type', 'perilaku')->where('acuan_child_id', $child->id)->values() : collect();
                                            $childRow = $rChild->first();
                                            $cLabel = $childRow->label_realisasi ?? ($child->label_lampiran ?? $perilakuLabel($child->indikator_kinerja, $cIdx + 1));
                                        @endphp
                                        <div class="mb-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm realisasi-card-penilai @if(!$childRow || trim((string) $childRow->realisasi) === '') realisasi-card-empty @endif">
                                            <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5">
                                                <i class="fas fa-paperclip text-blue-600"></i>
                                                <span>{{ $cLabel }}</span>
                                            </div>
                                            <div class="whitespace-pre-wrap break-words max-h-40 overflow-y-auto text-gray-800 text-xs mb-2">{{ \App\Support\RealisasiRingkas::ringkas(trim((string) ($childRow->realisasi ?? ''))) ?: '-' }}</div>
                                            @if($childRow && $childRow->bukti_dukung_url)
                                                <div class="bukti-meta-print">
                                                    <a href="{{ asset($childRow->bukti_dukung_url) }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                                        <i class="fas fa-file mr-1"></i> {{ $childRow->bukti_dukung_nama ?? 'Lihat Bukti' }}
                                                    </a>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-400 italic bukti-meta-print">Tidak ada file bukti</div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic lampiran-optional-print">
                                        <i class="fas fa-info-circle mr-1"></i> Tidak wajib lampiran
                                    </div>
                                @endif
                            </td>
                            <td class="align-top">
                                <textarea name="umpan_balik[perilaku_{{ $perilaku->id }}]" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = ($el.scrollHeight + 4) + 'px' } }" x-init="$nextTick(() => resize())" @input="resize()" class="w-full border-gray-300 bg-white rounded-md shadow-sm text-xs p-2.5 text-gray-800 focus:ring-blue-500 focus:border-blue-500 overflow-hidden resize-none" style="min-height: 120px;" rows="5" placeholder="Tuliskan umpan balik...">{{ $perilaku->umpan_balik ?? '' }}</textarea>
                            </td>
                            <td class="align-middle text-center">
                                <select name="hasil_kerja[perilaku_{{ $perilaku->id }}]" x-model="selectedHasilKerja" class="w-full text-xs font-bold border-gray-200 bg-white rounded-md shadow-sm text-gray-800 border-gray-300 py-2">
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="Sesuai Ekspektasi">Sesuai Ekspektasi</option>
                                    <option value="Diatas Ekspektasi">Diatas Ekspektasi</option>
                                    <option value="Dibawah Ekspektasi">Dibawah Ekspektasi</option>
                                </select>
                                {{-- Teks khusus cetak: mengikuti nilai Alpine selectedHasilKerja. --}}
                                <span class="print-only font-bold" x-text="selectedHasilKerja || '-'"></span>
                            </td>
                        </tr>
                        @endforeach
<tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">RATING PERILAKU KERJA*</div>
                                <textarea :value="ratingPerilaku" readonly tabindex="-1" class="print-hidden rating-penilai-print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                <span class="print-only font-bold" x-text="ratingPerilaku || '-'"></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">PREDIKAT KINERJA PEGAWAI*</div>
                                <textarea :value="predikatKinerja" readonly tabindex="-1" class="print-hidden rating-penilai-print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                <span class="print-only font-bold" x-text="predikatKinerja || '-'"></span>
                            </td>
                        </tr>
</tbody>
                </table>
                </fieldset>
                @if(!in_array(strtolower($skpKualitatif->skpHeader->status ?? ''), ['draft', 'selesai']))
                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3 no-print">
    <input type="hidden" name="status" id="skp_status_input" value="Draft">
    
    <button type="submit" onclick="document.getElementById('skp_status_input').value='Draft'" class="inline-flex items-center justify-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none">
        <i class="fas fa-save mr-2"></i> Simpan Sementara
    </button>

    
    <button type="submit" onclick="document.getElementById('skp_status_input').value='Selesai'" class="inline-flex items-center justify-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none">
        <i class="fas fa-check-double mr-2"></i> Setujui & Finalisasi
    </button>
</div>
                @endif
                </form>
            </div>

            <!-- BAGIAN LAMPIRAN SKP -->
            <div x-show="activeTab === 'lampiran'" x-cloak class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
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
                
            </div>
        
            <!-- DOKUMEN EVALUASI TAB -->
            <div x-show="activeTab === 'dokumen'" x-cloak class="overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
                <div class="text-center font-bold mb-4 space-y-1">
                    <img src="{{ asset('images/umsuraicon.png') }}" alt="Logo UMSurabaya" class="h-32 mx-auto mb-4 object-contain">
                    <p>DOKUMEN EVALUASI KINERJA PEGAWAI</p>
                </div>
                <div class="flex justify-between font-bold mb-4 uppercase text-sm">
                    <div>
                        <p>PERIODE: {{ $skpKualitatif->skpHeader->periode->triwulan_label ?? '-' }}</p>
                        <p>{{ $skpKualitatif->skpHeader->user->fakultas_nama ?? config('institusi.nama_fakultas') }}</p>
                    </div>
                    <div class="text-right">
                        <p>PERIODE PENILAIAN:</p>
                        <p>{{ $skpKualitatif->skpHeader->periode->periode_penilaian_label ?? '-' }}</p>
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
                            <td>{{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">NIP</td>
                            <td>{{ $bawahan->profile->nip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">PANGKAT/GOL. RUANG</td>
                            <td>{{ $bawahan->profile->pangkat_golongan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">JABATAN</td>
                            <td>{{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td>
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
                            <td class="font-bold">CAPAIAN KINERJA ORGANISASI (KATEGORI DISTRIBUSI)</td>
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
                                <textarea form="evaluasi-form" name="catatan_penilai" class="w-full border-0 focus:ring-0 resize-none p-4" rows="4" @if(in_array(strtolower($skpKualitatif->skpHeader->status ?? ''), ['selesai'])) disabled @endif>{{ $skpKualitatif->skpHeader->catatan_penilai ?? '' }}</textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            @if(!in_array(strtolower($skpKualitatif->skpHeader->status ?? ''), ['draft', 'selesai']))
            <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3 no-print">
                <button type="submit" form="evaluasi-form" onclick="document.getElementById('skp_status_input').value='Draft'" class="inline-flex items-center justify-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none">
                    <i class="fas fa-save mr-2"></i> Simpan Sementara
                </button>
                <button type="submit" form="evaluasi-form" onclick="document.getElementById('skp_status_input').value='Selesai'" class="inline-flex items-center justify-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none">
                    <i class="fas fa-check-double mr-2"></i> Setujui & Finalisasi
                </button>
            </div>
            @endif
        </div>
    @endsection
</x-app-layout>