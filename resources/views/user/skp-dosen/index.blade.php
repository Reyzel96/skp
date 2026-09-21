<x-app-layout>
    @section('page-title', 'Daftar SKP Dosen Saya')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tighter">Daftar SKP Dosen Saya</h1>
                    <p class="text-xs text-gray-500 uppercase tracking-widest font-bold">Pilih salah satu periode SKP untuk melihat detail atau melakukan pengisian</p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-3 text-sm text-green-700 font-bold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 text-sm text-red-700 font-bold shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-900">
                        <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-[10px] border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-center w-16">No</th>
                                <th class="px-6 py-4">Periode SKP</th>
                                <th class="px-6 py-4">Pejabat Penilai</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($headers as $header)
                                <tr class="hover:bg-emerald-50/50 transition-colors cursor-pointer group" 
                                    data-turbo-row="{{ route('pegawai.skp-dosen.show', $header->id) }}">
                                    <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-black text-emerald-800 uppercase tracking-tight">{{ $header->periode->nama_periode ?? '-' }}</div>
                                        <div class="text-[10px] text-gray-500 font-bold uppercase mt-0.5">
                                            {{ \Carbon\Carbon::parse($header->periode->mulai)->translatedFormat('d F Y') }} - 
                                            {{ \Carbon\Carbon::parse($header->periode->selesai)->translatedFormat('d F Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($header->pejabatPenilai)
                                            <div class="font-bold text-gray-800">{{ $header->pejabatPenilai->profile->nama_lengkap ?? $header->pejabatPenilai->name }}</div>
                                            <div class="text-[10px] text-emerald-600 font-bold uppercase tracking-tighter">Ditetapkan</div>
                                        @else
                                            <div class="text-red-400 italic font-medium">Belum Ditetapkan</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusColor = match($header->status) {
                                                'Disetujui' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'Revisi' => 'bg-red-100 text-red-700 border-red-200',
                                                'Target Diajukan' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'Draft' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                'Diajukan' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                default => 'bg-blue-50 text-blue-600 border-blue-100'
                                            };
                                        @endphp
                                        <span class="{{ $statusColor }} px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                            {{ $header->status ?? 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('pegawai.skp-dosen.show', $header->id) }}" 
                                           class="inline-flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-[10px] font-bold uppercase transition shadow-sm">
                                            Buka Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-medium bg-gray-50/30 italic">
                                        Belum ada data SKP yang ditemukan.
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
