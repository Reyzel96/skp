<x-app-layout>
    @section('page-title', 'Approval Target SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="p-6 max-w-7xl mx-auto" x-data="{ tab: 'pending' }">
        <h2 class="text-2xl font-black text-gray-800 mb-6 uppercase tracking-tighter">Approval Target SKP Dosen Kualitatif</h2>

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Navigasi Tab --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all">
                <i class="fas fa-clock mr-2"></i> Menunggu Persetujuan
            </button>
            <button @click="tab = 'history'" :class="tab === 'history' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition-all">
                <i class="fas fa-history mr-2"></i> Riwayat Approval
            </button>
        </div>

        @php
            $pendingHeaders = $headers->filter(fn($h) => in_array($h->status, ['Draft', 'Diajukan', 'Target Diajukan', 'Revisi']));
            $historyHeaders = $headers->filter(fn($h) => $h->status === 'Disetujui');
        @endphp

        {{-- Tab Content: Pending --}}
        <div x-show="tab === 'pending'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-emerald-50 text-emerald-800 font-bold uppercase text-[10px] border-b border-emerald-100">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Pegawai Yang Dinilai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($pendingHeaders as $header)
                            @php $p = $header->user; @endphp
                            <tr class="hover:bg-emerald-50/30 transition-colors">
                                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">{{ $p->profile->nama_lengkap ?? $p->name }}</p>
                                    <p class="text-xs text-gray-500">NIP: {{ $p->profile->nip ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-emerald-100 text-emerald-800 px-2 py-1 rounded border border-emerald-200 font-bold text-[10px]">
                                        {{ $header->periode->nama_periode ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-amber-100 text-amber-700 border-amber-200 rounded-full text-[10px] font-bold uppercase">
                                        {{ $header->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.approval-skp-dosen-kualitatif.show', $header->id) }}" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                        <i class="fas fa-search mr-1.5"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-medium bg-gray-50/50 italic">
                                    Tidak ada SKP Dosen Kualitatif yang menunggu persetujuan.
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
                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-[10px] border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 w-12 text-center">No</th>
                            <th class="px-6 py-4">Pegawai Yang Dinilai</th>
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4 text-center">Status</th>
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
                                    <p class="text-xs text-gray-500">NIP: {{ $p->profile->nip ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded border border-gray-200 font-bold text-[10px]">
                                        {{ $header->periode->nama_periode ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-green-100 text-green-700 border-green-200 rounded-full text-[10px] font-bold uppercase">
                                        {{ $header->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('penilai.approval-skp-dosen-kualitatif.show', $header->id) }}" class="inline-flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                        <i class="fas fa-eye mr-1.5"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-medium bg-gray-50/50 italic">
                                    Tidak ada riwayat persetujuan SKP Dosen Kualitatif.
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
