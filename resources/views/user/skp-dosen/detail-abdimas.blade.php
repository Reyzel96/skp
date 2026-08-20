<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Pengabdian Masyarakat')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-5xl mx-auto p-6">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Pelaksanaan Pengabdian</h2>
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

        @if($isFormLocked)
            <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm flex items-center gap-3">
                <i class="fas fa-lock text-amber-500"></i>
                <p class="text-amber-800 text-sm font-bold uppercase">Form Terkunci: SKP sedang dalam proses review atau sudah selesai.</p>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.abdimas.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            @foreach($masterData as $kategori => $group)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
                    <div class="bg-blue-600 px-4 py-3">
                        <h3 class="font-bold text-white uppercase text-sm">
                            {{ $kategori }}. {{ $group['title'] }}
                        </h3>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 border-b">
                            <tr>
                                <th class="p-3 text-left">Deskripsi Kegiatan</th>
                                <th class="p-3 w-32 text-center">Jumlah Kegiatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($group['items'] as $namaKegiatan => $akSatuan)
                                @php
                                    $dbItem = $dataDB->where('kategori', $kategori)->where('kegiatan', $namaKegiatan)->first();
                                    $jumlah = $dbItem->jumlah ?? 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 text-gray-700 font-medium">{{ $namaKegiatan }}</td>
                                    <td class="p-3">
                                        <input type="number" name="data[{{ $kategori }}][{{ $namaKegiatan }}]"
                                            value="{{ $jumlah }}" min="0"
                                            class="w-full text-center border-gray-300 rounded-lg focus:ring-blue-500 font-bold"
                                            {{ $isFormLocked ? 'readonly' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            @if(!$isFormLocked)
                <div class="flex justify-between items-center gap-4 mt-8">
                    <button type="button" onclick="if(confirm('Hapus/Reset Data Pengabdian Masyarakat?')) document.getElementById('reset-abdimas-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition">
                        <i class="fas fa-trash-alt mr-2"></i> Reset Data
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg transition">
                        💾 Simpan & Kembali
                    </button>
                </div>
            @endif
        </form>

        {{-- Form Reset/Hapus --}}
<form id="reset-abdimas-form" action="{{ route('pegawai.skp-dosen.abdimas.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>
    </div>
    @endsection
</x-app-layout>
