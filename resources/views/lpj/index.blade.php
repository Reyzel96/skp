<x-app-layout>
    @section('page-title', 'Monitoring LPJ')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</span>
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Monitoring LPJ</h2>

    <div class="bg-white rounded-lg shadow overflow-x-auto border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengaju</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengajuan Anggaran</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kegiatan LPJ</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($lpjList as $index => $lpj)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-4 text-sm">
                            <span class="font-bold text-gray-800">{{ $lpj->programKegiatanProdi->user->name }}</span><br>
                            <span class="text-xs text-gray-500">{{ $lpj->programKegiatanProdi->user->getRoleNames()->first() ?? 'Prodi' }}</span>
                        </td>
                        <td class="px-4 py-4 text-sm">
                            <span class="font-medium text-gray-800">{{ $lpj->programKegiatanProdi->nama_kegiatan_prodi ?? $lpj->programKegiatanProdi->programKegiatan->nama_kegiatan }}</span><br>
                            <span class="text-xs text-blue-600 font-mono">Pagu: Rp {{ number_format($lpj->programKegiatanProdi->anggaran_disetujui_prodi, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-4 text-sm font-bold text-gray-800">{{ $lpj->item_pengeluaran }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-green-700 font-bold">Rp {{ number_format($lpj->nominal_penggunaan, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600">{{ $lpj->tanggal_kuitansi->format('d/m/Y') }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                            <span class="px-2 py-1 text-[10px] rounded-full font-bold uppercase border shadow-sm {{ $lpj->getStatusClass() }}">
                                {{ $lpj->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center text-sm space-x-2">
                            <a href="{{ route('lpj.show', $lpj) }}" class="inline-flex items-center text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-2 py-1 rounded">Detail</a>
                            @if($lpj->isPending() && auth()->user()->hasRole('Admin Keuangan Fakultas'))
                                <button onclick="openVerifyModal({{ $lpj->id }})" class="inline-flex items-center text-purple-600 hover:text-purple-900 font-bold bg-purple-50 px-2 py-1 rounded ml-1">Verifikasi</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500 italic"><i class="fas fa-folder-open text-3xl mb-2 text-gray-300 block"></i> Tidak ada data LPJ yang masuk</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(auth()->user()->hasRole('Admin Keuangan Fakultas'))
        <div id="verifyModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex justify-center items-center backdrop-blur-sm">
            <div class="relative p-6 border shadow-2xl rounded-xl bg-white w-full max-w-md">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-lg font-bold text-gray-900">Verifikasi Bukti Pengeluaran</h3>
                    <button onclick="closeVerifyModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
                </div>
                <form id="verifyForm" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Keputusan *</label>
                        <select name="action" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" required>
                            <option value="verifikasi">Valid / Terima</option>
                            <option value="tolak">Tidak Valid / Tolak</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Catatan (Wajib jika ditolak)</label>
                        <textarea name="catatan_verifikasi" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Tulis alasan jika revisi/tolak..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeVerifyModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold hover:bg-gray-300 transition">Batal</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition">Simpan Keputusan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openVerifyModal(lpjId) {
                document.getElementById('verifyForm').action = `/lpj/${lpjId}/verify`;
                document.getElementById('verifyModal').classList.remove('hidden');
            }
            function closeVerifyModal() {
                document.getElementById('verifyModal').classList.add('hidden');
            }
        </script>
    @endif
@endsection
</x-app-layout>
