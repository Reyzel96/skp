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
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-1">{{ $user->profile->nama_lengkap ?? $user->name }}</h2>
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

        {{-- ===================== BARIS STAT CARD ===================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- Total Dosen --}}
            <div class="bg-white rounded-xl shadow border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a3 3 0 10-3-3 3 3 0 003 3z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Dosen</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalDosen }}</p>
                </div>
            </div>

            {{-- Total Tendik --}}
            <div class="bg-white rounded-xl shadow border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0v.75H4.5v-.75z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Tendik</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalTendik }}</p>
                </div>
            </div>

            {{-- Total Prodi --}}
            <div class="bg-white rounded-xl shadow border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.25 6.557A55.375 55.375 0 0112 15.493a55.372 55.372 0 017.25-3.493"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Prodi</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalProdi }}</p>
                </div>
            </div>

            {{-- Total SKP Menunggu Approval --}}
            <div class="bg-white rounded-xl shadow border border-gray-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">SKP Menunggu Approval</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalPending }}</p>
                </div>
            </div>
        </div>

        {{-- ===================== TABEL APPROVAL GABUNGAN ===================== --}}
        @php
            $badge = [
                'Target Diajukan' => 'bg-amber-100 text-amber-800',
                'Diajukan'        => 'bg-amber-100 text-amber-800',
                'Pending Utama'   => 'bg-blue-100 text-blue-800',
                'Pending Akhir'   => 'bg-blue-100 text-blue-800',
                'Review Akhir'    => 'bg-blue-100 text-blue-800',
                'Disetujui'       => 'bg-emerald-100 text-emerald-800',
                'Revisi'          => 'bg-orange-100 text-orange-800',
                'Ditolak'         => 'bg-red-100 text-red-800',
                'Selesai'         => 'bg-gray-100 text-gray-800',
                'Draft'           => 'bg-gray-100 text-gray-800',
                'pending'         => 'bg-amber-100 text-amber-800',
                'approved'        => 'bg-emerald-100 text-emerald-800',
                'rejected'        => 'bg-red-100 text-red-800',
            ];
        @endphp

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5 md:p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">SKP Menunggu Approval</h3>

            @if($approvalRows->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm">Tidak ada SKP yang menunggu approval saat ini</p>
                </div>
            @else
                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="w-full text-sm text-left min-w-[640px]">
                        <thead class="text-[11px] uppercase text-gray-400 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 font-bold">No</th>
                                <th class="px-3 py-3 font-bold">Pegawai</th>
                                <th class="px-3 py-3 font-bold">Jenis</th>
                                <th class="px-3 py-3 font-bold">Periode SKP</th>
                                <th class="px-3 py-3 font-bold">Status Progress</th>
                                <th class="px-3 py-3 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($approvalRows as $i => $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-3 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-3 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $row['pegawai'] }}</td>
                                    <td class="px-3 py-3 text-gray-700 whitespace-nowrap">{{ $row['jenis'] }}</td>
                                    <td class="px-3 py-3 text-gray-600 whitespace-nowrap">{{ $row['periode'] }}</td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $badge[$row['status']] ?? 'bg-gray-100 text-gray-800' }}">{{ $row['status'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ $row['url'] }}" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800">
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

    @endsection
</x-app-layout>
