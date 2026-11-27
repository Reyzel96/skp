<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Periode SKP</h1>
                </div>
                <a href="{{ route('superadmin.periode-skp.create') }}"
                    class="inline-flex items-center bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Periode Baru
                </a>
            </div>

            {{--
           HAPUS BLOK ALERT MANUAL DI SINI.
           Kita asumsikan <x-app-layout> sudah menghandle session('success')
        --}}

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Tahun</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Detail Semester</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Masa Penilaian</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Batas Akhir (Deadline)</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($periodeSkp as $periode)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-lg font-bold text-blue-600">{{ $periode->tahun }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col space-y-1">
                                            <div class="text-sm font-bold text-gray-800">
                                                {{ $periode->nama_periode }}
                                            </div>
                                            <div>
                                                @if($periode->tipe == 'tahunan')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Tahunan</span>
                                                @elseif($periode->tipe == 'semester')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Semester</span>
                                                @elseif($periode->tipe == 'bulanan')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-700">Bulanan (Triwulan)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="flex items-center italic">
                                            {{ $periode->mulai->format('d/m/Y') }} —
                                            {{ $periode->selesai->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex flex-col space-y-1">
                                            <div class="text-gray-700">
                                                <span
                                                    class="font-semibold text-xs uppercase text-gray-400">Pengisian:</span>
                                                {!! $periode->batas_pengisian
                                                    ? $periode->batas_pengisian->format('d M Y')
                                                    : '<span class="italic text-gray-400">Tidak diset</span>' !!}
                                            </div>
                                            <div class="text-gray-700">
                                                <span
                                                    class="font-semibold text-xs uppercase text-gray-400">Penilaian:</span>
                                                {!! $periode->batas_penilaian
                                                    ? $periode->batas_penilaian->format('d M Y')
                                                    : '<span class="italic text-gray-400">Tidak diset</span>' !!}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- KOLOM STATUS DENGAN FORM DROPDOWN (TETAP ADA) --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form action="{{ route('superadmin.periode-skp.update-status', $periode->id) }}"
                                            method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')

                                            @php
                                                $bgColors = [
                                                    'aktif' =>
                                                        'bg-green-100 text-green-800 border-green-300 focus:ring-green-500',
                                                    'draft' =>
                                                        'bg-amber-100 text-amber-800 border-amber-300 focus:ring-amber-500',
                                                    'selesai' =>
                                                        'bg-gray-100 text-gray-800 border-gray-300 focus:ring-gray-500',
                                                ];
                                            @endphp

                                            <select name="status" onchange="this.form.submit()"
                                                class="text-xs font-bold uppercase rounded-full border px-3 py-1 cursor-pointer focus:ring-2 outline-none transition-colors {{ $bgColors[$periode->status] ?? $bgColors['draft'] }}">
                                                <option value="draft" {{ $periode->status == 'draft' ? 'selected' : '' }}
                                                    class="bg-white text-gray-800">DRAFT</option>
                                                <option value="aktif" {{ $periode->status == 'aktif' ? 'selected' : '' }}
                                                    class="bg-white text-gray-800">AKTIF</option>
                                                <option value="selesai"
                                                    {{ $periode->status == 'selesai' ? 'selected' : '' }}
                                                    class="bg-white text-gray-800">SELESAI</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center space-x-2">
                                            <a href="{{ route('superadmin.periode-skp.edit', $periode->id) }}"
                                                class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                title="Edit Periode">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                            @php
                                                $dataTerkait = $periode->jumlahDataTerkait();
                                                $adaData = collect($dataTerkait)->contains(fn ($n) => $n > 0);
                                                $ketHapus = collect($dataTerkait)->filter(fn ($n) => $n > 0)
                                                    ->map(fn ($n, $j) => "{$n} {$j}")->implode(', ');
                                            @endphp
                                            <form action="{{ route('superadmin.periode-skp.destroy', $periode->id) }}"
                                                method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 rounded-lg transition-colors {{ $adaData ? 'text-gray-300 cursor-not-allowed' : 'text-red-600 hover:bg-red-50' }}"
                                                    {{ $adaData ? 'disabled' : '' }}
                                                    onclick="{{ $adaData ? 'return false;' : "return confirm('Apakah Anda yakin ingin menghapus periode ini?')" }}"
                                                    title="{{ $adaData ? ('Tidak bisa dihapus: dipakai ' . $ketHapus) : 'Hapus Periode' }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                                </path>
                                            </svg>
                                            <p class="text-gray-500 font-medium">Belum ada data periode SKP.</p>
                                            <p class="text-gray-400 text-sm">Klik tombol "Tambah Periode Baru" untuk
                                                memulai.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SCRIPT PENGHILANG ALERT OTOMATIS (MENCARI ALERT BAWAAN LAYOUT) --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mencari elemen alert (sesuaikan class-nya jika layout Anda menggunakan class alert spesifik)
                // Ini akan menarget div yang memiliki text "success" atau warna hijau
                const alerts = document.querySelectorAll(
                '.bg-green-100, .bg-green-50, .text-green-800, [role="alert"]');

                alerts.forEach(function(alert) {
                    // Pastikan elemen tersebut memang sebuah kotak alert, bukan sekadar teks hijau biasa
                    if (alert.tagName === 'DIV' && alert.innerText.trim().length > 0) {
                        alert.style.transition = "opacity 0.5s ease-out";

                        // Hilangkan setelah 3.5 detik
                        setTimeout(function() {
                            alert.style.opacity = '0';
                            setTimeout(() => alert.remove(), 500);
                        }, 3500);
                    }
                });
            });
        </script>
    @endsection
</x-app-layout>
