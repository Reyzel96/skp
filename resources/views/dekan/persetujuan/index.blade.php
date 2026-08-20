<x-app-layout>
    @section('page-title', 'Persetujuan SKP Bawahan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="p-6 max-w-7xl mx-auto" x-data="{ tab: 'pending' }">
        <h2 class="text-2xl font-black text-gray-800 mb-6 uppercase tracking-tighter italic">Verifikasi & Persetujuan Target SKP</h2>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-800 px-4 py-3 rounded shadow-sm flex items-center">
                <i class="fas fa-info-circle mr-2"></i> {{ session('info') }}
            </div>
        @endif

        {{-- Navigasi Tab --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all uppercase tracking-tight">
                <i class="fas fa-clock mr-2"></i> Tugas Review Anda ({{ $pengajuan->filter(fn($item) => in_array($item->status, ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan']))->count() }})
            </button>
            <button @click="tab = 'history'" :class="tab === 'history' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all uppercase tracking-tight">
                <i class="fas fa-history mr-2"></i> Riwayat Persetujuan Target ({{ $pengajuan->filter(fn($item) => $item->status === 'Disetujui')->count() }})
            </button>
        </div>

        {{-- Tab Content: Pending --}}
        <div x-show="tab === 'pending'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase border-b border-gray-200 text-[10px] tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Pegawai</th>
                            <th class="px-6 py-4">Periode SKP</th>
                            <th class="px-6 py-4 text-center">Status Progress</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $pendingCount = 0; @endphp
                        @foreach($pengajuan as $item)
                            @if(in_array($item->status, ['Target Diajukan', 'Pending Utama', 'Pending Akhir', 'Diajukan']))
                                @php $pendingCount++; @endphp
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $pendingCount }}</td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900 text-base">{{ $item->user->profile->nama_lengkap ?? $item->user->name }}</p>
                                        <p class="text-xs text-gray-500">NIP: {{ $item->user->profile->nip ?? '-' }}</p>
                                        <p class="text-[10px] text-gray-400 uppercase mt-0.5">{{ $item->user->profile->jabatan_pekerjaan ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 mb-1">
                                            {{ $item->periode->tahun ?? '-' }}
                                        </span>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-tight font-semibold">
                                            {{ $item->periode->semester_genap ?? '-' }} / {{ $item->periode->semester_gasal ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($item->status == 'Target Diajukan')
                                            <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full font-black text-[9px] uppercase border border-orange-200">
                                                ⏳ Review Target
                                            </span>
                                        @elseif($item->status == 'Pending Utama')
                                            <span class="bg-orange-50 text-orange-700 px-3 py-1 rounded-full font-black text-[9px] uppercase border border-orange-100">
                                                ⏳ Review Utama
                                            </span>
                                        @elseif($item->status == 'Pending Akhir')
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-black text-[9px] uppercase border border-blue-200">
                                                ⏳ Review Dekan
                                            </span>
                                        @elseif($item->status == 'Diajukan')
                                            <span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full font-black text-[9px] uppercase border border-amber-200">
                                                ⏳ Review Realisasi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('dekan.persetujuan-skp.show', $item->id) }}" class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow transition-all">
                                            Review SKP <i class="fas fa-chevron-right ml-2"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        @if($pendingCount === 0)
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                    <i class="fas fa-check-circle text-4xl text-green-200 mb-3 block"></i>
                                    Tidak ada target SKP yang perlu diverifikasi.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab Content: History --}}
        <div x-show="tab === 'history'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase border-b border-gray-200 text-[10px] tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Pegawai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4 text-center">Disetujui Oleh</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $historyCount = 0; @endphp
                        @foreach($pengajuan as $item)
                            @if($item->status === 'Disetujui')
                                @php $historyCount++; @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $historyCount }}</td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900">{{ $item->user->profile->nama_lengkap ?? $item->user->name }}</p>
                                        <p class="text-[10px] text-gray-400 uppercase">{{ $item->user->profile->jabatan_pekerjaan ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-bold text-gray-700">{{ $item->periode->tahun ?? '-' }}</p>
                                        <p class="text-[9px] text-gray-500 uppercase">{{ $item->periode->semester_genap ?? '-' }} / {{ $item->periode->semester_gasal ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($item->approver)
                                            <p class="text-xs font-bold text-emerald-700">{{ $item->approver->profile->nama_lengkap ?? $item->approver->name }}</p>
                                            <p class="text-[9px] text-gray-400 font-medium uppercase">{{ $item->approver->getRoleNames()->first() ?? '-' }}</p>
                                        @else
                                            <span class="text-gray-300 italic text-[10px]">Auto-Approved</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('dekan.persetujuan-skp.show', $item->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-tighter underline">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        @if($historyCount === 0)
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">Belum ada riwayat persetujuan.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
