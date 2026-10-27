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
    <div class="p-6" x-data="{ activeTab: 'skp' }">
            <div class="flex justify-between items-center mb-6 no-print">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Target SKP Dosen Kualitatif</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('penilai.approval-skp-dosen-kualitatif.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <a :href="'{{ route('pegawai.skp-dosen-kualitatif.print', $header->id) }}?tab=' + activeTab" target="_blank" class="no-print inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all" href="{{ route('pegawai.skp-dosen-kualitatif.print', $header->id) }}?tab=skp">
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
                    /* HANYA tab aktif yang dicetak: panel non-aktif tetap mengikuti
                       kondisi Alpine (inline display:none dari x-show + x-cloak).
                       Tidak ada rule yang memaksa panel tersembunyi tampil saat print.
                       (Selector .print-area lama tidak pernah match — main layout
                       hanya punya id="print-area", bukan class — jadi rule forcing
                       lama sebenarnya mati; dihapus agar tidak menyesatkan.) */
                    #print-area [x-cloak] { display: none !important; }

                    /* Kontrol form tidak ikut tercetak (form approval ada di blok
                       no-print, ini pengaman tambahan). */
                    #print-area form,
                    #print-area input,
                    #print-area select,
                    #print-area button { display: none !important; }

                    /* Bingkai kartu layar pada wrapper tab dipoloskan & diambil
                       padding/margin-nya agar hasil cetak rapi seperti dokumen
                       resmi dan tidak menyisakan halaman ekstra. */
                    #print-area div[x-show] {
                        border: none !important;
                        box-shadow: none !important;
                        padding: 0 !important;
                        margin-bottom: 0 !important;
                    }

                    /* Judul grup (HASIL KERJA, A. UTAMA, B. TAMBAHAN, PERILAKU
                       KERJA*) jangan tercetak sendirian di dasar halaman —
                       selalu bersama baris data pertama setelahnya. */
                    #print-area table.table-kualitatif tr:has(+ tr) td[colspan]:only-child {
                        page-break-after: avoid;
                        break-after: avoid;
                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

                    /* Baris placeholder internal tidak dicetak. */
                    #print-area tr.empty-placeholder-print,
                    #print-area td.empty-placeholder-print { display: none !important; }

                    /* Teks panjang membungkus di dalam sel. */
                    #print-area table.table-kualitatif td { overflow-wrap: break-word; }

                    /* Jarak antar tabel dirapatkan; tabel terakhir tanpa margin
                       agar tidak memicu halaman ekstra. */
                    #print-area table.table-kualitatif { margin-bottom: 10px !important; }
                    #print-area table.table-kualitatif:last-of-type { margin-bottom: 0 !important; }

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
                        @php
                            $user = $header->user;
                            $penilai = $header->pejabatPenilai;
                        @endphp
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
                        @foreach($utamas as $index => $utama)
                        <tr>
                            <td class="text-center font-bold w-12 align-top">{{ $index + 1 }}</td>
                            <td colspan="3" class="align-top italic text-gray-800">
                                {{ $utama->rencana_hasil_kerja }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center w-12 border-t-0"></td>
                            <td colspan="3">
                                <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                                <div class="whitespace-pre-wrap">{{ $utama->indikator_target }}</div>
                            </td>
                        </tr>
                        @endforeach

                        <!-- Baris Tambahan Default A. Utama (Removed) -->
                        
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
                        {{-- Dari snapshot rows skp_kualitatif_perilakus (terkunci saat SKP dibuat),
                             BUKAN master Acuan live — konsisten dgn dosen & penilai evaluasi.
                             Parent Acuan baru setelah SKP dibuat tidak lagi tampil sbg ghost row "-". --}}
                        @foreach($perilakus as $idx => $perilaku)
                        <tr>
                            <td class="text-center font-bold w-12 align-top">{{ $idx + 1 }}</td>
                            <td colspan="2" class="align-top">
                                <div class="font-bold mb-1 {{ strtolower($perilaku->aspek) == 'kepemimpinan' ? 'italic' : '' }}">{{ $perilaku->aspek }}</div>
                                <div class="whitespace-pre-wrap text-gray-700 pl-4">{!! nl2br(e($perilaku->indikator)) !!}</div>
                            </td>
                            <td class="align-top">
                                <div class="font-bold mb-1">Ekspektasi Khusus Pimpinan:</div>
                                <div class="whitespace-pre-wrap">{{ $perilaku->ekspektasi_pimpinan ?? "-" }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
        </div>

            <!-- Tombol Aksi Approval -->
            @if(in_array($header->status, ['Draft', 'Diajukan', 'Target Diajukan', 'Revisi']))
            <div class="mt-8 bg-white p-6 border border-gray-200 rounded-xl shadow-sm no-print">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Keputusan Approval</h3>
                <div class="flex gap-4">
                    <form action="{{ route('penilai.approval-skp-dosen-kualitatif.approve', $header->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui target SKP ini?')">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition">
                            <i class="fas fa-check mr-2"></i> Setujui Target SKP
                        </button>
                    </form>
                </div>
            </div>


            @endif

    @endsection
</x-app-layout>
