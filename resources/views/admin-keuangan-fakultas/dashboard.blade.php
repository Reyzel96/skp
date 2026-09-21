<x-app-layout>
    @section('page-title', 'Dashboard Admin Keuangan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        @php $user = auth()->user(); @endphp

        {{-- BAGIAN BIODATA USER --}}
        <div class="bg-white p-5 md:p-8 rounded-2xl shadow-lg mb-8 border border-gray-100 flex flex-col md:flex-row gap-5 md:gap-6 items-center md:items-start text-center md:text-left">

            {{-- Avatar Placeholder --}}
            <div class="w-20 h-20 md:w-24 md:h-24 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl md:text-3xl font-bold flex-shrink-0">
                {{ strtoupper(substr($user->profile->nama_lengkap ?? $user->name, 0, 1)) }}
            </div>

            {{-- Info Biodata --}}
            <div class="flex-1 w-full mt-2 md:mt-0">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-1">{{ $user->profile->nama_lengkap ?? $user->name }}</h2>
                <p class="text-blue-600 font-semibold mb-6 text-sm md:text-base">
                    {{ $user->getRoleNames()->first() ?? 'Admin Keuangan Fakultas' }}
                </p>

                {{-- Detail Profil Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-5 gap-x-6 text-left">
                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">NIP / NIK</span>
                        <span class="text-gray-800 font-medium break-all sm:break-words">{{ $user->profile->nip ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email</span>
                        <span class="text-gray-800 font-medium break-all sm:break-words">{{ $user->email ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pangkat / Golongan</span>
                        <span class="text-gray-800 font-medium break-words">{{ $user->profile->pangkat_golongan ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jabatan Pekerjaan</span>
                        <span class="text-gray-800 font-medium break-words">{{ $user->profile->jabatan_pekerjaan ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3 sm:col-span-2 lg:col-span-2">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Unit Kerja</span>
                        <span class="text-gray-800 font-medium break-words">{{ $user->profile->unit_kerja ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $statusLabels = [
                'pending'          => 'Pending',
                'disetujui_dekan'  => 'Disetujui Dekan',
                'disetujui_wadek'  => 'Disetujui Wadek',
                'disetujui_admin'  => 'Disetujui Admin',
                'dicairkan'        => 'Dicairkan',
                'selesai'          => 'Selesai',
                'ditolak'          => 'Ditolak',
            ];
            $prodiStatusColors = ['#f59e0b','#3b82f6','#8b5cf6','#10b981','#06b6d4','#64748b','#ef4444'];
        @endphp

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Total Periode Anggaran --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-blue-50 rounded-lg text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Periode Anggaran</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalPeriode ?? 0 }}</p>
            </div>

            {{-- Total Program Fakultas --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-emerald-50 rounded-lg text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-3.422L12 14z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Program Fakultas</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalProgram ?? 0 }}</p>
            </div>

            {{-- Usulan Prodi Menunggu Admin Keuangan --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-orange-50 rounded-lg text-orange-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-6 0h.01M12 16h3m-6 0h.01"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Usulan Prodi Menunggu</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $usulanMenunggu ?? 0 }}</p>
            </div>

            {{-- LPJ Menunggu Verifikasi --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-purple-50 rounded-lg text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">LPJ Menunggu Verifikasi</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $lpjPending ?? 0 }}</p>
            </div>
        </div>

        {{-- DONUT: BREAKDOWN STATUS USULAN PROGRAM PRODI --}}
        <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h2 class="text-base font-semibold text-gray-800">Breakdown Status Usulan Prodi</h2>
            <p class="text-sm text-gray-400 mb-4">Distribusi status persetujuan usulan program Prodi</p>

            @if($totalProdiUsulan > 0)
                <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                    <div class="relative w-44 h-44 sm:w-52 sm:h-52 shrink-0 mx-auto sm:mx-0">
                        <canvas id="donutProdi"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-3xl font-bold text-gray-800">{{ $totalProdiUsulan }}</span>
                            <span class="text-xs text-gray-400">Total Usulan</span>
                        </div>
                    </div>
                    <div class="flex-1 w-full space-y-3 min-w-0">
                        @foreach($prodiStatuses as $status)
                            @php
                                $cnt = $prodiStatusCounts[$status] ?? 0;
                                $pct = $totalProdiUsulan > 0 ? round($cnt / $totalProdiUsulan * 100) : 0;
                                $color = $prodiStatusColors[$loop->index] ?? '#94a3b8';
                            @endphp
                            <div class="flex items-center justify-between text-sm gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $color }}"></span>
                                    <span class="text-gray-600 truncate">{{ $statusLabels[$status] ?? $status }}</span>
                                </div>
                                <span class="font-semibold text-gray-700 shrink-0">{{ $pct }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm">Belum ada data usulan program</p>
                </div>
            @endif
        </div>

        {{-- TABEL: USULAN MENUNGGU ADMIN KEUANGAN --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Usulan Menunggu Admin Keuangan</h2>

            @if($usulanList->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm">Tidak ada usulan yang menunggu saat ini</p>
                </div>
            @else
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="w-full text-sm text-left min-w-[720px]">
                        <thead class="text-[11px] uppercase text-gray-400 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 font-bold">No</th>
                                <th class="px-3 py-3 font-bold">Prodi</th>
                                <th class="px-3 py-3 font-bold">Program</th>
                                <th class="px-3 py-3 font-bold">Nominal Diajukan</th>
                                <th class="px-3 py-3 font-bold">Status</th>
                                <th class="px-3 py-3 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($usulanList as $i => $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-3 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-3 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $item->prodi->nama ?? '-' }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $item->programKegiatan->nama_program ?? $item->nama_kegiatan_prodi ?? '-' }}</td>
                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">Rp {{ number_format((float) $item->anggaran_usulan_prodi, 0, ',', '.') }}</td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">{{ $statusLabels[$item->status_persetujuan] ?? $item->status_persetujuan }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('kegiatan-prodi.list-admin') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800">
                                            Review
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- CHART.JS (CDN) + INIT --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            function initAkCharts() {
                if (typeof Chart === 'undefined') return;

                const donutCanvas = document.getElementById('donutProdi');
                if (donutCanvas) {
                    const prev = Chart.getChart(donutCanvas);
                    if (prev) prev.destroy();
                    const colors = {!! json_encode($prodiStatusColors) !!};
                    new Chart(donutCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: {!! json_encode(array_map(fn($s) => $statusLabels[$s] ?? $s, $prodiStatuses)) !!},
                            datasets: [{
                                data: {!! json_encode(array_values($prodiStatusCounts)) !!},
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '72%',
                            plugins: { legend: { display: false }, tooltip: { enabled: true } }
                        }
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initAkCharts);
            document.addEventListener('turbo:load', initAkCharts);
        </script>
    @endsection
</x-app-layout>
