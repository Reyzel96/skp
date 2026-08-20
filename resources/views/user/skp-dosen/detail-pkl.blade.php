<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - PKL/KKN')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Bimbingan PKL / KKN / PKN</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold text-sm hover:underline">
                &larr; Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.pkl.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="bg-blue-600 px-4 py-3">
                    <h3 class="font-bold text-white uppercase text-sm tracking-widest text-center">Form Mahasiswa Bimbingan PKL</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-[10px] text-gray-500 font-black uppercase">
                            <th class="p-4 text-left pl-6">Kategori Bimbingan</th>
                            <th class="p-4 text-center">Sem. Genap (Mhs)</th>
                            <th class="p-4 text-center">Sem. Gasal (Mhs)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach(['KKN' => 'a. KKN', 'PKN' => 'b. PKN', 'PKL' => 'c. PKL'] as $key => $label)
                        <tr class="hover:bg-gray-50">
                            <td class="p-4 font-bold text-gray-700 pl-6">{{ $label }}</td>
                            <td class="p-4">
                                <input type="number" name="data[{{ $key }}][genap]"
                                       value="{{ $data[$key]->jml_mhs_genap ?? 0 }}" min="0"
                                       class="w-full border-gray-300 rounded-xl text-center font-bold focus:ring-blue-500 py-2 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                            </td>
                            <td class="p-4">
                                <input type="number" name="data[{{ $key }}][gasal]"
                                       value="{{ $data[$key]->jml_mhs_gasal ?? 0 }}" min="0"
                                       class="w-full border-gray-300 rounded-xl text-center font-bold focus:ring-blue-500 py-2 {{ $isFormLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isFormLocked ? 'readonly' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!$isFormLocked)
            <div class="flex justify-between items-center mt-6 gap-4">
                <button type="button" onclick="if(confirm('Hapus/Reset Data Bimbingan PKL?')) document.getElementById('reset-pkl-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition text-sm">
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

<form id="reset-pkl-form" action="{{ route('pegawai.skp-dosen.pkl.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
