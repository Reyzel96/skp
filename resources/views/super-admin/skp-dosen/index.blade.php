<x-app-layout>
    @section('page-title', 'SKP Dosen')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6">

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Daftar Target SKP Dosen</h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('superadmin.skp-dosen.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-plus mr-2"></i> Tambah Target Dosen
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
                <form method="GET" action="{{ route('superadmin.skp-dosen.index') }}"
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
                                    @if($p->tipe == 'tahunan')
                                        Tahun {{ $p->tahun }} : {{ $p->semester_genap }} / {{ $p->semester_gasal }}
                                    @elseif($p->tipe == 'semester')
                                        Tahun {{ $p->tahun }} : Semester {{ ucfirst($p->semester) }}
                                    @elseif($p->tipe == 'bulanan')
                                        Tahun {{ $p->tahun }} : Bulan {{ \Carbon\Carbon::create()->month($p->bulan)->translatedFormat('F') }}{{ $p->bulan_akhir ? ' — ' . \Carbon\Carbon::create()->month($p->bulan_akhir)->translatedFormat('F') : '' }}
                                    @else
                                        Tahun {{ $p->tahun }}
                                    @endif
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
                                <th class="px-4 py-4 w-1/4">NAMA DOSEN</th>
                                <th class="px-4 py-4">PEJABAT PENILAI (Atasan)</th>
                                <th class="px-4 py-4 text-center">STATUS PENGAJUAN</th>
                                <th class="px-4 py-4 text-center w-36">AKSI</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($headers as $header)
                                @php
                                    $user = $header->user;
                                    $penilaiNama =
                                        $header->pejabatPenilai->profile->nama_lengkap ??
                                        ($header->pejabatPenilai->name ?? null);

                                    $statusColor = 'bg-gray-100 text-gray-600 border-gray-300';
                                    if ($header->status == 'Diajukan') {
                                        $statusColor = 'bg-yellow-100 text-yellow-800 border-yellow-300';
                                    } elseif ($header->status == 'Disetujui') {
                                        $statusColor = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                    } elseif ($header->status == 'Pending Utama') {
                                        $statusColor = 'bg-blue-100 text-blue-800 border-blue-300';
                                    } elseif ($header->status == 'Pending Akhir') {
                                        $statusColor = 'bg-indigo-100 text-indigo-800 border-indigo-300';
                                    }
                                @endphp

                                {{-- ✅ Baris dapat diklik untuk ke halaman Show --}}
                                <tr class="hover:bg-emerald-50/50 transition-colors group cursor-pointer"
                                    onclick="window.location='{{ route('superadmin.skp-dosen.show', $header->id) }}'">
                                    <td class="px-4 py-4 text-center text-gray-500 align-top">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-bold text-gray-900">
                                            {{ $user->profile->nama_lengkap ?? ($user->name ?? 'User Unknown') }}
                                        </p>
                                        <p class="text-xs text-gray-500">NIP: {{ $user->profile->nip ?? '-' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1 font-semibold">{{ $header->skpDosens->count() }}
                                            Kegiatan Target</p>
                                        <p class="text-[10px] text-blue-600 font-bold uppercase mt-1">
                                            Periode: {{ $header->periode->nama_periode ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        @if ($header->pejabatPenilai)
                                            <p class="font-semibold text-sm text-gray-800">{{ $penilaiNama }}</p>
                                            <p class="text-[10px] text-green-600 font-bold uppercase tracking-tighter">Sudah
                                                Ditetapkan</p>
                                        @else
                                            <p class="text-sm text-red-400 italic font-medium">Belum dipilih</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center align-top">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusColor }}">
                                            {{ $header->status ?? 'Draft' }}
                                        </span>
                                        @if ($header->status == 'Draft' && $header->catatan_penilai)
                                            <div
                                                class="mt-2 text-left bg-red-50 p-2 rounded border border-red-100 text-[10px] text-red-600">
                                                <b>Ditolak Atasan:</b> "{{ $header->catatan_penilai }}"
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center align-top">
                                        <div class="flex flex-col gap-2 justify-center">
                                            @if($header->status !== 'Disetujui')
                                                {{-- ✅ Tombol Edit hanya jika belum Disetujui --}}
                                                <a href="{{ route('superadmin.skp-dosen.edit', $header->id) }}"
                                                    onclick="event.stopPropagation();"
                                                    class="bg-amber-500 hover:bg-amber-600 text-black border-amber-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition whitespace-nowrap">
                                                    <i class="fas fa-edit mr-1"></i> Edit Target
                                                </a>
                                            @else
                                                <span class="text-[9px] font-bold text-gray-400 uppercase italic">Terkunci (Approved)</span>
                                            @endif

                                            <form action="{{ route('superadmin.skp-dosen.destroyAll', $header->id) }}"
                                                method="POST" onclick="event.stopPropagation();"
                                                onsubmit="return confirm('Peringatan: Yakin ingin menghapus data target SKP Dosen ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-full border border-red-500 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                                    <i class="fas fa-trash-alt mr-1"></i> Hapus Data
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-12 text-center text-gray-500 font-medium bg-gray-50/50">Belum ada
                                        target SKP Dosen yang ditambahkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endsection
</x-app-layout>
