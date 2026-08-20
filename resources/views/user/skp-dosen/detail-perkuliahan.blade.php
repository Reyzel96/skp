<x-app-layout>
    @section('page-title', 'Pengerjaan SKP - Perkuliahan')

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
    <div class="max-w-7xl mx-auto p-6">

        @php
            $matkuls = $daftarMatkul ?? collect();
            $dataGasal = $dataGasal ?? $matkuls->where('semester', 'gasal');
            $dataGenap = $dataGenap ?? $matkuls->where('semester', 'genap');
        @endphp

        <div class="mb-6 flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-2xl font-black text-gray-800 uppercase">Input Data Perkuliahan</h2>
                <p class="text-gray-500 text-sm">{{ $targetSkp->kegiatan }}</p>
            </div>
            <a href="{{ route('pegawai.skp-dosen.index') }}" class="text-gray-500 font-bold hover:underline text-sm">
                &larr; Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2 text-lg"></i>
                    <h3 class="text-red-800 font-bold">Gagal Menyimpan Data</h3>
                </div>
                <ul class="list-disc ml-8 text-sm text-red-600">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded relative shadow-sm font-semibold">
                <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i> {{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('pegawai.skp-dosen.perkuliahan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="penilaian_id" value="{{ $penilaian->id ?? '' }}">

            @foreach(['gasal' => 'Semester Gasal', 'genap' => 'Semester Genap'] as $key => $title)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <div class="p-4 {{ $key == 'gasal' ? 'bg-orange-600' : 'bg-blue-600' }} text-white font-black uppercase tracking-widest flex justify-between items-center">
                    <span>{{ $title }}</span>
                    @if(!$isFormLocked)
                    <button type="button" onclick="addRow('{{ $key }}')" class="bg-white/20 hover:bg-white/40 px-3 py-1 rounded-lg text-xs transition shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah Matkul
                    </button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr class="text-[10px] text-gray-500 uppercase font-black tracking-wider text-center">
                                <th class="p-3 text-left pl-4">Mata Kuliah</th>
                                <th class="p-3 w-24">SKS MK</th>
                                <th class="p-3 w-24">Jml Kelas</th>
                                <th class="p-3 w-24">Jml Dosen</th>
                                <th class="p-3 w-64 text-left">Upload Bukti (PDF/JPG)</th>
                                @if(!$isFormLocked)
                                <th class="p-3 w-16">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="inputBody{{ ucfirst($key) }}" class="divide-y border-b border-gray-100">
                            @php $dataSemester = ($key == 'gasal') ? $dataGasal : $dataGenap; @endphp

                            @forelse($dataSemester as $item)
                            <tr class="bg-gray-50/50 hover:bg-gray-100 transition-colors">
                                <td class="p-3 pl-4">
                                    <input type="text" value="{{ $item->nama_mata_kuliah }}" class="w-full border-gray-300 rounded-lg text-xs bg-gray-100 cursor-not-allowed font-bold text-gray-600" readonly>
                                </td>
                                <td class="p-3">
                                    <input type="number" value="{{ $item->sks_mata_kuliah }}" class="w-full text-center border-gray-300 rounded-lg text-xs font-bold bg-gray-100 cursor-not-allowed text-gray-600" readonly>
                                </td>
                                <td class="p-3">
                                    <input type="number" value="{{ $item->jumlah_kelas }}" class="w-full text-center border-gray-300 rounded-lg text-xs font-bold bg-gray-100 cursor-not-allowed text-gray-600" readonly>
                                </td>
                                <td class="p-3">
                                    <input type="number" value="{{ $item->jumlah_dosen_pengampu }}" class="w-full text-center border-gray-300 rounded-lg text-xs font-bold bg-gray-100 cursor-not-allowed text-gray-600" readonly>
                                </td>
                                <td class="p-3 text-xs">
                                    @if($item->bukti_dokumen)
                                        <a href="{{ Storage::url($item->bukti_dokumen) }}" target="_blank" class="text-blue-600 font-bold hover:underline bg-blue-50 px-3 py-1 rounded-full">
                                            <i class="fas fa-file-download mr-1"></i> File Tersimpan
                                        </a>
                                    @else
                                        <span class="text-red-400 italic">Belum ada file</span>
                                    @endif
                                </td>
                                @if(!$isFormLocked)
                                <td class="p-3 text-center">
                                    <button type="button" onclick="deleteItem('{{ route('pegawai.skp-dosen.perkuliahan.destroy', $item->id) }}')" class="text-red-400 hover:text-red-600 transition-colors bg-red-50 hover:bg-red-100 p-2 rounded-lg" title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr id="empty-{{ $key }}">
                                <td colspan="{{ $isFormLocked ? '5' : '6' }}" class="p-6 text-center text-gray-400 italic text-xs font-semibold">
                                    Belum ada mata kuliah. {{ !$isFormLocked ? 'Klik tombol "Tambah Matkul" di pojok kanan atas.' : '' }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            @if(!$isFormLocked)
            <div class="flex justify-between items-center mt-6 gap-4">
                <button type="button" onclick="if(confirm('Hapus/Reset Seluruh Data Perkuliahan?')) document.getElementById('reset-perkuliahan-form').submit();" class="text-red-500 hover:text-white bg-red-50 hover:bg-red-600 px-6 py-3 rounded-xl font-bold transition text-sm">
                    <i class="fas fa-trash-alt mr-2"></i> Reset Data Keseluruhan
                </button>
                <button type="submit" class="bg-emerald-600 text-white px-10 py-3 rounded-xl font-black shadow-lg hover:bg-emerald-700 transition transform hover:-translate-y-1">
                    💾 SIMPAN & KEMBALI
                </button>
            </div>
            @else
            <div class="mt-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-lock text-amber-500 mr-3 text-lg"></i>
                    <p class="text-amber-800 font-bold text-sm">Formulir Terkunci. Anda tidak dapat menambah, menghapus, atau mereset data karena SKP sedang dalam proses review atau telah disetujui.</p>
                </div>
            </div>
            @endif
        </form>
        </form>

        {{-- Form Reset Keseluruhan Skp Induk (Menjadi 0) --}}
<form id="reset-perkuliahan-form" action="{{ route('pegawai.skp-dosen.perkuliahan.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="penilaian_id" value="{{ $penilaian->id ?? '' }}">
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
        let rowCounter = 0;
        function addRow(semester) {
            rowCounter++;
            const id = semester.charAt(0).toUpperCase() + semester.slice(1);
            const tbody = document.getElementById('inputBody' + id);

            // Hapus row kosong
            const emptyRow = document.getElementById('empty-' + semester);
            if (emptyRow) emptyRow.remove();

            const unique = Date.now() + rowCounter;

            const html = `
                <tr class="animate-fadeIn bg-white border-l-4 border-emerald-400 shadow-sm border-b border-gray-100">
                    <td class="p-2 pl-4">
                        <input type="hidden" name="items[${unique}][semester]" value="${semester}">
                        <input type="text" name="items[${unique}][nama_mata_kuliah]" class="w-full border-gray-300 rounded-lg text-xs font-bold text-gray-800 focus:ring-emerald-500" required placeholder="Cth: Pemrograman Web">
                    </td>
                    <td class="p-2">
                        <input type="number" step="0.5" name="items[${unique}][sks_mata_kuliah]" class="w-full border-gray-300 rounded-lg text-xs text-center focus:ring-emerald-500 font-bold" required placeholder="0">
                    </td>
                    <td class="p-2">
                        <input type="number" name="items[${unique}][jumlah_kelas]" class="w-full border-gray-300 rounded-lg text-xs text-center focus:ring-emerald-500 font-bold" value="1" min="1" required>
                    </td>
                    <td class="p-2">
                        <input type="number" name="items[${unique}][jumlah_dosen_pengampu]" class="w-full border-gray-300 rounded-lg text-xs text-center focus:ring-emerald-500 font-bold" value="1" min="1" required>
                    </td>
                    <td class="p-2">
                        <input type="file" name="items[${unique}][file_bukti]" class="w-full text-[10px] text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" required accept=".pdf,.jpg,.jpeg,.png">
                    </td>
                    <td class="p-2 text-center">
                        <button type="button" onclick="this.closest('tr').remove()" class="text-gray-400 hover:text-red-600 bg-gray-100 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batal Tambah">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', html);
        }

        function deleteItem(url) {
            if(confirm('Hapus data mata kuliah ini? Realisasi capaian akan dihitung ulang.')) {
                const f = document.getElementById('deleteForm');
                f.action = url;
                f.submit();
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
    </style>
    @endpush
</x-app-layout>
