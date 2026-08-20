<x-app-layout>
    @section('page-title', 'Evaluasi SKP Bawahan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="p-6 max-w-7xl mx-auto" x-data="{ tab: 'pending' }">
        <h2 class="text-2xl font-black text-gray-800 mb-6 uppercase tracking-tighter">Evaluasi Realisasi SKP Bawahan</h2>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Navigasi Tab --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all">
                <i class="fas fa-clock mr-2"></i> Tugas Review Anda ({{ $pendingHeaders->count() }})
            </button>
            <button @click="tab = 'history'" :class="tab === 'history' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all">
                <i class="fas fa-history mr-2"></i> Riwayat Penilaian Skp Pegawai ({{ $historyHeaders->count() }})
            </button>
        </div>

        {{-- Tab Content: Pending --}}
        <div x-show="tab === 'pending'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Nama Pegawai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4 text-center">Status Validasi</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($pendingHeaders as $header)
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">{{ $header->user->profile->nama_lengkap ?? $header->user->name }}</p>
                                    <p class="text-xs text-gray-500 font-semibold">{{ $header->user->getRoleNames()->first() }}</p>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    {{ $header->periode->tahun }} - {{ $header->periode->semester_genap }} / {{ $header->periode->semester_gasal }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($header->status == 'Review Akhir')
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-blue-200">Realisasi: Review Dekan</span>
                                    @elseif($header->status == 'Diajukan')
                                        <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-orange-300">Realisasi: Review Wadek</span>
                                    @elseif($header->status == 'Disetujui')
                                        <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-emerald-200">Target OK: Siap Dievaluasi</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-gray-200">{{ $header->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.evaluasi.show', $header->id) }}" class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow transition-all">
                                        Lihat & Evaluasi <i class="fas fa-chevron-right ml-2"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-check-circle text-4xl text-green-200 mb-3"></i>
                                        <p class="text-gray-500 italic">Tidak ada evaluasi yang menunggu review Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab Content: History --}}
        <div x-show="tab === 'history'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Nama Pegawai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4 text-center">Status Validasi</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($historyHeaders as $header)
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">{{ $header->user->profile->nama_lengkap ?? $header->user->name }}</p>
                                    <p class="text-xs text-gray-500 font-semibold">{{ $header->user->getRoleNames()->first() }}</p>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-700">
                                    {{ $header->periode->tahun }} - {{ $header->periode->semester_genap }} / {{ $header->periode->semester_gasal }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($header->status == 'Selesai')
                                        <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-emerald-200">Evaluasi Selesai (Final)</span>
                                    @elseif($header->status == 'Ditolak')
                                        <span class="bg-rose-100 text-rose-800 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-rose-200">Perlu Revisi / Ditolak</span>
                                    @elseif($header->status == 'Disetujui')
                                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-gray-300">Target Disetujui (Belum Realisasi)</span>
                                    @elseif($header->status == 'Review Akhir')
                                        <span class="bg-blue-50 text-blue-500 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-blue-200">Sedang Review (Dekan)</span>
                                    @elseif($header->status == 'Diajukan')
                                        <span class="bg-orange-50 text-orange-500 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-orange-200">Sedang Review (Wadek)</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-bold text-[10px] uppercase border border-gray-200">{{ $header->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.evaluasi.show', $header->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        {{ $isBypassRole ? 'Pantau Kinerja' : 'Lihat Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">Belum ada riwayat evaluasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
