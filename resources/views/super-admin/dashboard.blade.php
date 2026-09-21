<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        @php
            $totalSkp = array_sum($skpStatusCounts);
            $statusColors = ['#94a3b8', '#3b82f6', '#f59e0b', '#8b5cf6', '#10b981'];
            $topRoles = $rolesDist->take(6);
            $moreRoles = $rolesDist->count() - 6;
        @endphp

        {{-- STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            {{-- Total Users (SATU-SATUNYA dengan sparkline asli) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-blue-50 rounded-lg text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalUsers ?? 0 }}</p>
            </div>

            {{-- Total Prodi (tanpa sparkline) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-emerald-50 rounded-lg text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0112 21a12.083 12.083 0 01-6.16-3.422L12 14z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Prodi</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalProdi ?? 0 }}</p>
            </div>

            {{-- Total Periode SKP (tanpa sparkline) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-orange-50 rounded-lg text-orange-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Periode SKP</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalPeriodeSkp ?? 0 }}</p>
            </div>

            {{-- Total Role (tanpa sparkline) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-purple-50 rounded-lg text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total Role</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalRole ?? 0 }}</p>
            </div>

            {{-- Total SKP Dosen (tanpa sparkline) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-teal-50 rounded-lg text-teal-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total SKP Dosen</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalSkpDosen ?? 0 }}</p>
            </div>

            {{-- Total SKP Tendik (tanpa sparkline) --}}
            <div class="relative bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div class="absolute top-4 right-4 p-2.5 bg-indigo-50 rounded-lg text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"></path></svg>
                </div>
                <p class="text-xs font-medium text-gray-500">Total SKP Tendik</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalSkpTendik ?? 0 }}</p>
            </div>
        </div>

        {{-- DONUT + DISTRIBUSI ROLE --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- DONUT: BREAKDOWN STATUS SKP --}}
            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Breakdown Status SKP</h2>
                <p class="text-sm text-gray-400 mb-4">Distribusi status pengajuan SKP saat ini</p>
                <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                    <div class="relative w-44 h-44 sm:w-52 sm:h-52 shrink-0 mx-auto sm:mx-0">
                        <canvas id="donutSkp"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-3xl font-bold text-gray-800">{{ $totalSkp }}</span>
                            <span class="text-xs text-gray-400">Total SKP</span>
                        </div>
                    </div>
                    <div class="flex-1 w-full space-y-3 min-w-0">
                        @foreach($skpStatuses as $status)
                            @php
                                $cnt = $skpStatusCounts[$status] ?? 0;
                                $pct = $totalSkp > 0 ? round($cnt / $totalSkp * 100) : 0;
                                $color = $statusColors[$loop->index] ?? '#94a3b8';
                            @endphp
                            <div class="flex items-center justify-between text-sm gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $color }}"></span>
                                    <span class="text-gray-600 truncate">{{ $status }}</span>
                                </div>
                                <span class="font-semibold text-gray-700 shrink-0">{{ $pct }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- DISTRIBUSI USER PER ROLE (progress) --}}
            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Distribusi User</h2>
                <p class="text-sm text-gray-400 mb-4">Jumlah user per role (top {{ $topRoles->count() }})</p>
                <div class="space-y-4">
                    @foreach($topRoles as $role)
                        @php
                            $pct = $totalUsers > 0 ? round($role->users_count / $totalUsers * 100) : 0;
                        @endphp
                        <div class="min-w-0">
                            <div class="flex justify-between text-sm mb-1 gap-3">
                                <span class="text-gray-600 truncate">{{ $role->name }}</span>
                                <span class="font-semibold text-gray-700 shrink-0">{{ $role->users_count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full bg-lime-500" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                    @if($moreRoles > 0)
                        <p class="text-xs text-gray-400 pt-1">+{{ $moreRoles }} role lainnya</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- QUICK ACTIONS (redesign) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <a href="{{ route('superadmin.master-skp-tendik.create') }}"
                    class="group flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:shadow-md hover:border-lime-200 hover:bg-lime-50/40 transition-all duration-200">
                    <div class="p-3 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-500 group-hover:text-white transition-colors shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 group-hover:text-blue-700 truncate">Buat SKP Tendik</h3>
                        <p class="text-sm text-gray-500 truncate">Input kegiatan untuk tendik</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-lime-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('superadmin.skp-dosen.create') }}"
                    class="group flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:shadow-md hover:border-lime-200 hover:bg-lime-50/40 transition-all duration-200">
                    <div class="p-3 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-500 group-hover:text-white transition-colors shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 group-hover:text-emerald-700 truncate">Buat SKP Dosen</h3>
                        <p class="text-sm text-gray-500 truncate">Input kegiatan untuk dosen</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-lime-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('superadmin.periode-skp.index') }}"
                    class="group flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:shadow-md hover:border-lime-200 hover:bg-lime-50/40 transition-all duration-200">
                    <div class="p-3 rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-500 group-hover:text-white transition-colors shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 group-hover:text-purple-700 truncate">Kelola Periode</h3>
                        <p class="text-sm text-gray-500 truncate">Atur periode semester SKP</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-lime-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>

        {{-- CHART.JS (CDN) + INIT --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            function initDashboardCharts() {
                if (typeof Chart === 'undefined') return;

                // Donut: Breakdown Status SKP (data ASLI)
                const donutCanvas = document.getElementById('donutSkp');
                if (donutCanvas) {
                    const prev = Chart.getChart(donutCanvas);
                    if (prev) prev.destroy();
                    const statusColors = {!! json_encode($statusColors) !!};
                    new Chart(donutCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: {!! json_encode($skpStatuses) !!},
                            datasets: [{
                                data: {!! json_encode(array_values($skpStatusCounts)) !!},
                                backgroundColor: statusColors,
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

            document.addEventListener('DOMContentLoaded', initDashboardCharts);
            document.addEventListener('turbo:load', initDashboardCharts);
        </script>
    @endsection
</x-app-layout>
