{<x-app-layout>
    @section('page-title', 'Pencairan Dana')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            {{-- ALERT NOTIFIKASI (AUTO DISMISS DALAM 3 DETIK) --}}
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.500ms x-init="setTimeout(() => show = false, 3000)"
                     class="flex justify-between items-center bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-lg"></i>
                        <span class="font-semibold text-sm">{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-green-700 hover:text-green-900 focus:outline-none">
                        <i class="fas fa-times font-bold"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.500ms x-init="setTimeout(() => show = false, 5000)"
                     class="flex justify-between items-center bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3 text-lg"></i>
                        <span class="font-semibold text-sm">{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-700 hover:text-red-900 focus:outline-none">
                        <i class="fas fa-times font-bold"></i>
                    </button>
                </div>
            @endif

            {{-- HEADER HALAMAN --}}
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Daftar Pengajuan Siap Dicairkan</h2>
            </div>

            {{-- TABEL DATA --}}
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12 text-center">No</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengaju</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[200px]">Kegiatan</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Pengajuan</th>
                                <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($pengajuanList as $index => $pengajuan)
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-medium">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-4 py-4 text-sm font-semibold text-gray-900 whitespace-nowrap">
                                        {{ $pengajuan->user->name }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 whitespace-normal leading-snug">
                                        {{ $pengajuan->nama_kegiatan }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                        Rp {{ number_format($pengajuan->nominal_diajukan, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span class="bg-gray-100 px-2.5 py-1 rounded-lg border border-gray-200">
                                            {{ $pengajuan->tanggal_pengajuan->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <form action="{{ route('pengajuan-anggaran.cairkan', $pengajuan) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="tanggal_pencairan" value="{{ date('Y-m-d') }}">
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-700 hover:shadow-md transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                                    onclick="return confirm('Yakin cairkan dana untuk kegiatan: {{ $pengajuan->nama_kegiatan }}?')">
                                                <i class="fas fa-check mr-2"></i> Cairkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p class="font-medium text-gray-600">Tidak ada pengajuan yang siap dicairkan.</p>
                                        </div>
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
