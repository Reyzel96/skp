<x-app-layout>
    @section('page-title', 'Dashboard Dekan')

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
                    {{ $user->getRoleNames()->first() ?? 'Dekan Fakultas' }}
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
    @endsection
</x-app-layout>
