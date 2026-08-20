<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Bahan Kuliah')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Mengembangkan Bahan Kuliah</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold text-sm hover:underline">
                &larr; Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.bahan.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            @foreach(['Genap', 'Gasal'] as $semester)
                <div class="bg-white rounded-xl border border-gray-200 mb-6 overflow-hidden shadow-sm">
                    <div class="p-4 bg-blue-600 text-white font-bold uppercase tracking-widest text-sm">
                        Semester {{ $semester }}
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach($masterBahan as $jenis => $akSatuan)
                                @php
                                    $dbItem = $dataDB->where('semester', $semester)->where('jenis_bahan', $jenis)->first();
                                    $jumlah = $dbItem->jumlah ?? 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4 text-gray-700 font-medium">{{ $jenis }}</td>
                                    <td class="p-4 w-32">
                                        <input type="number" name="data[{{ $semester }}][{{ $jenis }}]"
                                            value="{{ $jumlah }}" min="0"
                                            class="w-full text-center border-gray-300 rounded-lg font-bold focus:ring-blue-500 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            @if(!$isFormLocked)
            <div class="mt-6 flex justify-between items-center">
                <button type="button" onclick="if(confirm('Hapus/Reset Data Bahan Kuliah?')) document.getElementById('reset-bahan-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition">
                    <i class="fas fa-trash-alt mr-2"></i> Reset Data
                </button>
                <button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition">
                    💾 Simpan & Kembali
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

        {{-- Form Reset/Hapus --}}
<form id="reset-bahan-form" action="{{ route('pegawai.skp-dosen.bahan.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
