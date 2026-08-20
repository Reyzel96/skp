<x-app-layout>
    @section('page-title', 'Daftar Tupoksi Per Jabatan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="w-full p-4 font-sans">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">Daftar Tupoksi Per Jabatan</h2>
            <a href="{{ route('superadmin.daftar-tupoksi.create') }}"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:bg-blue-700 transition text-sm font-bold flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah Tupoksi
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
            <form method="GET" action="{{ route('superadmin.daftar-tupoksi.index') }}" class="flex flex-col sm:flex-row gap-3">
                <select name="bidang" class="w-full sm:w-1/3 px-4 py-2 border rounded-lg focus:ring-blue-500 text-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Jabatan --</option>
                    @php
                        $groupedRoles = [];
                        $rektorRoles = [];

                        foreach ($roles as $role) {
                            $name = $role->name;
                            if (str_contains(strtolower($name), 'rektor')) {
                                $rektorRoles[] = $role;
                                continue;
                            }

                            if (preg_match('/(D3|D4|S1|S2)\s+(.+)/i', $name, $matches)) {
                                $prodi = strtoupper($matches[1]) . ' ' . ucwords(strtolower($matches[2]));
                                $groupedRoles[$prodi][] = $role;
                            } else {
                                $kategoriLabel = ucfirst($role->kategori);
                                $groupedRoles["Umum / $kategoriLabel"][] = $role;
                            }
                        }
                    @endphp

                    @if(count($rektorRoles) > 0)
                        <optgroup label="Rektorat">
                            @foreach($rektorRoles as $role)
                                <option value="{{ $role->name }}" {{ request('bidang') == $role->name ? 'selected' : '' }}>{{ ucwords(strtolower($role->name)) }}</option>
                            @endforeach
                        </optgroup>
                    @endif

                    @foreach($groupedRoles as $groupName => $group)
                        <optgroup label="{{ $groupName }}">
                            @foreach($group as $role)
                                <option value="{{ $role->name }}" {{ request('bidang') == $role->name ? 'selected' : '' }}>{{ ucwords(strtolower($role->name)) }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @if(request('bidang'))
                    <a href="{{ route('superadmin.daftar-tupoksi.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-bold flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i> Reset Filter
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600 uppercase tracking-wider">
                            <th class="p-4 w-12 text-center">No</th>
                            <th class="p-4">Jabatan</th>
                            <th class="p-4 text-center">Jumlah Tupoksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($daftarTupoksi as $idx => $item)
                        <tr class="border-b border-gray-100 hover:bg-blue-50 transition cursor-pointer"
                            onclick="window.location='{{ route('superadmin.daftar-tupoksi.show-detail', $item->bidang) }}'">
                            <td class="p-4 text-center font-semibold text-gray-500">{{ $idx + 1 }}</td>
                            <td class="p-4 font-bold text-indigo-700 uppercase">
                                {{ $item->bidang }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">
                                    {{ $item->jumlah_tupoksi }} Tupoksi
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="p-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                <p>Belum ada daftar tupoksi. Silakan tambahkan kamus tupoksi baru.</p>
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
