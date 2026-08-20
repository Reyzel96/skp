<x-app-layout>
    @section('page-title', 'Monitoring LPJ')

{{-- ==========================================
     SIDEBAR
========================================== --}}
@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Monitoring LPJ</h2>

    <div class="bg-white rounded-lg shadow overflow-x-auto border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Unit / Pengaju</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Kegiatan</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Anggaran</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Progres LPJ</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($pengajuanList as $index => $pengajuan)
                    @php
                        $totalLpj = $pengajuan->daftarLpj->sum('nominal_penggunaan');
                        $pagu = $pengajuan->anggaran_disetujui_prodi;
                        $pct = $pagu > 0 ? ($totalLpj / $pagu) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-4 text-sm">
                            <span class="font-bold text-gray-800">{{ $pengajuan->user->profile->unit_kerja ?? $pengajuan->user->name }}</span><br>
                            <span class="text-xs text-gray-500">{{ $pengajuan->user->profile->nama_lengkap ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-4 text-sm font-medium text-gray-800">
                            {{ $pengajuan->nama_kegiatan_prodi }}
                        </td>
                        <td class="px-4 py-4 text-sm">
                            <span class="text-xs text-gray-500 block uppercase font-bold tracking-tighter">Pagu:</span>
                            <span class="font-mono text-blue-700 font-bold">Rp {{ number_format($pagu, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-gray-600">{{ number_format($pct, 1) }}%</span>
                            </div>
                            <span class="text-[10px] text-gray-500 mt-1 block">Terserap: Rp {{ number_format($totalLpj, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            @if($pengajuan->status_persetujuan === 'selesai')
                                <span class="px-2 py-1 text-[10px] rounded-full font-bold uppercase border bg-green-50 text-green-700 border-green-200 shadow-sm">
                                    Selesai / Ditutup
                                </span>
                            @else
                                <span class="px-2 py-1 text-[10px] rounded-full font-bold uppercase border bg-blue-50 text-blue-700 border-blue-200 shadow-sm">
                                    Berjalan
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                            <a href="{{ route('lpj.monitor-detail', $pengajuan->id) }}" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100 transition-all hover:bg-blue-100">
                                <i class="fas fa-search-plus mr-1.5"></i> Detail LPJ
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 italic"><i class="fas fa-folder-open text-3xl mb-2 text-gray-300 block"></i> Tidak ada kegiatan yang terpantau</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
</x-app-layout>
