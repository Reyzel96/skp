<x-app-layout>
    @section('page-title', 'Rekap SKP Tendik')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="max-w-full mx-auto p-6 font-sans relative min-h-screen">
        {{-- Subtle Background Decorative Blobs --}}
        <div class="absolute top-0 left-0 w-64 h-64 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob pointer-events-none"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-teal-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div>
                    <h1 class="text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-emerald-700 via-teal-600 to-emerald-500 uppercase tracking-tighter drop-shadow-sm mb-2">
                        Rekap Nilai SKP Tendik
                    </h1>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/60 backdrop-blur-sm border border-emerald-100 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-emerald-800 text-sm font-bold tracking-wide">Periode: {{ $periode->tahun }} - 
                            @if($periode->tipe == 'tahunan') {{ $periode->semester_genap }} / {{ $periode->semester_gasal }}
                            @elseif($periode->tipe == 'semester') Semester {{ ucfirst($periode->semester) }}
                            @elseif($periode->tipe == 'bulanan') Bulan {{ \Carbon\Carbon::create()->month($periode->bulan)->translatedFormat('F') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('rekap-skp.index') }}" class="group relative inline-flex items-center justify-center px-6 py-2.5 font-bold text-gray-700 bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                        <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-gray-200"></span>
                        <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> <span class="relative uppercase text-xs tracking-widest">Kembali</span>
                    </a>
                    <button onclick="window.print()" class="group relative inline-flex items-center justify-center px-6 py-2.5 font-bold text-white bg-gradient-to-r from-emerald-800 to-teal-700 rounded-xl overflow-hidden shadow-lg hover:shadow-emerald-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                        <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                        <i class="fas fa-print mr-2 group-hover:scale-110 transition-transform"></i> <span class="relative uppercase text-xs tracking-widest">Cetak Rekap</span>
                    </button>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-xl border border-gray-100 shadow-2xl rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white uppercase tracking-widest font-black text-xs">
                                <th class="p-5 text-center w-12 border-b-2 border-emerald-500/50">No</th>
                                <th class="p-5 text-left border-b-2 border-emerald-500/50">Nama Pegawai / NIP</th>
                                <th class="p-5 text-left border-b-2 border-emerald-500/50">Jabatan / Unit Kerja</th>
                                <th class="p-5 text-center w-28 border-b-2 border-emerald-500/50">
                                    <div class="flex flex-col items-center">
                                        <span>Nilai SKP</span>
                                        <span class="text-[9px] text-emerald-400 mt-0.5 bg-emerald-900/50 px-2 py-0.5 rounded-full">(60%)</span>
                                    </div>
                                </th>
                                <th class="p-5 text-center w-28 border-b-2 border-emerald-500/50">
                                    <div class="flex flex-col items-center">
                                        <span>Nilai Perilaku</span>
                                        <span class="text-[9px] text-teal-400 mt-0.5 bg-teal-900/50 px-2 py-0.5 rounded-full">(40%)</span>
                                    </div>
                                </th>
                                <th class="p-5 text-center w-32 border-b-4 border-emerald-400 bg-emerald-800/20">Nilai Prestasi</th>
                                <th class="p-5 text-center w-32 border-b-2 border-emerald-500/50">Predikat</th>
                                <th class="p-5 text-center w-32 border-b-2 border-emerald-500/50">Status Berkas</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800 divide-y divide-gray-100">
                            @forelse($rekap as $index => $item)
                                <tr class="group hover:bg-gradient-to-r hover:from-emerald-50 hover:to-transparent transition-all duration-300 {{ $item['header'] ? 'cursor-pointer' : '' }}"
                                    @if($item['header']) onclick="window.location='{{ route('penilai.evaluasi.show', $item['header']->id) }}'" @endif
                                    title="{{ $item['header'] ? 'Klik untuk melihat rincian pekerjaan' : 'Pegawai belum mengisi SKP' }}">
                                    <td class="p-4 text-center font-black text-gray-300 group-hover:text-emerald-500 transition-colors">{{ $index + 1 }}</td>
                                    <td class="p-4">
                                        <div class="font-black uppercase text-gray-800 group-hover:text-emerald-700 transition-colors">{{ $item['user']->profile->nama_lengkap ?? $item['user']->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-1 flex items-center gap-1">
                                            <i class="fas fa-id-badge text-gray-300"></i> {{ $item['user']->profile->nip ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-teal-700 uppercase">{{ $item['user']->getRoleNames()->first() ?? '-' }}</div>
                                        <div class="text-[10px] text-gray-500 italic uppercase mt-1 flex items-center gap-1">
                                            <i class="fas fa-building text-gray-300"></i> {{ $item['user']->profile->unit_kerja ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="p-4 text-center font-bold text-gray-600 group-hover:text-gray-900 transition-colors">{{ number_format($item['score_skp'] * 0.6, 2, ',', '.') }}</td>
                                    <td class="p-4 text-center font-bold text-gray-600 group-hover:text-gray-900 transition-colors">{{ number_format($item['score_perilaku'] * 0.4, 2, ',', '.') }}</td>
                                    <td class="p-4 text-center">
                                        <div class="inline-flex items-center justify-center bg-gradient-to-br from-emerald-100 to-teal-50 text-emerald-900 font-black px-4 py-2 rounded-xl shadow-sm border border-emerald-200/50 group-hover:scale-110 transition-transform duration-300">
                                            {{ number_format($item['final_score'], 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm border
                                            @if($item['predikat'] == 'Sangat Baik') bg-gradient-to-r from-blue-50 to-blue-100 text-blue-700 border-blue-200
                                            @elseif($item['predikat'] == 'Baik') bg-gradient-to-r from-emerald-50 to-emerald-100 text-emerald-700 border-emerald-200
                                            @elseif($item['predikat'] == 'Cukup') bg-gradient-to-r from-amber-50 to-amber-100 text-amber-700 border-amber-200
                                            @else bg-gradient-to-r from-red-50 to-red-100 text-red-700 border-red-200 @endif">
                                            {{ $item['predikat'] }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($item['status'] == 'Selesai')
                                            <div class="flex items-center justify-center gap-1.5 text-emerald-600 font-bold uppercase text-[10px] tracking-widest bg-emerald-50 py-1.5 px-2 rounded-lg border border-emerald-100">
                                                <i class="fas fa-check-circle animate-bounce-slow"></i> Selesai
                                            </div>
                                        @elseif($item['status'] == 'Belum Mulai')
                                            <div class="flex items-center justify-center gap-1.5 text-gray-400 font-bold uppercase text-[10px] tracking-widest bg-gray-50 py-1.5 px-2 rounded-lg border border-gray-100">
                                                <i class="fas fa-minus-circle"></i> Kosong
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center gap-1.5 text-amber-500 font-bold uppercase text-[10px] tracking-widest bg-amber-50 py-1.5 px-2 rounded-lg border border-amber-100">
                                                <i class="fas fa-spinner fa-spin"></i> Proses
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i class="fas fa-folder-open text-4xl mb-3 opacity-50"></i>
                                            <p class="font-bold tracking-wide uppercase text-sm">Tidak ada data tendik yang ditemukan</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-8 flex justify-between items-center text-[11px] text-gray-400 font-bold uppercase tracking-widest bg-white/60 backdrop-blur-md p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-2">
                    <i class="fas fa-robot text-emerald-500"></i>
                    <p>Digenerate Otomatis pada {{ now()->format('d/m/Y H:i') }}</p>
                </div>
                <p class="text-emerald-700">Universitas Muhammadiyah Surabaya</p>
            </div>
        </div>
    </div>

    <style>
        /* Custom Animations */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animate-bounce-slow {
            animation: bounce 2s infinite;
        }

        /* Print Styles */
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .bg-gradient-to-r, .bg-white\/80, .backdrop-blur-xl, .shadow-2xl { 
                background: none !important; 
                backdrop-filter: none !important; 
                box-shadow: none !important; 
                border: 1px solid #000 !important;
            }
            th { 
                background-color: #111827 !important; 
                color: white !important; 
                -webkit-print-color-adjust: exact; 
                border: 1px solid #000 !important;
            }
            td {
                border: 1px solid #ccc !important;
            }
            .text-transparent {
                color: #047857 !important; /* fallback to emerald-700 */
                -webkit-text-fill-color: #047857 !important;
            }
        }
    </style>
    @endsection
</x-app-layout>
