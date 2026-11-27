<x-app-layout>
    @section('page-title', 'Rekap SKP Pegawai')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="max-w-7xl mx-auto p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter">Rekap Nilai SKP</h1>
            <p class="text-gray-500 text-sm">Pilih periode untuk melihat rekapitulasi nilai prestasi kerja pegawai.</p>
        </div>

        <div class="bg-white border border-gray-800 shadow-xl overflow-hidden rounded-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-white uppercase tracking-widest font-black text-[11px]">
                            <th class="border border-gray-800 p-4 text-center w-12">No</th>
                            <th class="border border-gray-800 p-4 text-center w-24">Tahun</th>
                            <th class="border border-gray-800 p-4 text-center w-32">Tipe</th>
                            <th class="border border-gray-800 p-4 text-left">Keterangan Periode</th>
                            <th class="border border-gray-800 p-4 text-center w-64">Pilih Rekapitulasi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($periode as $index => $p)
                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-200">
                                <td class="border border-gray-300 p-3 text-center font-bold text-gray-400">{{ $index + 1 }}</td>
                                <td class="border border-gray-300 p-3 text-center font-black text-blue-900 text-lg">{{ $p->tahun }}</td>
                                <td class="border border-gray-300 p-3 text-center">
                                    <span class="bg-blue-100 text-blue-700 text-[9px] font-black px-2 py-1 rounded uppercase tracking-widest border border-blue-200">
                                        {{ $p->tipe }}
                                    </span>
                                </td>
                                <td class="border border-gray-300 p-3 font-bold text-gray-700">
                                    {{ $p->nama_periode }}
                                </td>
                                <td class="border border-gray-300 p-3">
                                    <div class="flex flex-col sm:flex-row gap-2 justify-center">
                                        <a href="{{ route('rekap-skp.dosen', $p->id) }}" 
                                            class="inline-flex items-center justify-center bg-gray-900 text-white font-bold py-2 px-4 rounded-lg text-[10px] uppercase tracking-widest hover:bg-black transition shadow-sm">
                                            <i class="fas fa-chalkboard-teacher mr-2"></i> Rekap Dosen
                                        </a>
                                        
                                        <a href="{{ route('rekap-skp.dosen-kualitatif', $p->id) }}" 
                                            class="inline-flex items-center justify-center bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition shadow-sm">
                                            <i class="fas fa-file-signature mr-2"></i> Rekap Dosen (Kualitatif)
                                        </a>
                                        
                                        @if(!auth()->user()->hasAnyRole(['Kaprodi Informatika', 'Kaprodi Teknik Elektro', 'Kaprodi Teknik Mesin', 'Kaprodi Teknik Perkapalan', 'Kaprodi Teknik Sipil', 'Kaprodi Teknik Industri', 'Kaprodi Arsitek']))
                                            <a href="{{ route('rekap-skp.tendik', $p->id) }}" 
                                                class="inline-flex items-center justify-center bg-emerald-600 text-white font-bold py-2 px-4 rounded-lg text-[10px] uppercase tracking-widest hover:bg-emerald-700 transition shadow-sm">
                                                <i class="fas fa-user-tie mr-2"></i> Rekap Tendik
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-20 text-center text-gray-400 italic font-medium bg-gray-50">
                                    <i class="fas fa-calendar-times text-4xl block mb-4 opacity-20"></i>
                                    Administrator belum membuat periode penilaian SKP.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
