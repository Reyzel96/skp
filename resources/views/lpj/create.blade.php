<x-app-layout>
    @section('page-title', 'Buat LPJ Baru')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

@section('content')
<div class="flex justify-between items-end mb-6 border-b border-gray-200 pb-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Buat Bukti Pengeluaran (LPJ)</h2>
        <p class="text-gray-500 mt-1">Pengajuan: <span class="font-bold text-blue-700">{{ $pengajuan->nama_kegiatan_prodi ?? $pengajuan->programKegiatan->nama_kegiatan }}</span></p>
    </div>
    <a href="{{ route('lpj.my-lpj') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center gap-1 bg-blue-50 px-4 py-2 rounded-lg transition hover:bg-blue-100">
        <i class="fas fa-arrow-left"></i> Batal & Kembali
    </a>
</div>

{{-- Pesan Error Validasi --}}
@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-blue-50 border border-blue-200 p-4 rounded-xl mb-6 grid grid-cols-1 md:grid-cols-3 gap-4 shadow-sm">
    <div class="bg-white p-3 rounded-lg border border-blue-100">
        <p class="text-xs text-blue-600 font-bold uppercase tracking-wider">Total Pagu Disetujui</p>
        <p class="text-xl font-black text-blue-900 mt-1">Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white p-3 rounded-lg border border-green-100">
        <p class="text-xs text-green-600 font-bold uppercase tracking-wider">Dana Sudah Dicairkan</p>
        <p class="text-xl font-black text-green-800 mt-1">Rp {{ number_format($pengajuan->getTotalRealisasi(), 0, ',', '.') }}</p>
    </div>
    <div class="bg-white p-3 rounded-lg border border-orange-100">
        <p class="text-xs text-orange-600 font-bold uppercase tracking-wider">Sisa Saldo Prodi</p>
        <p class="text-xl font-black text-orange-800 mt-1">Rp {{ number_format($pengajuan->sisa_anggaran, 0, ',', '.') }}</p>
    </div>
</div>

<div class="bg-white p-8 rounded-xl shadow-md border border-gray-100" x-data="{ fileName: null }">
    <form action="{{ route('lpj.store', $pengajuan->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Item Pengeluaran / Nota <span class="text-red-500">*</span></label>
            <input type="text" name="item_pengeluaran" value="{{ old('item_pengeluaran') }}" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Cth: Pembelian ATK / Sewa Bus" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Nominal di Kuitansi (Rp) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-mono">Rp</span>
                    <input type="number" name="nominal_penggunaan" value="{{ old('nominal_penggunaan') }}" max="{{ $pengajuan->sisa_anggaran }}" min="1" step="0.01" class="w-full pl-10 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-bold" required>
                </div>
                <p class="text-[10px] text-gray-500 mt-1">Maksimal: Rp {{ number_format($pengajuan->sisa_anggaran, 0, ',', '.') }}</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal di Kuitansi <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal_kuitansi" value="{{ old('tanggal_kuitansi', date('Y-m-d')) }}" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
        </div>

        {{-- PERBAIKAN: Input File Bukti Pengeluaran --}}
        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Upload Bukti Nota/Kuitansi (Wajib) <span class="text-red-500">*</span></label>
            <div class="flex items-center justify-center w-full">
                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fas fa-file-upload text-3xl text-gray-400 mb-2"></i>
                        <p class="mb-1 text-sm text-gray-500 font-medium" x-text="fileName ? fileName : 'Klik atau Drag & Drop file Bukti'"></p>
                        <p class="text-xs text-gray-400" x-show="!fileName">PDF, JPG, PNG (Maks. 10MB)</p>
                    </div>
                    <input type="file" name="bukti_pengeluaran" accept=".pdf,.jpg,.jpeg,.png" class="hidden" required @change="fileName = $event.target.files[0].name" />
                </label>
            </div>
        </div>

        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Keterangan Tambahan (Opsional)</label>
            <textarea name="keterangan" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Catatan tambahan...">{{ old('keterangan') }}</textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <button type="submit" class="bg-blue-600 text-white font-bold px-8 py-3 rounded-xl shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1">
                <i class="fas fa-save mr-2"></i> Submit Bukti LPJ
            </button>
        </div>
    </form>
</div>
@endsection
</x-app-layout>
