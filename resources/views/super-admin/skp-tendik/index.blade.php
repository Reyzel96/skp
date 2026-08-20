<x-app-layout>
    @section('page-title', 'Database SKP Tendik')

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
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">Database SKP Tendik</h1>
                </div>
                <a href="{{ route('superadmin.master-skp-tendik.create') }}"
                    class="w-full md:w-auto text-center inline-flex items-center justify-center bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg text-sm">
                    <i class="fas fa-plus mr-2"></i> Tambah Kegiatan Pegawai Baru
                </a>
            </div>

            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
                <form id="filterForm" action="{{ route('superadmin.master-skp-tendik.index') }}" method="GET"
                    class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:max-w-3xl">
                    <div class="flex-1 flex flex-col sm:flex-row gap-2 w-full">
                        <select name="periode_id" onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 text-sm font-bold text-emerald-700 bg-emerald-50 px-3 py-2">
                            <option value="">-- Semua Periode --</option>
                            @foreach ($periodes as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nama_periode }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if (request('periode_id'))
                        <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                            class="text-red-600 font-bold text-xs uppercase hover:underline whitespace-nowrap">Reset</a>
                    @endif
                </form>

                <div class="text-left sm:text-right w-full sm:w-auto">
                    <span
                        class="text-[10px] font-bold text-gray-400 uppercase bg-gray-100 px-3 py-1 rounded-md border border-gray-200">
                        Total: {{ $headers->count() }} Record SKP
                    </span>
                </div>
            </div>

            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                <table class="w-full table-fixed border-collapse excel-table bg-white">
                    <thead>
                        <tr>
                            <th style="width: 4%;">No</th>
                            <th style="width: 18%;">Nama Pegawai</th>
                            <th style="width: 12%;">Periode</th>
                            <th style="width: 18%;">Jabatan</th>
                            <th style="width: 8%;">Tugas</th>
                            <th style="width: 15%;">Status Target</th>
                            <th style="width: 15%;">Approval Atasan/Penilai</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($headers as $header)
                            @php
                                $user = $header->user;
                                $activities = $header->masterActivities;
                                $totalTugas = $activities->count();
                                $semuaJabatan = $activities->pluck('role_name')->unique()->implode(', ') ?: ($user->getRoleNames()->first() ?? '-');
                                
                                // Status Target
                                $statusColor = match($header->status) {
                                    'Disetujui' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'Revisi' => 'bg-red-100 text-red-700 border-red-200',
                                    'Pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp

                            <tr class="hover:bg-blue-50 transition-colors cursor-pointer"
                                onclick="window.location='{{ route('superadmin.master-skp-tendik.show', $header->id) }}'">
                                <td class="text-center font-bold text-gray-400">{{ $loop->iteration }}</td>
                                <td class="font-bold text-gray-900 uppercase">
                                    {{ $user->profile->nama_lengkap ?? ($user->name ?? '-') }}</td>
                                <td class="text-center">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200 font-bold text-[10px]">
                                        {{ $header->periode->nama_periode ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center font-bold text-blue-800 leading-snug">{{ $semuaJabatan }}</td>
                                <td class="text-center font-bold text-emerald-600">
                                    <span class="bg-emerald-50 px-2 py-1 rounded border border-emerald-200">{{ $totalTugas }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="{{ $statusColor }} px-2 py-1 rounded border font-black text-[9px] uppercase">
                                        {{ $header->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex flex-col gap-1 items-center">
                                        @if($header->is_target_approved_penilai)
                                            <span class="text-[8px] bg-green-50 text-green-600 border border-green-200 px-1 rounded font-bold uppercase">
                                                Penilai: OK
                                            </span>
                                        @else
                                            <span class="text-[8px] bg-orange-50 text-orange-600 border border-orange-200 px-1 rounded font-bold uppercase">
                                                Penilai: Waiting
                                            </span>
                                        @endif

                                        @if($header->is_target_approved_atasan)
                                            <span class="text-[8px] bg-green-50 text-green-600 border border-green-200 px-1 rounded font-bold uppercase">
                                                Atasan: OK
                                            </span>
                                        @else
                                            <span class="text-[8px] bg-orange-50 text-orange-600 border border-orange-200 px-1 rounded font-bold uppercase">
                                                Atasan: Waiting
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center align-middle p-2">
                                    <div class="flex flex-col gap-1.5">
                                        @if(in_array($header->status, ['Disetujui', 'Selesai']))
                                            <span class="text-[9px] font-bold text-gray-400 uppercase italic">Terkunci (Approved)</span>
                                        @else
                                            <a href="{{ route('superadmin.master-skp-tendik.edit', $header->id) }}"
                                                onclick="event.stopPropagation();"
                                                class="inline-flex items-center justify-center bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition px-3 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider w-full shadow-sm">
                                                <i class="fas fa-edit mr-1.5"></i> Edit
                                            </a>

                                            <form action="{{ route('superadmin.master-skp-tendik.destroy', $header->id) }}"
                                                method="POST" onclick="event.stopPropagation();"
                                                onsubmit="return confirm('Peringatan: Tindakan ini akan menghapus SKP ini. Lanjutkan?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition px-3 py-1.5 rounded-md text-[10px] font-black uppercase tracking-wider w-full border border-red-100 shadow-sm">
                                                    <i class="fas fa-trash-alt mr-1.5"></i> Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10">
                                    <p class="text-gray-400 font-bold italic text-sm">Belum ada data SKP yang dikonfigurasi.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endsection
</x-app-layout>
