<x-app-layout>
    @section('page-title', 'Detail SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6" x-data="{ activeTab: 'skp' }">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Target SKP Dosen Kualitatif</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.edit', $skpKualitatif->id) }}"
                        class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>


            <div class="flex space-x-1 mb-4 border-b border-gray-200">
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
                            $user = $skpKualitatif->skpHeader->user;
                            $penilai = $skpKualitatif->skpHeader->pejabatPenilai;
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
                                <div class="whitespace-pre-wrap">{{ $perilakus->where("aspek", $parent->sasaran_kinerja)->first()->ekspektasi_pimpinan ?? "-" }}</div>
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
    @endsection
</x-app-layout>
