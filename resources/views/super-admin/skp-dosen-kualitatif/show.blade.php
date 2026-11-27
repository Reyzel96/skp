<x-app-layout>
    @section('page-title', 'Detail SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6" x-data="{
            activeTab: 'skp',
            syncModal: false,
            syncLoading: false,
            syncPreview: null,
            syncStale: false,
            syncError: null,
            openSyncModal() {
                this.syncModal = true;
                this.syncLoading = true;
                this.syncPreview = null;
                this.syncStale = false;
                this.syncError = null;
                fetch('{{ route('superadmin.skp-dosen-kualitatif.lampiran-sync.preview', $skpKualitatif->skp_header_id) }}')
                    .then(res => res.json())
                    .then(data => { this.syncPreview = data.preview; this.syncLoading = false; })
                    .catch(() => { this.syncError = 'Gagal memuat preview.'; this.syncLoading = false; });
                setTimeout(() => { this.syncStale = true; }, 5 * 60 * 1000);
            },
            applySync() {
                if (!confirm('Terapkan perubahan kotak upload sesuai kondisi TERKINI? Yang di-apply dihitung ulang dari data saat ini, bukan dari preview yang mungkin sudah basi.')) return;
                this.syncLoading = true;
                this.syncError = null;
                fetch('{{ route('superadmin.skp-dosen-kualitatif.lampiran-sync.apply', $skpKualitatif->skp_header_id) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                })
                .then(res => res.json().then(d => ({ ok: res.ok, status: res.status, data: d })))
                .then(({ ok, status, data }) => {
                    if (ok && data.success) {
                        this.syncPreview = data.applied;
                        this.syncModal = false;
                        window.location.reload();
                    } else {
                        this.syncError = data.message || 'Gagal sinkron.';
                        this.syncLoading = false;
                    }
                })
                .catch(err => { this.syncError = 'Gagal sinkron (jaringan/server).'; this.syncLoading = false; });
            }
        }">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Target SKP Dosen Kualitatif</h1>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="openSyncModal()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-sync-alt mr-2"></i> Sinkron Ulang Persyaratan Lampiran
                    </button>
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

            {{-- MODAL PREVIEW SINKRON --}}
            <div x-show="syncModal" x-cloak style="z-index: 99999;" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm">
                <div class="flex min-h-screen w-full items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-3xl w-full relative max-h-[90vh] overflow-y-auto">
                        <h2 class="text-xl font-black text-gray-800 mb-1">Preview Sinkron Persyaratan Lampiran</h2>
                        <p class="text-sm text-gray-500 mb-4">Yang di-apply dihitung ulang dari data TERKINI saat tombol Konfirmasi diklik — bukan dari daftar ini (data mungkin sudah berubah).</p>

                        <template x-if="syncLoading">
                            <div class="py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat preview...</div>
                        </template>

                        <template x-if="syncError">
                            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-r mb-4 text-sm" x-text="syncError"></div>
                        </template>

                        <template x-if="syncPreview && !syncLoading">
                            <div class="space-y-4">
                                <div>
                                    <h3 class="font-bold text-green-700 mb-2"><i class="fas fa-plus-circle mr-1"></i> Akan DITAMBAHKAN (<span x-text="syncPreview.add.length"></span>)</h3>
                                    <template x-if="syncPreview.add.length === 0"><p class="text-sm text-gray-400 italic pl-5">Tidak ada.</p></template>
                                    <template x-for="item in syncPreview.add" :key="'a'+item.child_id">
                                        <div class="text-sm pl-5 py-1 border-b border-gray-100">
                                            <span class="font-bold text-gray-700" x-text="'#' + item.child_id + ' '"></span>
                                            <span class="text-gray-600" x-text="item.label || '(tanpa label — auto-detect)'"></span>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="font-bold text-red-700 mb-2"><i class="fas fa-minus-circle mr-1"></i> Akan DIHAPUS (<span x-text="syncPreview.remove.length"></span>)</h3>
                                    <template x-if="syncPreview.remove.length === 0"><p class="text-sm text-gray-400 italic pl-5">Tidak ada.</p></template>
                                    <template x-for="item in syncPreview.remove" :key="'r'+item.child_id">
                                        <div class="text-sm pl-5 py-1 border-b border-gray-100">
                                            <span class="font-bold text-gray-700" x-text="'#' + item.child_id + ' '"></span>
                                            <span class="text-gray-600" x-text="item.label || '(tanpa label)'"></span>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-600 mb-2"><i class="fas fa-lock mr-1"></i> Dikecualikan karena sudah ada realisasi/bukti dosen (<span x-text="syncPreview.skip_has_realisasi.length"></span>)</h3>
                                    <template x-if="syncPreview.skip_has_realisasi.length === 0"><p class="text-sm text-gray-400 italic pl-5">Tidak ada.</p></template>
                                    <template x-for="item in syncPreview.skip_has_realisasi" :key="'s'+item.child_id">
                                        <div class="text-sm pl-5 py-1 border-b border-gray-100">
                                            <span class="font-bold text-gray-700" x-text="'#' + item.child_id + ' '"></span>
                                            <span class="text-gray-500">tidak dihapus — sudah berisi realisasi</span>
                                        </div>
                                    </template>
                                </div>
                                <div class="pt-4 border-t flex justify-end gap-3">
                                    <button type="button" @click="syncModal = false"
                                        class="px-5 py-2.5 rounded-lg border-2 border-gray-200 text-gray-600 font-bold uppercase text-sm hover:bg-gray-100">Batal</button>
                                    <button type="button" @click="applySync()" :disabled="syncLoading || syncStale"
                                        :class="syncStale ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                                        class="px-5 py-2.5 rounded-lg font-bold uppercase text-sm shadow-md transition-all">
                                        <i class="fas fa-check mr-1"></i>
                                        <span x-text="syncStale ? 'Preview basi — refresh' : 'Konfirmasi Sinkron'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
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
                        {{-- Dari snapshot rows skp_kualitatif_perilakus (terkunci saat SKP dibuat),
                             BUKAN master Acuan live — konsisten dgn dosen & penilai evaluasi. --}}
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
    @endsection
</x-app-layout>
