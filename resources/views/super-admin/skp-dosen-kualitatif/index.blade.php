<x-app-layout>
    @section('page-title', 'SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6">

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Daftar Target SKP Dosen Kualitatif</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.acuan') }}"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-book mr-2"></i> Acuan Skp
                    </a>
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.rumus') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-calculator mr-2"></i> Rumus Hasil
                    </a>
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Target Kualitatif
                    </a>
                </div>
            </div>

            {{-- NOTIFIKASI --}}
            @if (session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-3 text-sm text-green-700 font-bold shadow-sm">
                    {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 text-sm text-red-700 font-bold shadow-sm">
                    {{ session('error') }}</div>
            @endif

            {{-- FILTER PERIODE --}}
            <div class="mb-6 bg-white p-4 border border-gray-200 rounded-xl shadow-sm">
                <form method="GET" action="{{ route('superadmin.skp-dosen-kualitatif.index') }}"
                    class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filter Periode Aktif</label>
                        <select name="periode_id" onchange="this.form.submit()"
                            class="border border-gray-300 px-3 py-2 rounded-lg text-sm focus:ring-blue-500 font-semibold text-blue-800 bg-blue-50">
                            <option value="">-- Semua Periode Aktif --</option>
                            @foreach ($periode as $p)
                                @php $isNonSelectable = in_array(strtolower($p->status), ['draft', 'selesai']); @endphp
                                <option value="{{ $p->id }}"
                                    {{ request('periode_id') == $p->id ? 'selected' : '' }}
                                    {{ $isNonSelectable ? 'disabled' : '' }}
                                    class="{{ $isNonSelectable ? 'text-gray-400 bg-gray-100 italic' : '' }}">
                                    {{ $p->nama_periode }}
                                    ({{ strtoupper($p->status) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- TABEL DATA --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-gray-900 text-left">
                        <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-xs border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-4 text-center w-12">NO</th>
                                <th class="px-4 py-4">NAMA DOSEN</th>
                                <th class="px-4 py-4">PENILAI</th>
                                <th class="px-4 py-4 text-center">STATUS PENGAJUAN</th>
                                <th class="px-4 py-4 text-center w-36">AKSI</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($skpKualitatifs as $skp)
                                <tr data-turbo-row="{{ route('superadmin.skp-dosen-kualitatif.show', $skp->id) }}" class="hover:bg-emerald-50/50 transition-colors group cursor-pointer">
                                    <td class="px-4 py-4 text-center text-gray-500 align-top" onclick="event.stopPropagation()">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-bold text-gray-900">
                                            {{ $skp->skpHeader->user->name ?? '-' }}
                                        </p>
                                        <p class="text-[10px] text-blue-600 font-bold uppercase mt-1">
                                            Periode: {{ $skp->skpHeader->periode->semester ?? '' }} {{ $skp->skpHeader->periode->tahun ?? '' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-bold text-gray-900">
                                            {{ $skp->skpHeader->pejabatPenilai->name ?? '-' }}
                                        </p>
                                        @if($skp->skpHeader->pejabatPenilai)
                                            <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">
                                                {{ $skp->skpHeader->pejabatPenilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center align-top">
                                        @php
                                            $status = strtolower($skp->skpHeader->status ?? 'draft');
                                            $statusClass = 'bg-gray-100 text-gray-800 border-gray-300';
                                            if ($status === 'diajukan') $statusClass = 'bg-blue-100 text-blue-800 border-blue-300';
                                            elseif ($status === 'disetujui') $statusClass = 'bg-green-100 text-green-800 border-green-300';
                                            elseif ($status === 'revisi') $statusClass = 'bg-orange-100 text-orange-800 border-orange-300';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusClass }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center align-top" onclick="event.stopPropagation()">
                                        <div class="flex flex-col gap-2 justify-center">
                                            <a href="{{ route('superadmin.skp-dosen-kualitatif.edit', $skp->id) }}"
                                                class="bg-amber-500 hover:bg-amber-600 text-black border-amber-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition whitespace-nowrap">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>

                                            <form action="{{ route('superadmin.skp-dosen-kualitatif.destroy', $skp->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Peringatan: Yakin ingin menghapus data target SKP Dosen Kualitatif ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-full border border-red-500 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 font-medium bg-gray-50/50">
                                        Belum ada target SKP Dosen Kualitatif yang ditambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endsection
</x-app-layout>
