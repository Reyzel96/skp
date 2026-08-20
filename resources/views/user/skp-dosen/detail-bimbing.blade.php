<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Bimbingan Tugas Akhir')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-5xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Membimbing Skripsi/Tesis/Disertasi</h2>
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

        <form action="{{ route('pegawai.skp-dosen.bimbing.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            @foreach(['Skripsi', 'Tesis', 'Disertasi'] as $judul)
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-8">
                <h3 class="font-black text-lg text-blue-800 mb-4 border-l-4 border-blue-500 pl-3 uppercase">{{ $judul }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach(['Genap', 'Gasal'] as $semester)
                        @php $item = $data[$judul][$semester] ?? null; @endphp
                        <div class="p-4 rounded-xl border bg-gray-50 border-gray-200">
                            <p class="font-black mb-3 text-center uppercase text-gray-600 tracking-widest">Semester {{ $semester }}</p>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pembimbing Utama (Mhs)</label>
                                    <input type="number" name="bimbing[{{ $judul }}][{{ $semester }}][utama]"
                                           value="{{ $item->jml_mhs_utama ?? 0 }}" min="0"
                                           class="w-full border-gray-300 rounded-lg text-center font-bold focus:ring-blue-500 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pembimbing Penyerta (Mhs)</label>
                                    <input type="number" name="bimbing[{{ $judul }}][{{ $semester }}][penyerta]"
                                           value="{{ $item->jml_mhs_penyerta ?? 0 }}" min="0"
                                           class="w-full border-gray-300 rounded-lg text-center font-bold focus:ring-blue-500 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            @if(!$isFormLocked)
            <div class="flex justify-between items-center mt-6">
                <button type="button" onclick="if(confirm('Hapus/Reset Data Bimbingan?')) document.getElementById('reset-bimbing-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition">
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
        </form>

<form id="reset-bimbing-form" action="{{ route('pegawai.skp-dosen.bimbing.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
