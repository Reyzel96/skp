<x-app-layout>
    @section('page-title', 'Dashboard Rektorat')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        @php $user = auth()->user(); @endphp

        {{-- BAGIAN BIODATA USER (RESPONSIVE) --}}
        <div class="bg-white p-5 md:p-8 rounded-2xl shadow-lg mb-8 border border-gray-100 flex flex-col md:flex-row gap-5 md:gap-6 items-center md:items-start text-center md:text-left">
            {{-- Avatar Placeholder --}}
            <div class="w-20 h-20 md:w-24 md:h-24 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl md:text-3xl font-bold flex-shrink-0">
                {{ strtoupper(substr($user->profile->nama_lengkap ?? $user->name, 0, 1)) }}
            </div>

            {{-- Info Biodata --}}
            <div class="flex-1 w-full mt-2 md:mt-0">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-1">Selamat Datang, {{ $user->profile->nama_lengkap ?? $user->name }}</h2>
                <p class="text-blue-600 font-semibold mb-6 text-sm md:text-base">
                    {{ $user->getRoleNames()->first() ?? 'Rektorat' }}
                </p>

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
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jabatan Fungsional</span>
                        <span class="text-gray-800 font-medium break-words">{{ $user->profile->jabatan_fungsional ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-4 md:border-l-2 border-blue-200 md:border-gray-200 pl-3 sm:col-span-2 lg:col-span-2">
                        <span class="text-[11px] md:text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Unit Kerja</span>
                        <span class="text-gray-800 font-medium break-words">{{ $user->profile->unit_kerja ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SKP MONITORING SECTION --}}
        <div class="grid grid-cols-1 gap-8 mb-8">
            {{-- SKP Status Chart/Summary --}}
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-chart-pie mr-3 text-blue-600"></i> Monitoring SKP Seluruh Pegawai
                    </h3>
                </div>
                
                <div class="space-y-4">
                    {{-- Pending --}}
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 font-medium">Menunggu Persetujuan</span>
                            <span class="text-orange-600 font-bold">{{ $skpStats['pending'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php 
                                $totalSkp = array_sum($skpStats);
                                $pendingPct = $totalSkp > 0 ? ($skpStats['pending'] / $totalSkp) * 100 : 0;
                                $approvedPct = $totalSkp > 0 ? ($skpStats['disetujui'] / $totalSkp) * 100 : 0;
                                $draftPct = $totalSkp > 0 ? ($skpStats['draft'] / $totalSkp) * 100 : 0;
                            @endphp
                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $pendingPct }}%"></div>
                        </div>
                    </div>

                    {{-- Approved --}}
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 font-medium">Sudah Disetujui</span>
                            <span class="text-green-600 font-bold">{{ $skpStats['disetujui'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $approvedPct }}%"></div>
                        </div>
                    </div>

                    {{-- Draft --}}
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600 font-medium">Masih Draft / Belum Diajukan</span>
                            <span class="text-gray-400 font-bold">{{ $skpStats['draft'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: {{ $draftPct }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-50 flex items-center justify-between">
                    <div class="text-sm text-gray-500 italic">
                        * Data real-time dari seluruh fakultas
                    </div>
                    <a href="{{ route('lpj.monitoring') }}" class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center">
                        Lihat Monitoring LPJ <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
