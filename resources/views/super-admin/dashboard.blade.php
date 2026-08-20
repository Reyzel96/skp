<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        {{-- STATISTICS CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalUsers ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-xl">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Periode SKP</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $totalPeriodeSkp ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- QUICK ACTIONS --}}
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            {{-- <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2> --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('superadmin.skp-tendik.index') }}"
                    class="group flex flex-col items-center p-6 border-2 border-dashed border-blue-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-blue-600 mb-1 text-center">Buat SKP Tendik</h3>
                    <p class="text-sm text-gray-500 text-center">Input kegiatan untuk tendik</p>
                </a>

                <a href="{{ route('superadmin.skp-dosen.create') }}"
                    class="group flex flex-col items-center p-6 border-2 border-dashed border-emerald-200 rounded-xl hover:border-emerald-400 hover:bg-emerald-50 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-emerald-600 mb-1 text-center">Buat SKP Dosen
                    </h3>
                    <p class="text-sm text-gray-500 text-center">Input kegiatan untuk dosen</p>
                </a>

                <a href="{{ route('superadmin.periode-skp.index') }}"
                    class="group flex flex-col items-center p-6 border-2 border-dashed border-purple-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-300">
                    <div
                        class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-500 group-hover:text-white transition-colors mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-purple-600 mb-1 text-center">Kelola Periode</h3>
                    <p class="text-sm text-gray-500 text-center">Atur periode semester SKP</p>
                </a>
            </div>
        </div>
    @endsection
</x-app-layout>
