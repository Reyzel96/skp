<x-app-layout>
    @section('page-title', 'Approval Target Skp Tendik')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="p-6 max-w-7xl mx-auto" x-data="{ tab: 'pending' }">
        <h2 class="text-2xl font-black text-gray-800 mb-6 uppercase tracking-tighter">Approval Target Skp Tendik</h2>

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
                <i class="fas fa-history mr-2"></i> Riwayat Penilaian SKP Tendik ({{ $historyHeaders->count() }})
            </button>
        </div>

        {{-- Tab Content: Pending --}}
        <div x-show="tab === 'pending'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Pegawai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4">Jabatan/Unit</th>
                            <th class="px-6 py-4 text-center">Total Kegiatan</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($pendingHeaders as $header)
                            @php $p = $header->user; @endphp
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">{{ $p->profile->nama_lengkap ?? $p->name }}</p>
                                    <p class="text-xs text-gray-500">NIP: {{ $p->profile->nip ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200 font-bold text-[10px]">
                                        {{ $header->periode->nama_periode ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-700 text-xs">{{ $p->getRoleNames()->implode(', ') }}</p>
                                    <p class="text-[10px] text-gray-500 uppercase">{{ $p->profile->unit_kerja ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-black text-xs">
                                        {{ $header->masterActivities->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.approval-skp-tendik.show', $header->id) }}" class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 shadow transition-all">
                                        Review Kegiatan <i class="fas fa-chevron-right ml-2"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-check-circle text-4xl text-green-200 mb-3"></i>
                                        <p class="text-gray-500 italic">Tidak ada kegiatan yang perlu di-review.</p>
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
                            <th class="px-6 py-4">Pegawai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4">Detail Review</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($historyHeaders as $header)
                            @php $p = $header->user; @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">{{ $p->profile->nama_lengkap ?? $p->name }}</p>
                                    <p class="text-[10px] text-gray-500 uppercase">{{ $p->profile->unit_kerja ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200 font-bold text-[10px]">
                                        {{ $header->periode->nama_periode ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-2">
                                        @php
                                            $reviewedActivities = $header->masterActivities->whereIn('status_approval', ['approved', 'rejected']);
                                            $approvedCount = $reviewedActivities->where('status_approval', 'approved')->count();
                                            $rejectedCount = $reviewedActivities->where('status_approval', 'rejected')->count();
                                            
                                            // Ambil daftar nama unik yang melakukan review
                                            $reviewers = $reviewedActivities->map(fn($a) => $a->approver->profile->nama_lengkap ?? ($a->approver->name ?? null))
                                                ->filter()
                                                ->unique();
                                        @endphp
                                        
                                        <div class="flex flex-wrap gap-2">
                                            @if($approvedCount > 0)
                                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold">
                                                    {{ $approvedCount }} Disetujui
                                                </span>
                                            @endif
                                            @if($rejectedCount > 0)
                                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold">
                                                    {{ $rejectedCount }} Ditolak
                                                </span>
                                            @endif
                                        </div>

                                        @if($reviewers->isNotEmpty())
                                            <div class="text-[9px] text-gray-500 italic mt-1">
                                                <i class="fas fa-user-check mr-1"></i> Direview oleh: {{ $reviewers->implode(', ') }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.approval-skp-tendik.show', $header->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">Belum ada riwayat review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
