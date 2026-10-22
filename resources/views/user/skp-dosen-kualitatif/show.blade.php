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
                    <button type="button" onclick="window.print()" class="no-print inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
@if(strtolower($skpKualitatif->skpHeader->status ?? '') != 'draft' && !($isRealisasiSaved ?? false))
                    <button type="button" @click="activeTab = 'evaluasi'; $nextTick(() => document.getElementById('formEvaluasiPegawai').requestSubmit())" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-save mr-2"></i> Simpan Realisasi
                    </button>
                    @endif
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

                /* Elemen khusus cetak: pengganti teks dari kontrol form.
                   Tersembunyi di layar, ditampilkan hanya saat print. */
                .print-only { display: none !important; }

                @media print {
                    /* 1) Sembunyikan kontrol form & tombol (input file, input teks,
                          select, tombol tambah/hapus). Textarea realisasi ikut
                          disembunyikan dan isinya digantikan elemen .print-only.
                          Textarea read-only (umpan balik, rating, predikat) TIDAK
                          disembunyikan di sini — digaya ulang jadi teks polos di (1b). */
                    #print-area form input,
                    #print-area form select,
                    #print-area form button,
                    #print-area .no-print,
                    #print-area [data-pdf-status] { display: none !important; }

                    /* 1b) Textarea read-only (umpan balik, rating, predikat kinerja)
                            tampil sebagai teks polos tanpa bingkai, tanpa scrollbar,
                            dan tanpa batas tinggi supaya seluruh isinya tercetak. */
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

                    /* 1c) Textarea realisasi (punya pengganti .print-only) tetap
                            disembunyikan. Ditandai lewat name field-nya. */
                    #print-area form textarea[name^="realisasi"] { display: none !important; }

                    /* 2) Kotak "Upload Bukti" (label upload, input file, nama file,
                          pesan status ekstraksi PDF) tidak ikut dicetak. */
                    #print-area .pdf-upload-box { display: none !important; }

                    /* 3) Isi realisasi/umpan balik/rating tampil sebagai teks biasa:
                          membungkus sesuai lebar kolom, tanpa scrollbar, tanpa
                          batas tinggi yang memotong isi. */
                    #print-area .print-only {
                        display: block !important;
                        white-space: pre-wrap;
                        overflow-wrap: break-word;
                        word-break: break-word;
                        min-height: 0 !important;
                        max-height: none !important;
                        overflow: visible !important;
                    }

                    /* 3f) Textarea rating/predikat (nilai terikat :value Alpine,
                            bukan isi server) disembunyikan saat print — sudah
                            diwakili elemen .print-only di sampingnya. */
                    #print-area .print-hidden { display: none !important; }

                    /* 3c) Label layar (judul subbab + ikon paperclip) TIDAK dicetak:
                            judul sudah diwakili elemen [data-label-print] khusus cetak,
                            jadi tidak boleh muncul dua kali. */
                    #print-area .area-cetak td .realisasi-label-screen { display: none !important; }

                    /* 3d) Kartu yang ditandai kosong oleh syncPrintRealisasi()
                            (nilai realisasi kosong/whitespace saja) disembunyikan
                            SELURUHNYA saat cetak — termasuk judul subbabnya.
                            Menyembunyikan [data-realisasi-print] kosong juga
                            menutup item kosong bawaan di B. TAMBAHAN. */
                    #print-area [data-realisasi-card].realisasi-card-empty { display: none !important; }
                    #print-area [data-realisasi-print]:empty { display: none !important; }

                    /* 3e) Nama/tautan file bukti tidak dicetak: hasil cetak tiap
                            kartu cukup judul subbab + isi realisasi. */
                    #print-area .area-cetak td .bukti-nama-print { display: none !important; }

                    /* 3b) Pengaman: sebelum mencetak, salin isi textarea realisasi
                           (yang dikelola Alpine x-model) ke elemen .print-only
                           penggantinya. Ini menjamin versi cetak selalu menampilkan
                           nilai yang SEBENARNYA terlihat di layar — baik yang sudah
                           tersimpan (pre-fill server) maupun yang baru diketik/diisi
                           otomatis dari PDF dan belum tersimpan. Tanpa ini, elemen
                           cetak berisi snapshot server saat render blade saja. */
                    #print-area [data-realisasi-print]:not(:empty) {
                        min-height: 1em;
                    }

                    /* 4) Kartu/kotak dekoratif di dalam sel tabel dipoloskan agar
                          hasil cetak mendekati format dokumen asli. */
                    #print-area .area-cetak td .rounded-lg,
                    #print-area .area-cetak td .rounded-md,
                    #print-area .area-cetak td .rounded {
                        border: none !important;
                        box-shadow: none !important;
                        background: transparent !important;
                        padding: 0 !important;
                        border-radius: 0 !important;
                    }

                    /* 5) Pemenggalan halaman: tabel & baris boleh menyambung antar
                          halaman supaya tidak ada ruang kosong besar di ekor halaman
                          saat satu baris lebih tinggi dari sisa ruang. Granularitas
                          pemenggalan dipindah ke kartu realisasi: kartu tidak boleh
                          terpotong di tengah — seluruh kartu berpindah utuh ke
                          halaman berikutnya bila tidak muat. */
                    #print-area table { page-break-inside: auto; }
                    #print-area tr { page-break-inside: auto; }
                    /* Kartu realisasi tidak boleh terpotong di tengah — TAPI hanya
                       bila muat dalam satu halaman. Kartu sangat panjang (lebih tinggi
                       dari 1 halaman) dengan avoid akan didorong UTUH ke halaman
                       berikutnya meninggalkan halaman kosong besar (bug hal. 2). Solusi:
                       avoid tetap untuk kartu normal; kartu panjang diberi min-height 0
                       sehingga engine boleh memecahnya di dalam kartu, bukan memindah-
                       kan utuh. Deteksi kartu panjang via max-height + overflow visible
                       memungkinkan pemenggalan natural. */
                    #print-area [data-realisasi-card] {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        /* Kartu yang tingginya melebihi sisa halaman tetap boleh dipecah
                           — hindari halaman kosong akibat pemindahan utuh. */
                        max-height: none;
                    }
                    /* Kartu SANGAT panjang (lebih tinggi dari satu halaman cetak):
                       dipindah utuh akan menyisakan halaman kosong (bug hal. 2),
                       maka boleh dipecah antar halaman. Class ditandai oleh
                       markTallRealisasiCards() sebelum cetak berdasarkan tinggi
                       kartu vs tinggi area cetak. */
                    #print-area [data-realisasi-card].realisasi-card-tall {
                        page-break-inside: auto;
                        break-inside: auto;
                    }
                    #print-area td { overflow-wrap: break-word; }

                    /* 6) Proporsi kolom cetak TABEL EVALUASI UTAMA: Hasil Kerja
                          36% (lebih lebar — uraian target/indikator panjang),
                          Realisasi 24%, Umpan Balik 26%, Hasil Kerja (nilai)
                          14%. Berlaku di HEADER dan seluruh SEL body (kolom 5
                          "ANGKA" tetap hidden via class Tailwind). Selektor
                          hanya menarget thead/tbody tr yang memiliki sel
                          realisasi (tabel identitas tidak terdampak karena
                          strukturnya tidak memiliki 4+ kolom berurutan dengan
                          thead 5 kolom — tetap diamankan dengan nth-child
                          pada baris ber-kartu saja tidak praktis; tabel
                          identitas memakai <th> colspan berbeda sehingga
                          nth-child-nya juga cocok — karena itu dibatasi ke
                          elemen yang berada SETELAH tabel evaluasi dengan
                          selector :has td [data-realisasi-card] tidak reliable
                          di print; solusi aman: batasi ke table yang berisi
                          [data-realisasi-card] via :has()). */
                    #print-area table.table-kualitatif:has([data-realisasi-card]) thead th:nth-child(1),
                    #print-area table.table-kualitatif:has([data-realisasi-card]) tbody td:nth-child(1) { width: 36% !important; }
                    #print-area table.table-kualitatif:has([data-realisasi-card]) thead th:nth-child(2),
                    #print-area table.table-kualitatif:has([data-realisasi-card]) tbody td:nth-child(2) { width: 24% !important; }
                    #print-area table.table-kualitatif:has([data-realisasi-card]) thead th:nth-child(3),
                    #print-area table.table-kualitatif:has([data-realisasi-card]) tbody td:nth-child(3) { width: 26% !important; }
                    #print-area table.table-kualitatif:has([data-realisasi-card]) thead th:nth-child(4),
                    #print-area table.table-kualitatif:has([data-realisasi-card]) tbody td:nth-child(4) { width: 14% !important; }

                    /* 6b) Judul tabel tidak terpisah dari baris isinya saat
                           halaman berganti. */
                    #print-area table.table-kualitatif thead { page-break-inside: avoid; break-inside: avoid; }
                    #print-area table.table-kualitatif thead th { page-break-after: avoid; break-after: avoid; }
                    /* Header tabel perilaku (PERILAKU KERJA | REALISASI | UMPAN BALIK | HASIL KERJA)
                       jangan terpisah dari baris pertamanya — sebelumnya nyangkut sendirian
                       di dasar halaman (bug hal. 4). */
                    #print-area table.table-perilaku thead { page-break-inside: avoid; break-inside: avoid; }
                    #print-area table.table-perilaku thead th { page-break-after: avoid; break-after: avoid; }
                    /* Baris tabel kualitatif: baris pertama grup (A. UTAMA dst.) jangan
                       terpisah dari isi di belakangnya. */

                    /* 6b-3) Grup "judul + baris data pertama" (markup beberapa tbody):
                       Chromium menghormati break-inside:avoid pada tbody, tidak pada
                       break-after:avoid antar-baris — judul grup (HASIL KERJA /
                       A. UTAMA / B. TAMBAHAN / PERILAKU KERJA*) tidak boleh tercetak
                       sendirian di dasar halaman. */
                    #print-area table.table-kualitatif tbody.keep-with-next-print,
                    #print-area table.table-perilaku tbody.keep-with-next-print {
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

                    /* 6c) Tipografi cetak: isi utama minimal 9pt agar tetap
                           terbaca (11px = 8.25pt terlalu kecil). Padding sel
                           ikut disesuaikan agar format resmi tetap lega. */
                    #print-area table.table-kualitatif th,
                    #print-area table.table-kualitatif td {
                        font-size: 12px !important; /* 9pt */
                        line-height: 1.35 !important;
                        padding: 5px 7px !important;
                    }

                    /* 6d) Placeholder kekosongan data tidak dicetak — dokumen
                           resmi tidak boleh menampilkan catatan internal
                           seperti "Tidak ada target tambahan." atau
                           "Belum ada data Kinerja Utama." (baris direktif
                           empty Blade di tabel SKP tab SKP Pegawai & Evaluasi). */
                    #print-area tr.empty-placeholder-print { display: none !important; }
                    #print-area td.empty-placeholder-print { display: none !important; }

                    /* 7) Jarak antar kartu realisasi saat cetak dirapatkan agar
                          beberapa lampiran tersusun kompak satu di bawah yang lain. */
                    #print-area [data-realisasi-card] { margin-bottom: 6px !important; }

                    /* 8) Opsi B — pemecahan baris untuk baris yang berisi BEBERAPA
                          kartu realisasi: kartu pertama tetap di baris asli
                          (sejajar kolom Hasil Kerja), kartu berikutnya dipindah
                          applyPrintRowSplit() ke baris lanjutan tersendiri sehingga
                          tinggi tiap baris mengikuti isinya — tidak ada ruang kosong
                          besar di bawah teks yang pendek. Sel pengiri/pengisi baris
                          lanjutan tanpa garis & tanpa padding sehingga tercetak
                          hanya kotak realisasinya (tampak seperti lanjutan baris). */
                    #print-area tr.print-split-row > td.print-split-empty {
                        border: none !important;
                        padding: 0 !important;
                    }

                    /* 9) Kasus SATU realisasi: TIDAK ada penyeimbang vertikal.
                          Teks tetap di ATAS sel (vertical-align: top default)
                          sesuai format dokumen resmi — vertical-align: middle
                          pernah dicoba dan dihapus karena hanya memindahkan
                          teks ke tengah tanpa mengurangi tinggi baris dan
                          terlihat janggal. Cara SATU-SATUNYA mengurangi ruang
                          kosong pada baris ber-satu-realiasi adalah memecah
                          baris seperti kasus multi-kartu (lihat fungsi
                          applyPrintRowSplit dan opsi desain di bawah). */

                    #print-area .area-cetak,
                    #print-area > div { border: none !important; box-shadow: none !important; }

                    /* 10) Tabel identitas (PEGAWAI YANG DINILAI / PEJABAT
                           PENILAI KINERJA): pola kolom cetak jelas dan konsisten
                           antara sisi pegawai & penilai — nomor | label | nilai.
                           Span label & nilai diberi lebar tetap sehingga nilai
                           selalu mulai pada posisi yang sama antar baris dan
                           antar sisi; nilai panjang wrap rapi di dalam sel tanpa
                           mendorong lebar kolom. Tampilan layar tidak berubah
                           (class ident-* tanpa efek di luar @media print). */
                    #print-area td .ident-label {
                        display: inline-block !important;
                        width: 42% !important;
                        min-width: 110px !important;
                        vertical-align: top !important;
                    }
                    #print-area td .ident-value {
                        display: inline-block !important;
                        width: 56% !important;
                        vertical-align: top !important;
                        overflow-wrap: break-word !important;
                        word-break: break-word !important;
                        white-space: normal !important;
                    }

                    /* Border bawah baris tabel yang terpotong pindah halaman:
                          Chromium mode border-collapse:collapse TIDAK menggambar
                          border bawah fragmen baris saat baris panjang terbelah
                          di batas halaman. Solusi khusus cetak: model border
                          terpisah (separate) dengan border per-sel — setiap
                          fragmen sel menggambar border sendiri sehingga
                          potongan halaman selalu tertutup garis rapi.
                          Tanpa efek di layar (hanya @media print). */
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
                <script>
                    // Sinkronisasi isi cetak SEBELUM dialog print dibuka (window.print
                    // diblokir sinkron saat beforeprint berjalan, jadi disalin di sini).
                    // Setiap textarea realisasi dipasangkan dengan elemen
                    // [data-realisasi-print] di dalam pembungkus [data-realisasi-card]
                    // yang sama (bukan lewat [x-data] — textarea dan <td> juga punya
                    // x-data sendiri) supaya teks tidak pernah bergeser/tertukar ke
                    // item lain. Nilai yang disalin = nilai SEBENARNYA di textarea
                    // (Alpine x-model, termasuk teks hasil ekstraksi PDF yang belum
                    // tersimpan); bila kosong, kembali ke snapshot teks tersimpan
                    // server (data-print-fallback).
                    //
                    // Selain menyalin isi, fungsi ini menandai kartu yang realisasinya
                    // KOSONG (hanya whitespace dianggap kosong) dengan class
                    // .realisasi-card-empty — CSS @media print menyembunyikan kartu
                    // bertanda itu SELURUHNYA (judul ikut hilang), sehingga kartu
                    // tanpa isi dan item kosong bawaan B. TAMBAHAN tidak tercetak.
                    function syncPrintRealisasi() {
                        document.querySelectorAll('#print-area form textarea[name^="realisasi"]').forEach(function (ta) {
                            // Pasangkan textarea dengan elemen cetak milik KARTU yang
                            // sama: naik ke pembungkus [data-realisasi-card] terdekat,
                            // lalu ambil [data-realisasi-print] DI DALAM kartu itu.
                            // CATATAN: tidak memakai closest('[x-data]') karena textarea
                            // realisasi sendiri punya x-data (fungsi resize) dan pada
                            // section B. TAMBAHAN <td> juga ber-x-data (array items) —
                            // keduanya bisa membuat pasangan meleset ke item lain.
                            var card = ta.closest('[data-realisasi-card]');
                            var printEl = card ? card.querySelector('[data-realisasi-print]') : null;
                            if (!printEl) return;
                            var val = (ta.value || '').trim();
                            var fallback = (printEl.getAttribute('data-print-fallback') || '').trim();
                            var effective = val !== '' ? ta.value : fallback;
                            // Batasi teks cetak (maks 30 kata per dokumen) memakai
                            // helper yang SAMA dengan ekstraksi PDF
                            // (window.limitRealisasiWords, diekspos dari
                            // resources/js/app.js) supaya web & print konsisten dan
                            // dokumen cetak tetap ringkas walau teks realisasi
                            // panjang. Aman utk halaman lama: helper ada karena
                            // dibundel bersama halaman ini.
                            if (typeof window.limitRealisasiWords === 'function') {
                                effective = window.limitRealisasiWords(effective, window.REALISASI_MAX_WORDS || 30);
                            }
                            printEl.textContent = effective;
                            // Kartu kosong = tidak ada teks di textarea DAN tidak ada
                            // snapshot tersimpan. Judul & seluruh kartunya disembunyikan
                            // saat print lewat CSS (.realisasi-card-empty).
                            if (card) {
                                card.classList.toggle('realisasi-card-empty', effective.trim() === '');
                            }
                        });
                    }
                    window.addEventListener('beforeprint', syncPrintRealisasi);

                    // Opsi B — pemecahan baris saat cetak untuk baris yang berisi
                    // BEBERAPA kartu realisasi (multi-lampiran):
                    // - Kartu PERTAMA yang berisi dibiarkan di baris asli, sejajar
                    //   kolom Hasil Kerja (dokumen tetap resmi: judul & isi utama
                    //   tetap satu baris dengan targetnya).
                    // - Kartu berikutnya yang berisi dipindah ke <tr> lanjutan baru
                    //   (disisipkan tepat setelah baris asli). Kolom hasil kerja,
                    //   umpan balik, dan hasil kerja pada baris lanjutan dibiarkan
                    //   kosong tanpa garis (class print-split-empty), sehingga
                    //   tercetak hanya kotak realisasinya — tinggi tiap baris
                    //   mengikuti isinya dan tidak ada ruang kosong besar.
                    // - Kartu kosong tidak dipindah (sudah disembunyikan CSS).
                    //
                    // Kasus SATU realisasi: baris TIDAK diberi perlakuan khusus
                    // lagi — teks tetap di atas sel (format dokumen resmi).
                    // (vertical-align: middle sudah dihapus: hanya memindahkan
                    // teks ke tengah tanpa mengurangi tinggi baris.)
                    // Fungsi idempotent: elemen yang sudah dipindah ditandai
                    // data-print-split supaya cetak ulang tidak menduplikasi.
                    // PEMULIHAN LAYOUT: semua mutasi DOM (kartu dipindah ke
                    // baris lanjutan) dicatat di _printMutations; restorePrintLayout()
                    // mengembalikan kartu ke posisi aslinya dan menghapus baris
                    // lanjutan. Dipanggil di 'afterprint' (dialog ditutup — baik
                    // selesai maupun dibatalkan) dan sebelum mutasi berikutnya,
                    // sehingga layout LAYAR selalu kembali persis seperti semula.
                    var _printMutations = [];

                    // Kartu realisasi yang tingginya MELEBIHI satu halaman cetak
                    // tidak boleh dipaksa utuh di satu halaman (break-inside: avoid
                    // akan mendorongnya utuh ke halaman berikutnya & menyisakan
                    // halaman kosong — bug hal. 2). Kartu sedemikian ditandai
                    // .realisasi-card-tall sehingga CSS memperbolehkan pemenggalan
                    // alami antar halaman. Pemulihan: class dihapus di afterprint.
                    function markTallRealisasiCards() {
                        var pageH = window.innerHeight || 700;
                        document.querySelectorAll('#print-area [data-realisasi-card]').forEach(function (card) {
                            if (card.offsetHeight > pageH) {
                                card.classList.add('realisasi-card-tall');
                            }
                        });
                    }
                    function unmarkTallRealisasiCards() {
                        document.querySelectorAll('#print-area .realisasi-card-tall').forEach(function (card) {
                            card.classList.remove('realisasi-card-tall');
                        });
                    }

                    function applyPrintRowSplit() {
                        // Bersihkan sisa mutasi sesi cetak sebelumnya dulu.
                        restorePrintLayout();

                        document.querySelectorAll('#print-area table tr').forEach(function (tr) {
                            var tds = tr.children;
                            // Hanya baris data realisasi: td kedua memuat kartu.
                            if (!tds || tds.length < 2) return;
                            var cardTd = tds[1];
                            if (!cardTd || cardTd.tagName !== 'TD') return;
                            var cards = cardTd.querySelectorAll(':scope > [data-realisasi-card]');
                            if (cards.length === 0) return;

                            var filled = Array.prototype.filter.call(cards, function (c) {
                                return !c.classList.contains('realisasi-card-empty');
                            });

                            if (filled.length <= 1) {
                                return;
                            }

                            // Beberapa realisasi: pindahkan kartu ke-2 dst ke baris lanjutan.
                            var nCols = tds.length;
                            var insertAfter = tr;
                            filled.slice(1).forEach(function (card) {
                                var splitRow = document.createElement('tr');
                                splitRow.className = 'print-split-row';
                                for (var i = 0; i < nCols; i++) {
                                    var td = document.createElement('td');
                                    if (i === 1) {
                                        td.appendChild(card);
                                    } else {
                                        td.className = 'print-split-empty';
                                    }
                                    splitRow.appendChild(td);
                                }
                                insertAfter.after(splitRow);
                                // Catat mutasi untuk pemulihan: kartu kembali ke
                                // posisi semula (sebelum nextSibling awalnya), baris
                                // lanjutan dihapus.
                                _printMutations.push({
                                    card: card,
                                    originalParent: cardTd,
                                    originalNext: card.nextSibling,
                                    splitRow: splitRow
                                });
                                insertAfter = splitRow;
                            });
                        });
                    }

                    // Kembalikan struktur DOM ke kondisi SEBELUM cetak: kartu
                    // dikembalikan ke td aslinya di urutan semula, baris lanjutan
                    // dihapus, lalu catatan mutasi dikosongkan.
                    function restorePrintLayout() {
                        _printMutations.forEach(function (m) {
                            m.originalParent.insertBefore(m.card, m.originalNext);
                            m.splitRow.remove();
                        });
                        _printMutations = [];
                    }
                    window.addEventListener('beforeprint', applyPrintRowSplit);
                    window.addEventListener('beforeprint', markTallRealisasiCards);
                    // afterprint fire baik saat dialog DITUTUP setelah mencetak
                    // maupun saat DIBATALKAN — layout layar selalu dipulihkan.
                    window.addEventListener('afterprint', restorePrintLayout);
                    window.addEventListener('afterprint', unmarkTallRealisasiCards);
                    document.addEventListener('click', function (e) {
                        if (e.target.closest && e.target.closest('button[onclick*="window.print"]')) {
                            syncPrintRealisasi();
                            applyPrintRowSplit();
                            markTallRealisasiCards();
                            // Kembalikan layout setelah dialog tertutup; window.print
                            // sinkron di Chrome (memblokir sampai dialog ditutup).
                            setTimeout(function () {
                                restorePrintLayout();
                                unmarkTallRealisasiCards();
                            }, 0);
                        }
                    }, true);
                </script>

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
                            <td><span class="inline-block w-48 font-bold ident-label">NAMA</span> <span class="ident-value">{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NAMA</span> <span class="ident-value">{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NIP</span> <span class="ident-value">{{ $user->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NIP</span> <span class="ident-value">{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $user->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold ident-label">JABATAN</span> <span class="ident-value">{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold ident-label">JABATAN</span> <span class="ident-value">{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
                        </tr>
                    </tbody>

                    <!-- HASIL KERJA: judul grup + baris data pertama dalam satu tbody
                         keep-with-next-print agar tidak tercetak sendirian di dasar halaman -->
                    <tbody class="keep-with-next-print">
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
                        </tbody>
                        <tbody>

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

                        {{-- B. TAMBAHAN: judul grup + baris data pertama di satu tbody
                             dengan break-inside:avoid (class keep-with-next-print)
                             agar judul tidak tercetak sendirian di dasar halaman. --}}
                        </tbody>
                        <tbody class="keep-with-next-print">
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
                            <tr class="empty-placeholder-print">
                                <td class="text-center font-bold w-12 align-top pt-4">1</td>
                                <td colspan="3" class="align-top text-gray-500 italic">
                                    Tidak ada target tambahan.
                                </td>
                            </tr>
                        @endforelse
                        @if($tambahans->isNotEmpty())
                    </tbody>

                    <!-- PERILAKU KERJA -->
                     <tbody class="keep-with-next-print">
                        @endif
                         <tr class="bg-light-gray">
                             <td colspan="4" class="font-bold">PERILAKU KERJA*</td>
                         </tr>
                         @foreach($perilakus as $index => $perilaku)
                            @if($loop->first)
                            <tbody>
                            @endif
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
                <form id="formEvaluasiPegawai" action="{{ route('pegawai.skp-dosen-kualitatif.store-realisasi', $skpKualitatif->skpHeader->id ?? 1) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <fieldset @if(strtolower($skpKualitatif->skpHeader->status ?? '') == 'draft') disabled @endif>
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
                            <td><span class="inline-block w-48 font-bold ident-label">NAMA</span> <span class="ident-value">{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</span></td>
                            <td class="text-center font-bold">1</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NAMA</span> <span class="ident-value">{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NIP</span> <span class="ident-value">{{ $user->profile->nip ?? '-' }}</span></td>
                            <td class="text-center font-bold">2</td>
                            <td><span class="inline-block w-48 font-bold ident-label">NIP</span> <span class="ident-value">{{ $penilai->profile->nip ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $user->profile->pangkat_golongan ?? '-' }}</span></td>
                            <td class="text-center font-bold">3</td>
                            <td><span class="inline-block w-48 font-bold ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold ident-label">JABATAN</span> <span class="ident-value">{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                            <td class="text-center font-bold">4</td>
                            <td><span class="inline-block w-48 font-bold ident-label">JABATAN</span> <span class="ident-value">{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
                            <td class="text-center font-bold">5</td>
                            <td><span class="inline-block w-48 font-bold ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
                        </tr>
                </tbody>
            </table>

            <table class="table-kualitatif mb-6 w-full">
                    <tbody>
                        <tr>
                            <td class="p-4">
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
                            // Kotak upload = SNAPSHOT terkunci di skp_kualitatifs.lampiran_snapshot
                            // (dikunci saat SKP dibuat/diedit/sinkron), BUKAN live dari master Acuan.
                            // Konsisten dengan teks target yang juga snapshot. Fallback: row lama
                            // tanpa snapshot & parent masih ada -> live (grandfathered).
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
                                        <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md" x-data="{ item: {{ $cData }} }" data-realisasi-card>
                                            {{-- Teks khusus cetak: judul subbab + isi realisasi, tampil
                                                 di print sebagai teks biasa menggantikan kontrol form.
                                                 Nama/tautan file bukti TIDAK dicetak. Judul layar
                                                 (.realisasi-label-screen) disembunyikan saat print.
                                                 data-print-fallback = snapshot teks tersimpan server,
                                                 dipakai bila textarea kosong saat cetak. --}}
                                            <div class="print-only font-bold text-xs mb-1" data-label-print>{{ $cLabel }}</div>
                                            <div class="print-only break-words" data-realisasi-print data-print-fallback="{{ $childRow->realisasi ?? '' }}">{{ $childRow->realisasi ?? '' }}</div>
                                            <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5 realisasi-label-screen">
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
                                            <div x-data="{ words: 0, resize() { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight + 4, 160) + 'px' }, limitWords() { if (typeof window.limitRealisasiWords === 'function' && window.limitRealisasiWords($el.value) !== $el.value) { $el.value = window.limitRealisasiWords($el.value); } this.words = (window.limitRealisasiWords ? window.limitRealisasiWords($el.value).split(/\s+/).filter(Boolean).length : 0); $nextTick(() => resize()) } }">
                                            <textarea x-model="item.text" x-init="words = $el.value.trim() ? $el.value.trim().split(/\s+/).length : 0; $nextTick(() => resize())" @input="limitWords()" class="w-full rounded-md shadow-sm text-xs p-2.5 max-h-40 overflow-y-auto resize-y {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-200' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi..." name="realisasi[utama][{{ $child->id }}]" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}>{{ $childRow->realisasi ?? '' }}</textarea>
                                            @unless($isRealisasiSaved)
                                            <div class="text-right text-[10px] text-gray-400 mt-0.5" x-text="words.toLocaleString('id-ID') + '/30 kata'"></div>
                                            @endunless
                                            </div>
                                            <div class="mt-3 bg-blue-50 p-2.5 rounded border border-blue-100 pdf-upload-box" x-init="initPdfBukti($el)">
                                                @if(!$isRealisasiSaved)
                                                <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                    <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                                </label>
                                                @endif
                                                <div x-show="item.file" class="mb-2">
                                                    <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                                </div>
                                                @if(!$isRealisasiSaved)
                                                <input type="file" data-pdf-input name="bukti_dukung[utama][{{ $child->id }}]" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md">
                                                <p data-pdf-status class="text-[10px] mt-1 italic hidden"></p>
                                                <p class="text-[10px] text-gray-500 mt-1 italic">* Wajib unggah minimal 1 bukti pendukung</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic no-print">
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
                                {{-- Nilai hasil kerja khusus cetak: select disembunyikan
                                     saat print, nilainya diwakili teks print-only ini. --}}
                                <span class="print-only font-bold" x-text="selectedHasilKerja || '-'"></span>
                            </td>
                            <td class="align-middle text-center hidden">
                                <input type="number" :value="hasilKerjaMap[selectedHasilKerja]" readonly tabindex="-1" class="w-16 border-gray-200 bg-gray-50 rounded-md shadow-inner text-center font-bold text-gray-600 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 italic py-4 empty-placeholder-print">Belum ada data Kinerja Utama.</td>
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
                                    <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md relative group" data-realisasi-card>
                                        {{-- Teks khusus cetak: ikut state Alpine (item.text) karena
                                             textarea masih bisa diedit sebelum disimpan.
                                             data-print-fallback = teks tersimpan server (item pertama). --}}
                                        <div class="print-only font-bold text-xs mb-1" data-label-print>Realisasi</div>
                                        <div class="print-only break-words" data-realisasi-print data-print-fallback="{{ $rTambahan->first()->realisasi ?? '' }}"><span x-text="(index + 1) + '.'"></span> <span x-text="item.text"></span></div>
                                        @if(!$isRealisasiSaved)
                                        <button @click="items.splice(index, 1)" x-show="items.length > 1" type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                        <div class="flex items-start gap-2 mb-3 pr-6">
                                            <span class="font-bold text-gray-700 mt-1" x-text="(index + 1) + '.'"></span>
                                            <div x-data="{ words: 0, resize() { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight + 4, 160) + 'px' }, limitWords() { if (typeof window.limitRealisasiWords === 'function' && window.limitRealisasiWords($el.value) !== $el.value) { $el.value = window.limitRealisasiWords($el.value); } this.words = (window.limitRealisasiWords ? window.limitRealisasiWords($el.value).split(/\s+/).filter(Boolean).length : 0); $nextTick(() => resize()) } }">
                                            <textarea x-model="item.text" x-init="words = $el.value.trim() ? $el.value.trim().split(/\s+/).length : 0; $nextTick(() => resize())" @input="limitWords()" class="w-full rounded-md shadow-sm text-xs p-2.5 max-h-40 overflow-y-auto resize-y {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-200' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi..." :name="'realisasi[tambahan][' + {{ $tambahan->id }} + '][]'" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}></textarea>
                                            @unless($isRealisasiSaved)
                                            <div class="text-right text-[10px] text-gray-400 mt-0.5" x-text="words.toLocaleString('id-ID') + '/30 kata'"></div>
                                            @endunless
                                            </div>
                                        </div>
                                        <div class="ml-6 bg-blue-50 p-2.5 rounded border border-blue-100 pdf-upload-box" x-init="initPdfBukti($el)">
                                            @if(!$isRealisasiSaved)
                                            <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                            </label>
                                            @endif
                                            <div x-show="item.file" class="mb-2">
                                                <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                            </div>
                                            @if(!$isRealisasiSaved)
                                            <input type="file" data-pdf-input :name="'bukti_dukung[tambahan][' + {{ $tambahan->id }} + '][]'" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md" multiple>
                                            <p data-pdf-status class="text-[10px] mt-1 italic hidden"></p>
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
                                {{-- Nilai hasil kerja khusus cetak (B. TAMBAHAN). --}}
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
                                <textarea :value="rating" readonly tabindex="-1" class="print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                {{-- Nilai rating khusus cetak: textarea terikat :value
                                     Alpine (bukan isi server) sehingga tidak ikut
                                     tercetak; diwakili teks print-only berikut. --}}
                                <span class="print-only font-bold" x-text="rating || '-'"></span>
                            </td>
                        </tr>
                        </tbody>
                </table>

                <table class="table-kualitatif table-perilaku w-full mb-6" style="table-layout: fixed;">
                    {{-- Header di <thead>: Chromium mengulang thead di halaman lanjutan
                         saat tabel terpotong, dan rule thead pada @media print mencegah
                         header tercetak sendirian di dasar halaman. --}}
                    <thead>
                        <tr class="bg-gray-50 text-xs border-t-2 border-b-2 border-gray-300">
                            <th colspan="2" class="p-2 text-left w-[45%]">PERILAKU KERJA</th>
                            <th class="p-2 text-center w-[25%]">REALISASI PEGAWAI &amp; BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[20%]">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
                            <th class="p-2 text-center w-[10%]">HASIL KERJA</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                // Matching dinormalisasi (trim + mb_strtolower) agar tidak gagal
                                // saat sasaran_kinerja master diedit kapitalisasi/spasi-nya.
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
                                        <div class="mb-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md" x-data="{ item: {{ $cData }} }" data-realisasi-card>
                                            {{-- Teks khusus cetak: judul subbab + isi realisasi.
                                                 Nama/tautan file bukti TIDAK dicetak sesuai permintaan. --}}
                                            <div class="print-only font-bold text-xs mb-1" data-label-print>{{ $cLabel }}</div>
                                            <div class="print-only break-words" data-realisasi-print data-print-fallback="{{ $childRow->realisasi ?? '' }}">{{ $childRow->realisasi ?? '' }}</div>
                                            <div class="font-bold text-xs text-gray-800 mb-2 flex items-center gap-1.5 realisasi-label-screen">
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
                                            <div x-data="{ words: 0, resize() { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight + 4, 160) + 'px' }, limitWords() { if (typeof window.limitRealisasiWords === 'function' && window.limitRealisasiWords($el.value) !== $el.value) { $el.value = window.limitRealisasiWords($el.value); } this.words = (window.limitRealisasiWords ? window.limitRealisasiWords($el.value).split(/\s+/).filter(Boolean).length : 0); $nextTick(() => resize()) } }">
                                            <textarea x-model="item.text" x-init="words = $el.value.trim() ? $el.value.trim().split(/\s+/).length : 0; $nextTick(() => resize())" @input="limitWords()" class="w-full rounded-md shadow-sm text-xs p-2.5 max-h-40 overflow-y-auto resize-y {{ $isRealisasiSaved ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-blue-500 focus:ring-blue-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}" style="min-height: 120px;" rows="5" placeholder="Tuliskan realisasi perilaku kerja..." name="realisasi[perilaku][{{ $child->id }}]" {{ $isRealisasiSaved ? 'readonly disabled' : '' }}>{{ $childRow->realisasi ?? '' }}</textarea>
                                            @unless($isRealisasiSaved)
                                            <div class="text-right text-[10px] text-gray-400 mt-0.5" x-text="words.toLocaleString('id-ID') + '/30 kata'"></div>
                                            @endunless
                                            </div>
                                            <div class="mt-3 bg-blue-50 p-2.5 rounded border border-blue-100 pdf-upload-box" x-init="initPdfBukti($el)">
                                                @if(!$isRealisasiSaved)
                                                <label class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1">
                                                    <i class="fas fa-cloud-upload-alt"></i> Upload Bukti <span class="text-red-500" x-show="!item.file">*</span>
                                                </label>
                                                @endif
                                                <div x-show="item.file" class="mb-2">
                                                    <a :href="'/' + item.file" target="_blank" class="text-xs text-blue-600 hover:underline"><i class="fas fa-file mr-1"></i> <span x-text="item.filename"></span></a>
                                                </div>
                                                @if(!$isRealisasiSaved)
                                                <input type="file" data-pdf-input name="bukti_dukung[perilaku][{{ $child->id }}]" x-bind:required="!item.file" class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer border border-gray-300 bg-white rounded-md">
                                                <p data-pdf-status class="text-[10px] mt-1 italic hidden"></p>
                                                <p class="text-[10px] text-gray-500 mt-1 italic">* Wajib unggah minimal 1 bukti pendukung</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                <div class="bg-gray-100 border border-gray-200 border-dashed rounded-md p-4 text-center text-xs text-gray-400 italic no-print">
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
                                {{-- Nilai hasil kerja khusus cetak (tabel Perilaku Kerja). --}}
                                <span class="print-only font-bold" x-text="selectedHasilKerja || '-'"></span>
                            </td>
                        </tr>
                        @endforeach
<tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">RATING PERILAKU KERJA*</div>
                                <textarea :value="ratingPerilaku" readonly tabindex="-1" class="print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                <span class="print-only font-bold" x-text="ratingPerilaku || '-'"></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="font-bold bg-gray-50 p-4 text-left border-t border-gray-200">
                                <div class="mb-2">PREDIKAT KINERJA PEGAWAI*</div>
                                <textarea :value="predikatKinerja" readonly tabindex="-1" class="print-hidden w-full border-gray-200 bg-gray-100 rounded-md shadow-inner text-left font-bold text-gray-700 focus:ring-0 focus:border-gray-200 cursor-not-allowed select-none resize-none" rows="1"></textarea>
                                <span class="print-only font-bold" x-text="predikatKinerja || '-'"></span>
                            </td>
                        </tr>
</tbody>
                </table>
                </fieldset>
                @if(false)
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-save mr-2"></i> Simpan Realisasi Evaluasi
                    </button>
                @endif
                @if(false)
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-save mr-2"></i> Simpan Realisasi Evaluasi
                    </button>
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
            </div>
        
            <!-- DOKUMEN EVALUASI TAB -->
            <div x-show="activeTab === 'dokumen'" x-cloak class="area-cetak overflow-x-auto bg-white p-6 border border-gray-300 rounded-lg shadow-sm mb-6">
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
                                <textarea class="w-full border-0 focus:ring-0 resize-none p-4" rows="4" readonly disabled>{{ $skpKualitatif->skpHeader->catatan_penilai ?? '' }}</textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
</div>
    @endsection
</x-app-layout>