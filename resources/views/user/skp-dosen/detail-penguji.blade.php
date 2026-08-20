<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Penguji Ujian Akhir')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Input Data Penguji</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold hover:underline text-sm">
                &larr; Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.penguji.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach(['Genap', 'Gasal'] as $semester)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 bg-blue-600 text-white font-bold text-center tracking-widest uppercase text-sm">
                        SEMESTER {{ $semester }}
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                            <label class="block text-xs font-black text-gray-500 uppercase mb-2">Sebagai Ketua Penguji (Mhs)</label>
                            <input type="number" name="data[{{ $semester }}][ketua]"
                                   value="{{ $data[$semester]->jml_mhs_ketua ?? 0 }}" min="0"
                                   class="w-full border-gray-300 rounded-lg p-2 text-center font-bold text-lg focus:ring-blue-500 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl">
                            <label class="block text-xs font-black text-gray-500 uppercase mb-2">Sebagai Anggota Penguji (Mhs)</label>
                            <input type="number" name="data[{{ $semester }}][anggota]"
                                   value="{{ $data[$semester]->jml_mhs_anggota ?? 0 }}" min="0"
                                   class="w-full border-gray-300 rounded-lg p-2 text-center font-bold text-lg focus:ring-blue-500 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if(!$isFormLocked)
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                <button type="button" onclick="if(confirm('Hapus/Reset Data Penguji?')) document.getElementById('reset-penguji-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition text-sm">
                    <i class="fas fa-trash-alt mr-2"></i> Reset Data
                </button>
                <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-black shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1 tracking-wider">
                    💾 SIMPAN DATA PENGUJI
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

<form id="reset-penguji-form" action="{{ route('pegawai.skp-dosen.penguji.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
