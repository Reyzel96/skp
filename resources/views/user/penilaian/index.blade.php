<x-app-layout>
    @section('page-title', 'Nilai Prestasi Kerja')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
        <div class="max-w-6xl mx-auto p-4"
            {{-- Inisialisasi Alpine.js --}}
            x-data="{
                isLengkap: {{ $isLengkap ? 'true' : 'false' }},
                skp: {{ number_format($nilaiSkp, 2, '.', '') }},
                orientasi: {{ $perilaku->orientasi_pelayanan ?? 0 }},
                integritas: {{ $perilaku->integritas ?? 0 }},
                komitmen: {{ $perilaku->komitmen ?? 0 }},
                disiplin: {{ $perilaku->disiplin ?? 0 }},
                kerjasama: {{ $perilaku->kerjasama ?? 0 }},
                kepemimpinan: '{{ $perilaku->kepemimpinan ?? '' }}',

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
                    // JIKA SKP BELUM LENGKAP, JANGAN TAMPILKAN PREDIKAT BURUK
                    if (!this.isLengkap) return '(SKP Belum Lengkap)';

                    let v = parseFloat(val);
                    if (isNaN(v) || v == 0) return '';
                    if (v <= 50) return '(Buruk)';
                    if (v <= 60) return '(Kurang)';
                    if (v <= 75) return '(Cukup)';
                    if (v <= 90.99) return '(Baik)';
                    return '(Sangat Baik)';
                },

                get countFilled() {
                    let count = 0;
                    if(this.orientasi > 0) count++;
                    if(this.integritas > 0) count++;
                    if(this.komitmen > 0) count++;
                    if(this.disiplin > 0) count++;
                    if(this.kerjasama > 0) count++;
                    if(parseFloat(this.kepemimpinan) > 0) count++;
                    return count === 0 ? 1 : count; // cegah divide by zero
                },

                get sumPerilaku() {
                    return parseFloat(this.orientasi) + parseFloat(this.integritas) +
                           parseFloat(this.komitmen) + parseFloat(this.disiplin) +
                           parseFloat(this.kerjasama) + (parseFloat(this.kepemimpinan) || 0);
                },

                get avgPerilaku() {
                    return this.sumPerilaku > 0 ? (this.sumPerilaku / this.countFilled).toFixed(2) : 0;
                },

                get calcSkp60() {
                    return (this.skp * 0.6).toFixed(2);
                },

                get calcPerilaku40() {
                    return (this.avgPerilaku * 0.4).toFixed(2);
                },

                get finalScore() {
                    // JIKA SKP BELUM LENGKAP, KUNCI TOTAL SKOR DI ANGKA 0
                    if (!this.isLengkap) return '0.00';
                    return (parseFloat(this.calcSkp60) + parseFloat(this.calcPerilaku40)).toFixed(2);
                }
            }"
        >

            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- HEADER & FILTER --}}
            <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Nilai Prestasi Kerja Pegawai</h1>
                    <p class="text-sm text-gray-500">Kalkulasi 60% SKP dan 40% Perilaku Kerja</p>
                </div>
                <form method="GET" action="{{ route('pegawai.penilaian.index') }}">
                    <select name="periode_id" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        @foreach($periode as $p)
                            <option value="{{ $p->id }}" {{ $periodeId == $p->id ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($periodeId)

                {{-- PERINGATAN JIKA SKP BELUM LENGKAP --}}
                @if(!$isLengkap)
                    <div class="mb-4 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-orange-500 mt-1"></i>
                        <div>
                            <h3 class="text-orange-800 font-bold">Target SKP Belum Sepenuhnya Terealisasi!</h3>
                            <p class="text-sm text-orange-700">Anda dapat mengisi form Perilaku Kerja, namun <b>Nilai Akhir (Nilai Prestasi Kerja) tidak akan dihitung/dimunculkan</b> sebelum Anda melengkapi seluruh realisasi kegiatan di menu SKP.</p>
                        </div>
                    </div>
                @endif

            <div class="bg-white overflow-hidden shadow border border-gray-400 p-2">
                <form action="{{ route('pegawai.penilaian.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="periode_skp_id" value="{{ $periodeId }}">

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm border-2 border-black" style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th class="border border-black p-2 w-10 text-center text-lg bg-white" rowspan="2">4.</th>
                                    <th class="border border-black p-2 text-left text-lg bg-white" colspan="4">UNSUR YANG DINILAI</th>
                                    <th class="border border-black p-2 w-32 text-center text-lg bg-white">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- A. SKP (60%) --}}
                                <tr>
                                    <td class="border border-black p-2 bg-white font-bold" colspan="2">a. Sasaran Kerja Pegawai (SKP)</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white text-lg w-24">
                                        @if($isLengkap)
                                            {{ number_format($nilaiSkp, 2) }}
                                        @else
                                            <span class="text-xs text-red-600">0.00</span>
                                        @endif
                                    </td>
                                    <td class="border border-black p-2 text-center font-bold bg-white w-16">x</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white w-16">60%</td>
                                    <td class="border border-black p-2 text-center font-bold bg-gray-100 text-lg">
                                        @if($isLengkap)
                                            <span x-text="calcSkp60"></span>
                                        @else
                                            <span class="text-xs text-red-600">Menunggu SKP</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- B. PERILAKU KERJA (40%) --}}
                                @php
                                    $aspekMap = [
                                        'Orientasi Pelayanan' => 'orientasi_pelayanan',
                                        'Integritas' => 'integritas',
                                        'Komitmen' => 'komitmen',
                                        'Disiplin' => 'disiplin',
                                        'Kerjasama' => 'kerjasama',
                                        'Kepemimpinan' => 'kepemimpinan'
                                    ];
                                @endphp

                                @foreach ($aspekMap as $aspekLabel => $fieldName)
                                    <tr>
                                        @if ($loop->first)
                                            <td class="border border-black p-2 bg-white font-bold align-top w-40" rowspan="{{ count($aspekMap) + 3 }}">b. Perilaku Kerja</td>
                                        @endif
                                        <td class="border border-black p-2 bg-white">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="font-bold">{{ $loop->iteration }}. {{ $aspekLabel }}</span>
                                                <button type="button" @click="$dispatch('open-modal-perilaku', { aspek: '{{ $aspekLabel }}' })" 
                                                    class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded hover:bg-blue-200">
                                                    <i class="fas fa-plus mr-1"></i> Tambah Poin
                                                </button>
                                            </div>
                                            
                                            {{-- List Indikator --}}
                                            <div class="space-y-1 ml-4 mt-2">
                                                @if (isset($indicators[$aspekLabel]))
                                                    @foreach ($indicators[$aspekLabel] as $ind)
                                                        <div class="flex items-start gap-2 text-[11px] text-gray-600 bg-gray-50 p-1.5 rounded border border-gray-100">
                                                            <input type="checkbox" name="realisasi_indicators[{{ $ind->id }}]" value="1" 
                                                                {{ $ind->is_realized ? 'checked' : '' }} class="mt-0.5 rounded text-blue-600 w-3 h-3">
                                                            <div class="flex-1 leading-tight">
                                                                {{ $ind->indikator }}
                                                                <span class="text-[9px] font-bold text-blue-500 ml-1">({{ number_format($ind->bobot_persen, 1) }}%)</span>
                                                                @if (!$ind->is_from_admin)
                                                                    <span class="text-[8px] bg-emerald-100 text-emerald-700 px-1 rounded ml-1">Mandiri</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="text-[10px] text-gray-400 italic">Belum ada target indikator dari atasan.</p>
                                                @endif

                                                {{-- New Indicators Added via Alpine (not yet saved) --}}
                                                <div x-data="{ newInds: [] }" @add-new-ind.window="if($event.detail.aspek === '{{ $aspekLabel }}') newInds.push($event.detail.text)">
                                                    <template x-for="(text, i) in newInds" :key="i">
                                                        <div class="flex items-start gap-2 text-[11px] text-emerald-700 bg-emerald-50 p-1.5 rounded border border-emerald-100 mt-1 animate-pulse">
                                                            <i class="fas fa-check-circle mt-0.5"></i>
                                                            <div class="flex-1 leading-tight">
                                                                <span x-text="text"></span>
                                                                <input type="hidden" :name="'new_indicators[{{ $aspekLabel }}][]'" :value="text">
                                                                <span class="text-[8px] font-bold ml-1">(Baru - Akan dihitung ulang)</span>
                                                            </div>
                                                            <button type="button" @click="newInds.splice(i, 1)" class="text-red-400"><i class="fas fa-times"></i></button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="border border-black p-1 bg-white text-center">
                                            <input type="number" step="0.01" name="{{ $fieldName }}" x-model.number="{{ $fieldName }}" class="w-full text-center border-none p-1 focus:ring-0 focus:bg-yellow-50">
                                        </td>
                                        <td class="border border-black p-2 text-center bg-gray-200 text-gray-700" colspan="2" x-text="getPredicateNormal({{ $fieldName }})"></td>
                                        @if ($loop->first)
                                            <td class="border border-black bg-gray-200" rowspan="6"></td>
                                        @endif
                                    </tr>
                                @endforeach

                                {{-- KALKULASI PERILAKU --}}
                                <tr>
                                    <td class="border border-black p-2 bg-white font-bold">7. Jumlah</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white text-lg" x-text="sumPerilaku"></td>
                                    <td class="border border-black p-2 text-center bg-gray-200" colspan="2"></td>
                                    <td class="border border-black p-2 text-center bg-gray-200"></td>
                                </tr>
                                <tr>
                                    <td class="border border-black p-2 bg-white font-bold">8. Nilai rata – rata</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white text-lg" x-text="avgPerilaku"></td>
                                    <td class="border border-black p-2 text-center font-bold bg-gray-200 text-gray-800" colspan="2" x-text="getPredicateNormal(avgPerilaku)"></td>
                                    <td class="border border-black p-2 text-center bg-gray-200"></td>
                                </tr>
                                <tr>
                                    <td class="border border-black p-2 bg-white font-bold text-lg">9. Nilai Perilaku Kerja</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white text-lg" x-text="avgPerilaku"></td>
                                    <td class="border border-black p-2 text-center font-bold bg-white w-16">x</td>
                                    <td class="border border-black p-2 text-center font-bold bg-white w-16">40%</td>
                                    <td class="border border-black p-2 text-center font-bold bg-gray-100 text-lg" x-text="calcPerilaku40"></td>
                                </tr>

                                {{-- HASIL AKHIR PRESTASI KERJA --}}
                                <tr>
                                    <td class="border-t-4 border-black p-4 text-center font-bold text-xl uppercase bg-white tracking-widest" colspan="5">
                                        NILAI PRESTASI KERJA
                                    </td>
                                    <td class="border-t-4 border-l border-black p-2 text-center bg-white align-middle">
                                        <div class="font-bold text-2xl" :class="{'text-red-600': !isLengkap, 'text-blue-800': isLengkap}" x-text="finalScore"></div>
                                        <div class="font-bold text-sm mt-1" :class="{'text-red-500': !isLengkap, 'text-gray-800': isLengkap}" x-text="getPredicateFinal(finalScore)"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded shadow-lg transform transition hover:scale-105">
                            <i class="fas fa-save mr-2"></i> Simpan Penilaian Perilaku
                        </button>
                    </div>
                </form>
            </div>
            @else
                <div class="bg-blue-50 p-8 rounded-xl border border-blue-200 text-center">
                    <p class="font-semibold text-blue-800">Silakan pilih periode untuk melihat dan mengisi Penilaian Prestasi Kerja.</p>
                </div>
            @endif
        </div>

        {{-- MODAL TAMBAH POIN PERILAKU --}}
        <div x-data="{ open: false, aspek: '', text: '' }" 
            @open-modal-perilaku.window="open = true; aspek = $event.detail.aspek; text = ''"
            x-show="open" 
            class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/75 backdrop-blur-sm" @click="open = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                <i class="fas fa-plus-circle text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight">Tambah Poin Perilaku</h3>
                                <p class="text-xs text-gray-500 font-bold" x-text="'Kategori: ' + aspek"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Indikator / Deskripsi Kegiatan</label>
                            <textarea x-model="text" rows="3" 
                                class="w-full border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm font-medium" 
                                placeholder="Contoh: Mengikuti pelatihan pengembangan diri secara mandiri..."></textarea>
                            <p class="mt-2 text-[10px] text-gray-400 italic font-medium leading-relaxed">
                                * Poin yang Anda tambahkan akan dihitung sebagai bagian dari 100% bobot kategori ini. Bobot per poin akan menyesuaikan secara otomatis.
                            </p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-2">
                        <button type="button" @click="$dispatch('add-new-ind', { aspek: aspek, text: text }); open = false;"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-lg px-4 py-2.5 bg-blue-600 text-sm font-black text-white hover:bg-blue-700 transition uppercase tracking-widest">
                            Simpan Poin
                        </button>
                        <button type="button" @click="open = false"
                            class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-100 transition uppercase tracking-widest">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
