<x-app-layout>
    @section('page-title', 'Rekap SKP Dosen (Kualitatif)')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="max-w-full mx-auto p-6 font-sans">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter">Rekap Nilai SKP Dosen (Kualitatif)</h1>
                <p class="text-gray-500 text-sm font-medium">Periode: {{ $periode->tahun }} - 
                    @if($periode->tipe == 'tahunan') {{ $periode->semester_genap }} / {{ $periode->semester_gasal }}
                    @elseif($periode->tipe == 'semester') Semester {{ ucfirst($periode->semester) }}
                    @elseif($periode->tipe == 'bulanan') Bulan {{ \Carbon\Carbon::create()->month($periode->bulan)->translatedFormat('F') }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('rekap-skp.index') }}" class="bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-xl font-bold hover:bg-gray-50 transition shadow-sm text-xs uppercase tracking-widest">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
                <button onclick="window.print()" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-black transition shadow-sm text-xs uppercase tracking-widest">
                    <i class="fas fa-print mr-2"></i> Cetak Rekap
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-800 shadow-xl overflow-hidden rounded-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-900 text-white uppercase tracking-widest font-black">
                            <th class="border border-gray-800 p-4 text-center w-12">No</th>
                            <th class="border border-gray-800 p-4 text-left">Nama Pegawai / NIP</th>
                            <th class="border border-gray-800 p-4 text-left">Jabatan / Unit Kerja</th>
                            <th class="border border-gray-800 p-4 text-center w-48">Capaian Kinerja Organisasi</th>
                            <th class="border border-gray-800 p-4 text-center w-48 bg-indigo-900">Predikat Kinerja Pegawai</th>
                            <th class="border border-gray-800 p-4 text-center w-32">Status Berkas</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($rekap as $index => $item)
                            <tr class="hover:bg-indigo-50 transition-colors border-b border-gray-200 {{ $item['header'] ? 'cursor-pointer' : '' }}"
                                @if($item['header']) data-turbo-row="{{ route('penilai.evaluasi.show', $item['header']->id) }}" @endif
                                title="{{ $item['header'] ? 'Klik untuk melihat rincian pekerjaan' : 'Pegawai belum mengisi SKP' }}">
                                <td class="border border-gray-300 p-3 text-center font-bold text-gray-400">{{ $index + 1 }}</td>
                                <td class="border border-gray-300 p-3">
                                    <div class="font-black uppercase">{{ $item['user']->profile->nama_lengkap ?? $item['user']->name }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono mt-0.5">NIP. {{ $item['user']->profile->nip ?? '-' }}</div>
                                </td>
                                <td class="border border-gray-300 p-3">
                                    <div class="font-bold text-indigo-800 uppercase">{{ $item['user']->getRoleNames()->first() ?? '-' }}</div>
                                    <div class="text-[10px] text-gray-500 italic uppercase mt-0.5">{{ $item['user']->profile->unit_kerja ?? '-' }}</div>
                                </td>
                                <td class="border border-gray-300 p-3 text-center font-bold text-gray-700 uppercase">
                                    {{ $item['capaian_organisasi'] }}
                                </td>
                                <td class="border border-gray-300 p-3 text-center">
                                    @php
                                        $predikatText = strtoupper($item['predikat']);
                                    @endphp
                                    <span class="px-2 py-1 rounded text-[9px] font-black uppercase tracking-tighter
                                        @if($predikatText == 'SANGAT BAIK') bg-indigo-100 text-indigo-700
                                        @elseif($predikatText == 'BAIK') bg-emerald-100 text-emerald-700
                                        @elseif($predikatText == 'BUTUH PERBAIKAN') bg-yellow-100 text-yellow-700
                                        @elseif($predikatText == 'KURANG' || $predikatText == 'SANGAT KURANG') bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-600 @endif">
                                        {{ $predikatText }}
                                    </span>
                                </td>
                                <td class="border border-gray-300 p-3 text-center">
                                    @if($item['status'] == 'Selesai')
                                        <span class="text-emerald-600 font-bold uppercase text-[9px] tracking-widest flex items-center justify-center">
                                            <i class="fas fa-check-circle mr-1"></i> Selesai
                                        </span>
                                    @elseif($item['status'] == 'Belum Mulai')
                                        <span class="text-gray-400 italic text-[9px]">Belum Mengisi</span>
                                    @else
                                        <span class="text-amber-500 font-bold uppercase text-[9px] tracking-widest flex items-center justify-center">
                                            <i class="fas fa-clock mr-1"></i> {{ $item['status'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-gray-400 italic font-medium">Tidak ada data dosen yang ditemukan untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-12 text-[10px] text-gray-400 font-medium flex justify-between items-center border-t border-gray-100 pt-4">
            <p>* Laporan ini digenerate otomatis oleh sistem pada {{ now()->format('d/m/Y H:i') }}</p>
            <p>Universitas Muhammadiyah Surabaya</p>
        </div>
    </div>

    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .bg-white { border: none !important; box-shadow: none !important; }
            th { background-color: #111827 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .bg-indigo-900 { background-color: #312e81 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
    @endsection
</x-app-layout>
