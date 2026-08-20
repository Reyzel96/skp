<x-app-layout>
    @section('page-title', 'Riwayat LPJ Saya')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Laporan LPJ</h2>
        <p class="text-gray-500 text-sm mt-1">Pilih kegiatan yang dananya sudah dicairkan untuk membuat LPJ, atau lihat riwayat lengkap laporan Anda.</p>
    </div>

    <div x-data="{ tab: 'cair' }">
        <div class="flex gap-4 border-b border-gray-200 mb-6">
            <button @click="tab = 'cair'" :class="tab === 'cair' ? 'border-blue-600 text-blue-600 border-b-2 font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'" class="pb-3 px-4 transition-all focus:outline-none">
                <i class="fas fa-money-bill-wave mr-2"></i> Dana Cair (Siap LPJ)
            </button>
            <button @click="tab = 'riwayat'" :class="tab === 'riwayat' ? 'border-blue-600 text-blue-600 border-b-2 font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'" class="pb-3 px-4 transition-all focus:outline-none">
                <i class="fas fa-list-ul mr-2"></i> Daftar Laporan LPJ
            </button>
        </div>

        {{-- TAB 1: DANA CAIR (MEMBUAT LPJ BARU) --}}
        <div x-show="tab === 'cair'" x-transition>
            <div class="grid gap-6">
                @forelse($pengajuanList as $pengajuan)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 {{ $pengajuan->status_persetujuan === 'selesai' ? 'opacity-80' : '' }}">
                        <div class="flex justify-between items-start mb-4 border-b pb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $pengajuan->nama_kegiatan_prodi ?? $pengajuan->programKegiatan->nama_kegiatan ?? 'Data Induk Dihapus' }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $pengajuan->programKegiatan->nama_program ?? '-' }}</p>
                            </div>
                            <span class="{{ $pengajuan->status_persetujuan === 'selesai' ? 'bg-gray-200 text-gray-800 border-gray-300' : 'bg-green-100 border-green-200 text-green-800' }} border text-xs font-bold px-3 py-1 rounded-full uppercase shadow-sm">
                                {{ str_replace('_', ' ', $pengajuan->status_persetujuan) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Anggaran yg di Cairkan</p>
                                <p class="font-black text-xl text-blue-900 mt-1">Rp {{ number_format($pengajuan->getTotalRealisasi(), 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-100">
                                <p class="text-xs font-bold text-orange-600 uppercase tracking-wider">Telah Digunakan (Total LPJ)</p>
                                <p class="font-black text-xl text-orange-900 mt-1">Rp {{ number_format($pengajuan->getTotalRealisasi() - $pengajuan->sisa_anggaran, 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                <p class="text-xs font-bold text-green-600 uppercase tracking-wider">Sisa Anggaran</p>
                                <p class="font-black text-xl text-green-900 mt-1">Rp {{ number_format($pengajuan->sisa_anggaran, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        @if($pengajuan->daftarLpj && $pengajuan->daftarLpj->isNotEmpty())
                            <h4 class="font-bold text-gray-700 mb-3 border-l-4 border-blue-500 pl-2">Riwayat LPJ Tersimpan:</h4>
                            <div class="space-y-3 mb-6">
                                @foreach($pengajuan->daftarLpj as $lpj)
                                    <div class="flex justify-between items-center bg-gray-50 border border-gray-200 p-4 rounded-lg hover:shadow-sm transition">
                                        <div class="flex-1">
                                            <p class="font-bold text-gray-800">{{ $lpj->item_pengeluaran }}</p>
                                            <div class="flex items-center gap-4 mt-1">
                                                <span class="text-sm font-mono font-bold text-blue-700">Rp {{ number_format($lpj->nominal_penggunaan, 0, ',', '.') }}</span>
                                                <span class="text-xs text-gray-500"><i class="fas fa-calendar-alt mr-1"></i> {{ $lpj->tanggal_kuitansi->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="px-2 py-1 text-[10px] font-bold rounded-full uppercase border shadow-sm {{ $lpj->getStatusClass() }}">{{ $lpj->getStatusLabel() }}</span>
                                            <a href="{{ route('lpj.show', $lpj->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-bold bg-blue-50 px-3 py-1.5 rounded transition">Detail</a>

                                            @if($pengajuan->status_persetujuan !== 'selesai')
                                                <form action="{{ route('lpj.destroy', $lpj->id) }}" method="POST" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Hapus LPJ ini? Saldo akan direfund kembali.')" class="text-red-500 hover:text-red-700 p-1.5 bg-red-50 rounded transition"><i class="fas fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 mb-6 p-6 border-2 border-dashed border-gray-200 rounded-xl text-center text-gray-500">
                                <i class="fas fa-file-invoice text-3xl mb-2 text-gray-300"></i>
                                <p class="font-medium">Belum ada LPJ yang dikirim untuk pencairan ini.</p>
                            </div>
                        @endif

                        {{-- AKSI DINAMIS TERGANTUNG SISA ANGGARAN --}}
                        <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                            @if($pengajuan->status_persetujuan !== 'selesai')

                                @if($pengajuan->sisa_anggaran > 0)
                                    <div class="text-sm text-gray-500 italic flex-1">
                                        <i class="fas fa-info-circle mr-1"></i> Sisa anggaran harus mencapai Rp 0 agar LPJ dapat ditutup.
                                    </div>
                                    <a href="{{ route('lpj.create', $pengajuan->id) }}" class="inline-flex items-center bg-blue-600 text-white font-bold px-6 py-2.5 rounded-lg hover:bg-blue-700 shadow-md transition transform hover:-translate-y-0.5">
                                        <i class="fas fa-plus mr-2"></i> Buat LPJ Baru
                                    </a>
                                @else
                                    <div class="text-sm text-green-600 font-bold flex-1">
                                        <i class="fas fa-check-circle mr-1"></i> Seluruh anggaran telah di-LPJ-kan! Siap ditutup.
                                    </div>
                                    <form action="{{ route('lpj.tutup', $pengajuan->id) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" onclick="return confirm('Yakin ingin menutup LPJ ini? Aksi ini akan mengunci program menjadi Selesai.')" class="inline-flex items-center bg-gray-800 text-white font-bold px-6 py-2.5 rounded-lg hover:bg-black shadow-md transition transform hover:-translate-y-0.5">
                                            <i class="fas fa-lock mr-2"></i> Tutup LPJ (Selesai)
                                        </button>
                                    </form>
                                @endif

                            @else
                                <div class="w-full text-center text-gray-500 font-bold bg-gray-50 py-3 rounded-lg">
                                    <i class="fas fa-check-double text-green-600 mr-2"></i> Rangkaian Laporan LPJ Program Ini Telah Selesai
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-500 border border-gray-200">
                        <i class="fas fa-box-open text-5xl mb-4 text-gray-300"></i>
                        <p class="text-xl font-bold text-gray-700">Belum ada dana yang dicairkan</p>
                        <p class="mt-2 text-sm max-w-md mx-auto">Halaman ini akan menampilkan kegiatan yang dananya sudah dicairkan oleh Admin Keuangan dan siap untuk Anda LPJ-kan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- TAB 2: DAFTAR RIWAYAT LPJ (TETAP SAMA SEPERTI SEBELUMNYA) --}}
        <div x-show="tab === 'riwayat'" x-transition style="display: none;">
            @php
                $allLpj = collect();
                foreach($pengajuanList as $p) {
                    if ($p->daftarLpj && $p->daftarLpj->isNotEmpty()) {
                        foreach($p->daftarLpj as $l) {
                            $l->induk_kegiatan = $p->nama_kegiatan_prodi ?? $p->programKegiatan->nama_kegiatan ?? 'Induk Dihapus';
                            $allLpj->push($l);
                        }
                    }
                }
                $allLpj = $allLpj->sortByDesc('created_at');
            @endphp

            <div class="bg-white rounded-lg shadow overflow-x-auto border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Kuitansi</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sub-Kegiatan LPJ</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Program Induk</th>
                            <th class="px-5 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse($allLpj as $itemLpj)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4 whitespace-nowrap text-gray-500">
                                    {{ $itemLpj->tanggal_kuitansi->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-gray-900">{{ $itemLpj->item_pengeluaran }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-600 text-xs">
                                    {{ Str::limit($itemLpj->induk_kegiatan, 40) }}
                                </td>
                                <td class="px-5 py-4 text-right font-mono font-bold text-blue-700">
                                    Rp {{ number_format($itemLpj->nominal_penggunaan, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap space-x-2">
                                    <a href="{{ route('lpj.show', $itemLpj->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-2 py-1 rounded">
                                        <i class="fas fa-eye mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500 italic">Belum ada riwayat LPJ yang Anda kirimkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
</x-app-layout>
