<x-app-layout>
    @section('page-title', 'Detail Tupoksi Pegawai')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            .excel-table th {
                background-color: #f8fafc;
                border: 1px solid #cbd5e1 !important;
                font-size: 10px;
                text-transform: uppercase;
                font-weight: 800;
                padding: 8px 4px !important;
                text-align: center;
                vertical-align: middle;
                color: #475569;
                letter-spacing: 0.05em;
            }

            .excel-table td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 6px !important;
                font-size: 10px;
                vertical-align: top;
                word-wrap: break-word;
                line-height: 1.4;
            }

            .v-check {
                color: #059669;
                font-weight: 900;
                text-align: center;
                vertical-align: middle !important;
            }
        </style>

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 w-full">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tighter">Detail Rincian Tugas</h1>
                    <p class="text-gray-500 text-sm">Rincian seluruh aktivitas tupoksi untuk pegawai bersangkutan.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                    <a href="{{ route('superadmin.master-skp-tendik.edit', $user->id) }}"
                        class="w-full md:w-auto text-center inline-flex items-center justify-center bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg text-sm">
                        <i class="fas fa-plus mr-2"></i> Tambah Tupoksi
                    </a>
                    <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                        class="w-full md:w-auto text-center inline-flex items-center justify-center bg-gray-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-gray-700 transition-all shadow-lg text-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            {{-- TABEL IDENTITAS TENDIK (Full-Width) --}}
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-6">
                <div class="bg-white">

                    {{-- Header --}}
                    <div class="grid grid-cols-[40px_1fr] border-b border-gray-800 font-bold bg-blue-50 uppercase">
                        <div class="p-2 text-center border-r border-gray-800">
                            <i class="fas fa-user text-blue-800"></i>
                        </div>
                        <div class="p-2 text-blue-800 text-center">
                            Informasi Pegawai
                        </div>
                    </div>

                    {{-- 1. Nama --}}
                    <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                        <div class="p-2 text-center border-r border-gray-400">1</div>
                        <div class="p-2">Nama Pegawai</div>
                        <div class="p-2 font-semibold uppercase">
                            {{ $user->profile->nama_lengkap ?? $user->name }}
                        </div>
                    </div>

                    {{-- 2. NIP --}}
                    <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                        <div class="p-2 text-center border-r border-gray-400">2</div>
                        <div class="p-2">Nomor Induk (NIP)</div>
                        <div class="p-2 font-semibold uppercase">
                            {{ $user->profile->nip ?? '-' }}
                        </div>
                    </div>

                    {{-- 3. Pangkat --}}
                    <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                        <div class="p-2 text-center border-r border-gray-400">3</div>
                        <div class="p-2">Pangkat / Golongan</div>
                        <div class="p-2 font-semibold uppercase">
                            {{ $user->profile->pangkat_golongan ?? '-' }}
                        </div>
                    </div>

                    {{-- 4. Jabatan --}}
                    @php
                        $roles = $user->getRoleNames()->implode(', ');
                        $fungsional = $user->profile->jabatan_fungsional ?? null;
                        $displayJabatan = $fungsional ? "$roles / $fungsional" : $roles;
                    @endphp
                    <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                        <div class="p-2 text-center border-r border-gray-400">4</div>
                        <div class="p-2">Jabatan / Bidang</div>
                        <div class="p-2 font-semibold uppercase">
                            {{ $displayJabatan ?: '-' }}
                        </div>
                    </div>

                    {{-- 5. Unit Kerja --}}
                    <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                        <div class="p-2 text-center border-r border-gray-400">5</div>
                        <div class="p-2">Unit Kerja</div>
                        <div class="p-2 font-semibold uppercase">
                            {{ $user->profile->unit_kerja ?? 'UNIVERSITAS MUHAMMADIYAH SURABAYA' }}
                        </div>
                    </div>

                </div>
            </div>

            {{-- TABEL RINCIAN TUGAS --}}
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                <table class="w-full table-fixed border-collapse excel-table bg-white">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 3%;">No</th>
                            <th rowspan="2" style="width: 10%;">Bidang</th>
                            <th rowspan="2" style="width: 18%;">Tupoksi</th>
                            <th rowspan="2" style="width: 16%;">Jenis Aktivitas</th>
                            <th rowspan="2" style="width: 8%;">Penilai</th>
                            <th rowspan="2" style="width: 10%;">Status</th>
                            <th colspan="3" style="width: 12%;" class="border-b border-slate-300">Kategori</th>
                            <th rowspan="2" style="width: 6%;">Freq / Thn</th>
                            <th rowspan="2" style="width: 6%;">Dur / Mnt</th>
                            <th rowspan="2" style="width: 4%;">SDM</th>
                            <th rowspan="2" style="width: 7%;">Total Vol</th>
                        </tr>
                        <tr>
                            <th style="width: 4%;" class="text-[8px] px-0">Utm</th>
                            <th style="width: 4%;" class="text-[8px] px-0">Prd</th>
                            <th style="width: 4%;" class="text-[8px] px-0">Bkn</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php $grandTotalVolume = 0; @endphp
                        @foreach ($activities as $index => $item)
                            @php
                                $volume =
                                    $item->volume_kerja ??
                                    $item->frekuensi_per_tahun * $item->durasi_menit * $item->jumlah_sdm;
                                $grandTotalVolume += $volume;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="text-center font-bold text-gray-400 align-middle">{{ $index + 1 }}</td>
                                <td class="font-bold text-blue-800 uppercase">{{ $item->role_name }}</td>
                                <td class="font-semibold text-gray-800">{{ $item->tupoksi }}</td>
                                <td class="text-gray-600 italic">{{ $item->jenis_aktivitas }}</td>
                                <td class="text-center font-bold text-emerald-700 bg-emerald-50/30 align-middle">
                                    {{ $item->penilai_role ?? '-' }}
                                </td>
                                <td class="text-center align-middle">
                                    @if($item->status_approval === 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-green-200">
                                            Approved
                                        </span>
                                    @elseif($item->status_approval === 'rejected')
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-red-200">
                                                Rejected
                                            </span>
                                            @if($item->catatan_approval)
                                                <p class="text-[8px] text-red-500 italic leading-tight">"{{ $item->catatan_approval }}"</p>
                                            @endif
                                        </div>
                                    @else
                                        <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[9px] font-black uppercase border border-amber-200">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                {{-- Kategori Checks --}}
                                <td class="v-check">
                                    {{ is_array($item->kategori) && in_array('Utama', $item->kategori) ? 'V' : '' }}</td>
                                <td class="v-check">
                                    {{ is_array($item->kategori) && in_array('Periodik', $item->kategori) ? 'V' : '' }}
                                </td>
                                <td class="v-check">
                                    {{ is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori) ? 'V' : '' }}
                                </td>

                                <td class="text-center align-middle font-medium">{{ $item->frekuensi_per_tahun }}</td>
                                <td class="text-center align-middle font-medium">{{ $item->durasi_menit }}</td>
                                <td class="text-center align-middle font-medium">{{ $item->jumlah_sdm }}</td>

                                <td class="text-right align-middle font-black text-indigo-700 bg-indigo-50/30 pr-2">
                                    {{ number_format($volume, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 border-t-2 border-gray-400">
                            <td colspan="12" class="text-right font-black text-gray-800 uppercase pr-4 py-3 text-xs">Grand
                                Total Volume Kinerja</td>
                            <td class="text-right font-black text-indigo-800 pr-2 py-3 text-xs">
                                {{ number_format($grandTotalVolume, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    @endsection
</x-app-layout>
