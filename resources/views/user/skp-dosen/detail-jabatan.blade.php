<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Jabatan Pimpinan')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-4xl mx-auto p-6">

        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Menduduki Jabatan</h2>
                <p class="text-sm text-gray-500">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold hover:text-red-500 transition text-sm hover:underline">
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

        {{-- FORM INPUT --}}
        <form action="{{ route('pegawai.skp-dosen.jabatan.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            @php
                $savedGenap = $dataDB->kode_genap ?? null;
                $savedGasal = $dataDB->kode_gasal ?? null;
            @endphp

            <div class="grid grid-cols-1 gap-6">
                @foreach(['Genap', 'Gasal'] as $semester)
                    @php
                        $isGenap = ($semester == 'Genap');
                        $currentValue = $isGenap ? $savedGenap : $savedGasal;
                        $inputName = $isGenap ? 'kode_genap' : 'kode_gasal';
                    @endphp

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-4 bg-blue-600 text-white font-bold text-center uppercase tracking-widest text-xs">
                            Semester {{ $semester }}
                        </div>
                        <div class="p-6">
                            <label class="block text-xs font-black text-gray-400 uppercase mb-3 text-center">Pilih Jabatan yang Diemban</label>

                            <select name="{{ $inputName }}" class="w-full border-gray-200 rounded-xl p-3 focus:ring-blue-500 focus:border-blue-500 font-bold text-gray-700 shadow-sm text-sm" {{ $isFormLocked ? 'disabled' : '' }}>
                                <option value="">-- Tidak Ada Jabatan / Tidak Menjabat --</option>
                                @foreach($masterJabatan as $kode => $mJabatan)
                                    <option value="{{ $kode }}" {{ $currentValue == $kode ? 'selected' : '' }}>
                                        {{ $kode }}. {{ $mJabatan['nama'] }} (AK: {{ $mJabatan['ak'] }})
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-4 text-[10px] text-center text-gray-400 italic">
                                * Pilih jabatan sesuai dengan SK Rektor/Dekan pada semester ini.
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!$isFormLocked)
                <div class="mt-8 flex justify-between items-center gap-4">
                    <button type="button" onclick="if(confirm('Hapus/Reset Data Jabatan?')) document.getElementById('reset-jabatan-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition text-sm">
                        <i class="fas fa-trash-alt mr-2"></i> Reset Data
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-black shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1">
                        💾 SIMPAN PERUBAHAN JABATAN
                    </button>
                </div>
            @endif
        </form>
<form id="reset-jabatan-form" action="{{ route('pegawai.skp-dosen.jabatan.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>

    </div>
    @endsection
</x-app-layout>
