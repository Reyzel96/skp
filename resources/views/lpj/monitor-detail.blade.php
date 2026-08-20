<x-app-layout>
    @section('page-title', 'Detail Monitoring LPJ')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
    <div class="mb-6">
        <a href="{{ route('lpj.monitoring') }}" class="text-blue-600 hover:text-blue-800 font-bold flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Monitoring
        </a>
    </div>

    {{-- HEADER INFORMASI KEGIATAN --}}
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $pengajuan->nama_kegiatan_prodi }}</h2>
                <p class="text-gray-500">Unit: <span class="font-bold text-blue-600">{{ $pengajuan->user->profile->unit_kerja ?? '-' }}</span></p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 text-xs rounded-full font-bold uppercase border {{ $pengajuan->status_persetujuan === 'selesai' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                    Status: {{ $pengajuan->status_persetujuan === 'selesai' ? 'Selesai' : 'Berjalan' }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">Total Pagu Disetujui</p>
                <p class="text-xl font-black text-blue-700 font-mono">Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-xl border border-green-100">
                <p class="text-[10px] font-bold text-green-400 uppercase tracking-wider mb-1">Total Dana Terserap</p>
                <p class="text-xl font-black text-green-700 font-mono">Rp {{ number_format($pengajuan->daftarLpj->sum('nominal_penggunaan'), 0, ',', '.') }}</p>
            </div>
            <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
                <p class="text-[10px] font-bold text-orange-400 uppercase tracking-wider mb-1">Sisa Anggaran</p>
                <p class="text-xl font-black text-orange-700 font-mono">Rp {{ number_format($pengajuan->sisa_anggaran, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- DAFTAR RINCIAN LPJ --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Rincian Penggunaan Dana (LPJ)</h3>
            <span class="text-xs font-medium text-gray-500">{{ $pengajuan->daftarLpj->count() }} Transaksi</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Kuitansi</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Item Pengeluaran</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Bukti</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pengajuan->daftarLpj as $lpj)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                {{ $lpj->tanggal_kuitansi->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">
                                {{ $lpj->item_pengeluaran }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 italic">
                                {{ $lpj->keterangan ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-green-700 font-bold">
                                Rp {{ number_format($lpj->nominal_penggunaan, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <a href="{{ route('lpj.download-bukti', $lpj) }}" 
                                   class="inline-flex items-center text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-3 py-1.5 rounded-lg transition-all hover:bg-blue-100">
                                    <i class="fas fa-file-download mr-1.5"></i> Unduh
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                                <i class="fas fa-receipt text-4xl mb-3 block opacity-20"></i>
                                Belum ada rincian penggunaan dana untuk kegiatan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($pengajuan->daftarLpj->isNotEmpty())
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-xs font-black text-gray-500 uppercase">Total Terpakai</td>
                        <td class="px-6 py-4 text-right text-sm font-black text-green-800 font-mono">
                            Rp {{ number_format($pengajuan->daftarLpj->sum('nominal_penggunaan'), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
</x-app-layout>
