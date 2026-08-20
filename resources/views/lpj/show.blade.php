<x-app-layout>
    @section('page-title', 'Detail LPJ')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
@php $pengajuan = $lpj->programKegiatanProdi; @endphp

<div class="mb-4">
    <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 font-bold">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="bg-white p-6 rounded-xl shadow-md mb-6 border border-gray-100">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Konteks Induk Pengajuan</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <p class="text-sm text-gray-500 font-medium">Pengaju Prodi</p>
            <p class="font-semibold text-gray-900">{{ $pengajuan->user->name }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Nama Kegiatan</p>
            <p class="font-semibold text-gray-900">{{ $pengajuan->nama_kegiatan_prodi ?? $pengajuan->programKegiatan->nama_kegiatan }}</p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
    <h3 class="text-lg font-bold text-gray-800 border-l-4 border-blue-600 pl-3 mb-6">Rincian Nota/Bukti Ini</h3>

    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <p class="font-black text-xl text-gray-800">{{ $lpj->item_pengeluaran }}</p>
                <p class="text-sm text-gray-500 mt-1"><i class="fas fa-calendar-alt mr-1"></i> Tanggal Kuitansi: {{ $lpj->tanggal_kuitansi->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Nominal Digunakan:</p>
                <p class="font-black text-blue-700 text-2xl font-mono">Rp {{ number_format($lpj->nominal_penggunaan, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mb-4">
            <p class="text-xs font-bold text-gray-700 uppercase">Keterangan / Rincian:</p>
            <p class="text-sm text-gray-600 mt-1">{{ $lpj->keterangan ?? 'Tidak ada keterangan spesifik' }}</p>
        </div>

        <div class="mb-4">
            <p class="text-xs font-bold text-gray-700 uppercase">Status:</p>
            <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-full uppercase border shadow-sm bg-green-100 text-green-800 border-green-200">
                Tercatat
            </span>
        </div>

        @if($lpj->bukti_pengeluaran)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('lpj.download-bukti', $lpj->id) }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition shadow-sm">
                    <i class="fas fa-download"></i> Unduh File Bukti Pengeluaran
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
</x-app-layout>
