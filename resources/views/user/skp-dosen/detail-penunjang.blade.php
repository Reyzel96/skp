<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Unsur Penunjang')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-4xl mx-auto p-6" x-data="{
        rows: {{ $daftarKegiatan->count() > 0 ? $daftarKegiatan->count() : 1 }},
        init() { rowCounter = this.rows }
    }">
        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Unsur Penunjang</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold hover:underline text-sm">
                &larr; Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold" role="alert">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        @if($isFormLocked)
            <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm flex items-center gap-3">
                <i class="fas fa-lock text-amber-500"></i>
                <p class="text-amber-800 text-sm font-bold uppercase">Form Terkunci: SKP sedang dalam proses review atau sudah selesai.</p>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.penunjang.store') }}" method="POST">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <table class="w-full mb-4 border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-blue-600 text-white border-b">
                        <tr class="text-xs font-black uppercase tracking-widest text-center">
                            <th class="p-3 text-left pl-4 w-2/3">Nama Kegiatan Penunjang</th>
                            <th class="p-3 w-32 border-l border-white/20">Angka Kredit</th>
                            <th class="p-3 w-16 border-l border-white/20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="inputBody" class="divide-y divide-gray-100">
                        {{-- Data History Tersimpan --}}
                        @forelse($daftarKegiatan as $index => $item)
                        <tr class="hover:bg-gray-50 transition bg-gray-50/30">
                            <td class="p-2 border-r border-gray-200 pl-4">
                                <input type="text" value="{{ $item->nama_kegiatan }}" class="w-full border-gray-300 rounded-lg text-sm bg-gray-200 cursor-not-allowed font-bold text-gray-600" readonly>
                            </td>
                            <td class="p-2 border-r border-gray-200">
                                <input type="number" step="0.01" value="{{ $item->ak }}" class="w-full text-center border-gray-300 rounded-lg text-sm bg-gray-200 cursor-not-allowed font-bold text-gray-600" readonly>
                            </td>
                            <td class="p-2 text-center">
                                @if(!$isFormLocked)
                                    <button type="button" onclick="deleteItem('{{ route('pegawai.skp-dosen.penunjang.destroy', $item->id) }}')" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors" title="Hapus Data"><i class="fas fa-trash"></i></button>
                                @else
                                    <i class="fas fa-lock text-gray-300"></i>
                                @endif
                            </td>
                        </tr>
                        @empty
                            @if(!$isFormLocked)
                                <tr class="input-row hover:bg-gray-50 border-b border-gray-100">
                                    <td class="p-2 border-r border-gray-200 pl-4">
                                        <input type="text" name="items[0][nama_kegiatan]" class="w-full border-gray-300 rounded-lg text-sm font-bold text-gray-800 focus:ring-blue-500" placeholder="Contoh: Anggota Panitia Ad-Hoc" required>
                                    </td>
                                    <td class="p-2 border-r border-gray-200">
                                        <input type="number" step="0.01" name="items[0][ak]" class="w-full text-center border-gray-300 rounded-lg text-sm font-bold focus:ring-blue-500" placeholder="0.00">
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" class="remove-row text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-1.5 rounded-lg transition-colors"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-gray-400 italic">Belum ada kegiatan penunjang.</td>
                                </tr>
                            @endif
                        @endforelse
                    </tbody>
                </table>

                @if(!$isFormLocked)
                    <div class="flex justify-between items-center mt-6">
                        <button type="button" id="addRow" class="text-blue-600 font-bold text-sm bg-blue-50 px-4 py-2 rounded-lg hover:bg-blue-100 transition border border-blue-200">
                            <i class="fas fa-plus mr-1"></i> Tambah Kegiatan Baru
                        </button>
                        <div class="flex gap-3">
                            <button type="button" onclick="if(confirm('Reset semua data penunjang?')) document.getElementById('reset-penunjang-form').submit();" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-6 py-3 rounded-xl font-bold transition text-sm">
                                Reset Data Keseluruhan
                            </button>
                            <button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition">
                                💾 Simpan & Kembali
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </form>

<form id="reset-penunjang-form" action="{{ route('pegawai.skp-dosen.penunjang.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id }}">
    <input type="hidden" name="reset" value="true">
</form>

        {{-- Form Delete Individual Row --}}
        <form id="deleteForm" method="POST" style="display:none">
            @csrf @method('DELETE')
        </form>
    </div>
    @endsection

    @push('scripts')
    <script>
        let rowCounter = {{ $daftarKegiatan->count() > 0 ? $daftarKegiatan->count() : 1 }};

        document.getElementById('addRow').addEventListener('click', function() {
            const body = document.getElementById('inputBody');
            const unique = Date.now() + rowCounter;

            const row = `
                <tr class="input-row hover:bg-gray-50 border-b border-gray-100">
                    <td class="p-2 border-r border-gray-200 pl-4">
                        <input type="text" name="items[${unique}][nama_kegiatan]" class="w-full border-gray-300 rounded-lg text-sm font-bold text-gray-800 focus:ring-blue-500" placeholder="Ketik nama kegiatan penunjang baru..." required>
                    </td>
                    <td class="p-2 border-r border-gray-200">
                        <input type="number" step="0.01" name="items[${unique}][ak]" class="w-full text-center border-gray-300 rounded-lg text-sm font-bold focus:ring-blue-500" placeholder="0.00">
                    </td>
                    <td class="p-2 text-center">
                        <button type="button" class="remove-row text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg transition-colors"><i class="fas fa-times"></i></button>
                    </td>
                </tr>`;
            body.insertAdjacentHTML('beforeend', row);
            rowCounter++;
        });

        document.addEventListener('click', e => {
            if(e.target.closest('.remove-row')) {
                e.target.closest('.input-row').remove();
            }
        });

        function deleteItem(url) {
            if(confirm('Hapus baris data kegiatan ini?')) {
                const f = document.getElementById('deleteForm');
                f.action = url;
                f.submit();
            }
        }
    </script>
    @endpush
</x-app-layout>
