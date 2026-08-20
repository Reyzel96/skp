<x-app-layout>
    @section('page-title', 'Detail Membimbing Seminar')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-5xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Membimbing Seminar</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold hover:underline text-sm">
                &larr; Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.seminar.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            @php
                $jmlGenap = $dataGenap->jumlah_mahasiswa ?? 0;
                $jmlGasal = $dataGasal->jumlah_mahasiswa ?? 0;
                $akGenap = ($jmlGenap >= 1) ? 1 : 0;
                $akGasal = ($jmlGasal >= 1) ? 1 : 0;
                $waktuGenap = ($jmlGenap >= 1) ? 6 : 0;
                $waktuGasal = ($jmlGasal >= 1) ? 6 : 0;
            @endphp

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse border border-gray-300 rounded-lg overflow-hidden">
                        <thead class="bg-blue-600 text-white text-center font-black uppercase tracking-widest text-xs">
                            <tr>
                                <th class="border border-white/20 p-3 w-48">SEMESTER</th>
                                <th class="border border-white/20 p-3">Input Mahasiswa<br><span class="text-[9px] font-normal lowercase">(Hanya Angka)</span></th>
                                <th class="border border-white/20 p-3 w-32">NILAI AK</th>
                                <th class="border border-white/20 p-3 w-32">WAKTU (Bulan)</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 font-bold">
                            {{-- GENAP --}}
                            <tr class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="border-r border-gray-200 p-4 bg-gray-50 text-gray-800 uppercase tracking-widest text-center">Genap</td>
                                <td class="border-r border-gray-200 p-3 text-center">
                                    <input type="number" name="jumlah_mahasiswa[genap]" value="{{ $jmlGenap }}" min="0"
                                        class="w-full text-center border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-bold {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                        placeholder="0" {{ $isFormLocked ? 'readonly' : '' }}>
                                </td>
                                <td class="border-r border-gray-200 p-3 text-center bg-gray-50 text-emerald-700">{{ number_format($akGenap, 2, ',', '.') }}</td>
                                <td class="p-3 text-center bg-gray-50">{{ $waktuGenap }}</td>
                            </tr>

                            {{-- GASAL --}}
                            <tr class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="border-r border-gray-200 p-4 bg-gray-50 text-gray-800 uppercase tracking-widest text-center">Gasal</td>
                                <td class="border-r border-gray-200 p-3 text-center">
                                    <input type="number" name="jumlah_mahasiswa[gasal]" value="{{ $jmlGasal }}" min="0"
                                        class="w-full text-center border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-bold {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                        placeholder="0" {{ $isFormLocked ? 'readonly' : '' }}>
                                </td>
                                <td class="border-r border-gray-200 p-3 text-center bg-gray-50 text-emerald-700">{{ number_format($akGasal, 2, ',', '.') }}</td>
                                <td class="p-3 text-center bg-gray-50">{{ $waktuGasal }}</td>
                            </tr>

                            {{-- TOTAL --}}
                            <tr class="bg-gray-800 text-white font-black text-center uppercase tracking-widest text-sm">
                                <td class="p-4 text-right border-r border-gray-700">TOTAL CAPAIAN</td>
                                <td class="p-4 border-r border-gray-700">{{ $jmlGenap + $jmlGasal }} Mhs</td>
                                <td class="p-4 border-r border-gray-700 text-emerald-400">{{ number_format($akGenap + $akGasal, 2, ',', '.') }}</td>
                                <td class="p-4">{{ $waktuGenap + $waktuGasal }} bln</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- INFO --}}
                <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm text-xs text-yellow-700 font-medium">
                    <b>Catatan Rumus:</b> 1 Mahasiswa per semester = 1 AK & 6 Bulan Waktu (Jika Kosong maka 0).
                </div>
            </div>

            @if(!$isFormLocked)
            <div class="flex justify-between items-center mt-6 gap-4">
                <button type="button" onclick="if(confirm('Hapus/Reset Data Seminar?')) document.getElementById('reset-seminar-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition text-sm">
                    <i class="fas fa-trash-alt mr-2"></i> Reset Data
                </button>
                <button type="submit" class="bg-emerald-600 text-white px-10 py-3 rounded-xl font-black shadow-lg hover:bg-emerald-700 transition transform hover:-translate-y-1">
                    💾 SIMPAN & KEMBALI
                </button>
            </div>
            @else
            <div class="mt-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-lock text-amber-500 mr-3 text-lg"></i>
                    <p class="text-amber-800 font-bold text-sm">Formulir Terkunci. Anda tidak dapat mengubah atau mereset data karena SKP sedang dalam proses review atau telah disetujui.</p>
                </div>
            </div>
            @endif
        </form>
        </form>

<form id="reset-seminar-form" action="{{ route('pegawai.skp-dosen.seminar.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
