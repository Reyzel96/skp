<x-app-layout>
    @section('page-title', 'Dashboard Wakil Dekan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        @php
            $user = Auth::user();
        @endphp

        {{-- BAGIAN BIODATA USER --}}
        <div class="bg-white p-8 rounded-2xl shadow-lg mb-8 border border-gray-100 flex flex-col md:flex-row gap-6 items-center md:items-start">
            {{-- Avatar Placeholder --}}
            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-3xl font-bold flex-shrink-0">
                {{ strtoupper(substr($user->profile->nama_lengkap ?? $user->name, 0, 1)) }}
            </div>

            {{-- Info Biodata --}}
            <div class="flex-1 w-full">
                <div class="flex flex-col md:flex-row justify-between items-center md:items-start mb-1">
                    <h2 class="text-2xl font-bold text-gray-800">{{ $user->profile->nama_lengkap ?? $user->name }}</h2>
                </div>
                <p class="text-blue-600 font-semibold mb-6">{{ $user->getRoleNames()->first() ?? 'Wakil Dekan' }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-6">
                    <div class="flex flex-col border-l-2 border-gray-200 pl-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">NIP / NIK</span>
                        <span class="text-gray-800 font-medium">{{ $user->profile->nip ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-2 border-gray-200 pl-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email</span>
                        <span class="text-gray-800 font-medium">{{ $user->email ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-2 border-gray-200 pl-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pangkat / Golongan</span>
                        <span class="text-gray-800 font-medium">{{ $user->profile->pangkat_golongan ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-2 border-gray-200 pl-3">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Jabatan Fungsional</span>
                        <span class="text-gray-800 font-medium">{{ $user->profile->jabatan_fungsional ?? '-' }}</span>
                    </div>

                    <div class="flex flex-col border-l-2 border-gray-200 pl-3 md:col-span-2 lg:col-span-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Unit Kerja</span>
                        <span class="text-gray-800 font-medium">{{ $user->profile->unit_kerja ?? 'Fakultas Teknik' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
