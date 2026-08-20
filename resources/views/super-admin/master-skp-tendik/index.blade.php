<x-app-layout>
    @section('page-title', 'Database Tupoksi Pegawai')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            .excel-table th {
                background-color: #f8fafc;
                border: 1px solid #cbd5e1 !important;
                font-size: 11px;
                text-transform: uppercase;
                font-weight: 800;
                padding: 10px 4px !important;
                text-align: center;
                vertical-align: middle;
                color: #475569;
                letter-spacing: 0.05em;
            }

            .excel-table td {
                border: 1px solid #cbd5e1 !important;
                padding: 8px 6px !important;
                font-size: 11px;
                vertical-align: middle;
            }
        </style>

        <div class="w-full max-w-full mx-auto p-4 min-w-0 grid grid-cols-1">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 w-full">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">Database Tupoksi Pegawai</h1>
                </div>
                <a href="{{ route('superadmin.master-skp-tendik.create') }}"
                    class="w-full md:w-auto text-center inline-flex items-center justify-center bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg text-sm">
                    <i class="fas fa-plus mr-2"></i> Tambah Kegiatan Pegawai Baru
                </a>
            </div>

            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
                <form id="filterForm" action="{{ route('superadmin.master-skp-tendik.index') }}" method="GET"
                    class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:max-w-xl">
                    <span class="font-bold text-xs uppercase text-gray-400 tracking-widest whitespace-nowrap">Filter
                        Jabatan</span>
                    <select name="role_name" onchange="this.form.submit()"
                        class="w-full sm:flex-1 rounded-lg border-gray-300 focus:ring-blue-500 text-sm font-bold text-blue-700 bg-blue-50 px-3 py-2">
                        <option value="">-- Tampilkan Semua Jabatan --</option>
                        @foreach ($roles as $role)
                            @php $roleName = is_object($role) ? $role->name : $role; @endphp
                            <option value="{{ $roleName }}" {{ request('role_name') == $roleName ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    @if (request('role_name'))
                        <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                            class="text-red-600 font-bold text-xs uppercase hover:underline whitespace-nowrap">Reset</a>
                    @endif
                </form>

                @php $groupedSkp = $masterSkp->groupBy('user_id'); @endphp
                <div class="text-left sm:text-right w-full sm:w-auto">
                    <span
                        class="text-[10px] font-bold text-gray-400 uppercase bg-gray-100 px-3 py-1 rounded-md border border-gray-200">
                        Total: {{ $groupedSkp->count() }} Pegawai
                    </span>
                </div>
            </div>

            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                <table class="w-full table-fixed border-collapse excel-table bg-white">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 20%;">Nama Pegawai</th>
                            <th style="width: 12%;">NIP</th>
                            <th style="width: 18%;">Bidang/Jabatan</th>
                            <th style="width: 10%;">Jumlah Tugas</th>
                            <th style="width: 15%;">Status Approval</th>
                            <th style="width: 10%;">Total Volume</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($groupedSkp as $userId => $activities)
                            @php
                                $firstActivity = $activities->first();
                                $user = $firstActivity->user;
                                $totalTugas = $activities->count();
                                $semuaJabatan = $activities->pluck('role_name')->unique()->implode(', ');
                                $totalVolume = $activities->sum(function ($act) {
                                    return $act->volume_kerja ??
                                        $act->frekuensi_per_tahun * $act->durasi_menit * $act->jumlah_sdm;
                                });

                                // Hitung Status Approval
                                $statusCounts = $activities->groupBy('status_approval');
                                $approvedCount = $statusCounts->has('approved') ? $statusCounts->get('approved')->count() : 0;
                                $pendingCount = $statusCounts->has('pending') ? $statusCounts->get('pending')->count() : 0;
                                $rejectedCount = $statusCounts->has('rejected') ? $statusCounts->get('rejected')->count() : 0;
                            @endphp

                            {{-- ✅ PERUBAHAN: Baris menjadi Clickable yang mengarah ke Detail (Show) --}}
                            <tr class="hover:bg-blue-50 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('superadmin.master-skp-tendik.show', $userId) }}'">
                                <td class="text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="font-bold text-gray-900 uppercase">
                                    {{ $user->profile->nama_lengkap ?? ($user->name ?? '-') }}</td>
                                <td class="text-center font-semibold text-gray-600">{{ $user->profile->nip ?? '-' }}</td>
                                <td class="text-center font-bold text-blue-800 leading-snug">{{ $semuaJabatan }}</td>
                                <td class="text-center font-bold text-emerald-600">
                                    <span
                                        class="bg-emerald-50 px-2 py-1 rounded border border-emerald-200">{{ $totalTugas }}</span>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="flex flex-wrap gap-1 justify-center">
                                        @if($approvedCount > 0)
                                            <span class="bg-green-100 text-green-700 px-1.5 py-0.5 rounded text-[9px] font-black border border-green-200 uppercase">
                                                {{ $approvedCount }} Approved
                                            </span>
                                        @endif
                                        @if($pendingCount > 0)
                                            <span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-[9px] font-black border border-amber-200 uppercase">
                                                {{ $pendingCount }} Pending
                                            </span>
                                        @endif
                                        @if($rejectedCount > 0)
                                            <span class="bg-red-100 text-red-700 px-1.5 py-0.5 rounded text-[9px] font-black border border-red-200 uppercase">
                                                {{ $rejectedCount }} Rejected
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right font-black text-indigo-600 bg-gray-50 pr-4">
                                    {{ number_format($totalVolume, 0, ',', '.') }}
                                </td>
                                <td class="text-center align-middle p-2">
                                    <div class="flex flex-col gap-1.5">
                                        @if($approvedCount == 0)
                                            {{-- ✅ PERUBAHAN: Tombol Edit & Hapus hanya jika belum ada yg di approve --}}
                                            <a href="{{ route('superadmin.master-skp-tendik.edit', $userId) }}"
                                                onclick="event.stopPropagation();"
                                                class="inline-flex items-center justify-center bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition px-3 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider w-full shadow-sm">
                                                <i class="fas fa-edit mr-1.5"></i> Edit
                                            </a>

                                            <form action="{{ route('superadmin.master-skp-tendik.destroy', $userId) }}"
                                                method="POST" onclick="event.stopPropagation();"
                                                onsubmit="return confirm('Peringatan: Tindakan ini akan menghapus SELURUH daftar kegiatan tupoksi untuk pegawai ini. Lanjutkan?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition px-3 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider w-full border border-red-100 shadow-sm">
                                                    <i class="fas fa-trash-alt mr-1.5"></i> Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[9px] font-bold text-gray-400 uppercase italic">Terkunci (Approved)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <p class="text-gray-400 font-bold italic text-sm">Belum ada pegawai yang dikonfigurasi daftar kegiatan tupoksinya.</p>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endsection
</x-app-layout>
