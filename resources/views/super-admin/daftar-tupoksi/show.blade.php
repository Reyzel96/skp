<x-app-layout>
    @section('page-title', 'Detail Daftar Tupoksi: ' . $bidang)

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <style>
        th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1 !important;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px !important;
            color: #475569;
            line-height: 1.2;
        }
        td {
            border: 1px solid #cbd5e1 !important;
            padding: 8px 6px !important;
            vertical-align: top;
            font-size: 10px;
        }
        .checkbox-cell { text-align: center; vertical-align: middle !important; }
        
        /* Hapus fitur scroll number (spinner) */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            th, td { font-size: 9px; padding: 4px 2px !important; }
        }
    </style>

    <div class="w-full p-4 font-sans" x-data="{ newRowsCount: 0 }">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">Detail Tupoksi: {{ $bidang }}</h2>
                <p class="text-sm text-gray-500">Daftar lengkap tupoksi untuk jabatan {{ $bidang }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.daftar-tupoksi.index') }}"
                    class="bg-gray-500 text-white px-5 py-2.5 rounded-lg shadow-md hover:bg-gray-600 transition text-sm font-bold flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('superadmin.daftar-tupoksi.bulk-store') }}" method="POST" id="inlineAddForm" onsubmit="return handleFormSubmit(this);">
            @csrf
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse bg-white" id="tupoksiTable">
                        <thead>
                            <tr>
                                <th rowspan="2" class="w-10">No</th>
                                <th rowspan="2" class="min-w-[120px]">Jabatan</th>
                                <th rowspan="2" class="min-w-[200px]">Tupoksi Utama</th>
                                <th rowspan="2" class="min-w-[200px]">Jenis Aktivitas</th>
                                <th colspan="3" class="text-center">Kategori</th>
                                <th rowspan="2" class="min-w-[80px]">Periode</th>
                                <th rowspan="2" class="w-16">Freq/ Thn</th>
                                <th rowspan="2" class="w-16">Durasi (Mnt)</th>
                                <th rowspan="2" class="w-16">Jml SDM</th>
                                <th rowspan="2" class="w-20">Volume Kerja</th>
                                <th rowspan="2" class="w-16">Aksi</th>
                            </tr>
                            <tr>
                                <th class="w-12 text-[8px]">Utama</th>
                                <th class="w-12 text-[8px]">Periode</th>
                                <th class="w-12 text-[8px]">Bukan</th>
                            </tr>
                        </thead>
                        <tbody id="tupoksiTableBody">
                            @forelse($daftarTupoksi as $idx => $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="font-semibold text-blue-800 uppercase px-2">{{ $item->bidang }}</td>
                                <td class="px-2 whitespace-pre-wrap">{{ $item->tupoksi }}</td>
                                <td class="px-2 whitespace-pre-wrap italic text-gray-600">{{ $item->jenis_aktivitas }}</td>
                                <td class="checkbox-cell">
                                    @if(is_array($item->kategori) && in_array('Utama', $item->kategori))
                                        <i class="fas fa-check text-blue-600"></i>
                                    @endif
                                </td>
                                <td class="checkbox-cell">
                                    @if(is_array($item->kategori) && in_array('Periodik', $item->kategori))
                                        <i class="fas fa-check text-blue-600"></i>
                                    @endif
                                </td>
                                <td class="checkbox-cell">
                                    @if(is_array($item->kategori) && in_array('Bukan Tupoksi', $item->kategori))
                                        <i class="fas fa-check text-blue-600"></i>
                                    @endif
                                </td>
                                <td class="px-2 text-center">{{ ucfirst($item->periode) }}</td>
                                <td class="px-2 text-center">{{ $item->frekuensi_per_tahun }}</td>
                                <td class="px-2 text-center">{{ $item->durasi_menit }}</td>
                                <td class="px-2 text-center">{{ $item->jumlah_sdm }}</td>
                                <td class="px-2 text-center font-bold text-blue-600 bg-gray-50">{{ $item->volume_kerja }}</td>
                                <td class="text-center align-middle">
                                    <div class="flex justify-center gap-1">
                                        <a href="{{ route('superadmin.daftar-tupoksi.edit', $item->id) }}" class="text-blue-500 hover:text-blue-700 p-1 rounded transition">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <button type="button" 
                                            onclick="if(confirm('Yakin ingin menghapus tupoksi ini?')) deleteTupoksi('{{ route('superadmin.daftar-tupoksi.destroy', $item->id) }}')" 
                                            class="text-red-500 hover:text-red-700 p-1 rounded transition">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr id="emptyRow">
                                <td colspan="13" class="p-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada daftar tupoksi untuk jabatan ini.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                <button type="button" id="btnAddRow" @click="newRowsCount++; addRow()"
                    class="w-full sm:w-auto bg-blue-600 text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-blue-700 transition text-sm font-bold flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Tupoksi
                </button>
                
                <button type="submit" x-show="newRowsCount > 0" x-cloak
                    class="w-full sm:w-auto bg-green-600 text-white px-8 py-2.5 rounded-lg shadow-md hover:bg-green-700 transition text-sm font-black uppercase tracking-widest flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Simpan (<span x-text="newRowsCount"></span>)
                </button>
            </div>
        </form>
    </div>

    {{-- Form terpisah untuk Delete agar tidak bertumpuk dengan form bulk store --}}
    <form id="deleteForm" method="POST" class="hidden">
        @csrf @method('DELETE')
    </form>

    <script>
        function handleFormSubmit(form) {
            const items = [];
            // Hanya ambil baris yang baru ditambahkan (yang punya input/select/textarea)
            const rows = tableBody.querySelectorAll('tr.bg-blue-50\\/30');
            
            if (rows.length === 0) return false;

            rows.forEach((row) => {
                const bidang = row.querySelector('textarea[name*="[bidang]"]').value;
                const tupoksi = row.querySelector('textarea[name*="[tupoksi]"]').value;
                const jenis_aktivitas = row.querySelector('textarea[name*="[jenis_aktivitas]"]').value;
                
                const kategori = [];
                row.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                    kategori.push(cb.value);
                });
                
                const periode = row.querySelector('select[name*="[periode]"]').value;
                const frekuensi_per_tahun = row.querySelector('input[name*="[frekuensi_per_tahun]"]').value;
                const durasi_menit = row.querySelector('input[name*="[durasi_menit]"]').value;
                const jumlah_sdm = row.querySelector('input[name*="[jumlah_sdm]"]').value;
                const volume_kerja = row.querySelector('input[name*="[volume_kerja]"]').value;

                items.push({
                    bidang, tupoksi, jenis_aktivitas, kategori, periode,
                    frekuensi_per_tahun, durasi_menit, jumlah_sdm, volume_kerja
                });
            });

            const jsonInput = document.createElement('input');
            jsonInput.type = 'hidden';
            jsonInput.name = 'items_json';
            jsonInput.value = JSON.stringify(items);
            form.appendChild(jsonInput);

            // Hapus atribut 'name' pada input asli agar tidak terkena limit max_input_vars PHP
            form.querySelectorAll('[name^="items["]').forEach(el => {
                el.removeAttribute('name');
            });

            return true;
        }

        function deleteTupoksi(url) {
            const form = document.getElementById('deleteForm');
            form.action = url;
            form.submit();
        }

        // Restore data dari session jika ada error (menggunakan items_json)
        document.addEventListener('DOMContentLoaded', function() {
            @if(old('items_json'))
                try {
                    const oldItemsStr = @json(old('items_json'));
                    const oldItems = oldItemsStr ? JSON.parse(oldItemsStr) : [];
                    if (oldItems && oldItems.length > 0) {
                        oldItems.forEach(item => {
                            // Update newRowsCount for Alpine.js if needed (though it's outside Alpine here)
                            // We use addRow() which increment addedRowCount
                            addRow();
                            const lastTr = tableBody.lastElementChild;
                            
                            // Fill the data
                            lastTr.querySelector('textarea[name*="[tupoksi]"]').value = item.tupoksi || '';
                            lastTr.querySelector('textarea[name*="[jenis_aktivitas]"]').value = item.jenis_aktivitas || '';
                            lastTr.querySelector('select[name*="[periode]"]').value = item.periode || '';
                            lastTr.querySelector('input[name*="[frekuensi_per_tahun]"]').value = item.frekuensi_per_tahun || '';
                            lastTr.querySelector('input[name*="[durasi_menit]"]').value = item.durasi_menit || '';
                            lastTr.querySelector('input[name*="[jumlah_sdm]"]').value = item.jumlah_sdm || 1;
                            
                            if (item.kategori) {
                                item.kategori.forEach(k => {
                                    const cb = lastTr.querySelector(`input[type="checkbox"][value="${k}"]`);
                                    if (cb) cb.checked = true;
                                });
                            }
                            
                            calculateVolume(lastTr);
                            autoResize(lastTr.querySelector('textarea[name*="[tupoksi]"]'));
                            autoResize(lastTr.querySelector('textarea[name*="[jenis_aktivitas]"]'));
                        });
                        
                        // Update Alpine.js state
                        const xData = document.querySelector('[x-data]').__x.$data;
                        xData.newRowsCount = oldItems.length;
                    }
                } catch (e) {
                    console.error('Gagal me-restore data lama:', e);
                }
            @endif
        });

        let addedRowCount = 0;
        const currentBidang = "{{ $bidang }}";
        const tableBody = document.getElementById('tupoksiTableBody');
        const emptyRow = document.getElementById('emptyRow');

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        function calculateVolume(tr) {
            const freq = parseFloat(tr.querySelector('.inp-frekuensi').value) || 0;
            const dur = parseFloat(tr.querySelector('.inp-durasi').value) || 0;
            const sdm = parseFloat(tr.querySelector('.inp-sdm').value) || 0;
            tr.querySelector('.inp-volume').value = freq * dur * sdm;
        }

        function addRow() {
            if (emptyRow) emptyRow.style.display = 'none';
            
            const idx = addedRowCount++;
            const totalExistingRows = tableBody.querySelectorAll('tr:not(#emptyRow)').length;
            const tr = document.createElement('tr');
            tr.className = "bg-blue-50/30 animate-pulse-once hover:bg-blue-50/50 transition-colors";
            
            const periodeOptions = ["Blok", "Bulan", "Bulanan", "Dokumen", "Event", "Hari", "Harian", "Isidetal", "Kegiatan", "Konten", "Mingguan", "Periode", "Semester", "Skripsi", "Surat", "Surat Keputusan", "Tahun", "Tahunan", "Triwulan"];
            let periodeHTML = '<select name="items['+idx+'][periode]" required class="w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 bg-white min-w-[70px]"><option value="">-</option>';
            periodeOptions.forEach(p => {
                periodeHTML += `<option value="${p}">${p}</option>`;
            });
            periodeHTML += '</select>';

            tr.innerHTML = `
                <td class="text-center font-bold text-blue-400 pt-3">${totalExistingRows + 1}</td>
                <td class="p-1">
                    <textarea name="items[${idx}][bidang]" readonly 
                        class="w-full text-[10px] border-none bg-transparent font-bold text-blue-800 uppercase focus:ring-0 p-1 resize-none overflow-hidden leading-tight" 
                        oninput="autoResize(this)">${currentBidang}</textarea>
                </td>
                <td class="p-1">
                    <textarea name="items[${idx}][tupoksi]" required 
                        class="w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 bg-white min-h-[40px] leading-tight" 
                        oninput="autoResize(this)" placeholder="Tupoksi..."></textarea>
                </td>
                <td class="p-1">
                    <textarea name="items[${idx}][jenis_aktivitas]" required 
                        class="w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 bg-white min-h-[40px] leading-tight" 
                        oninput="autoResize(this)" placeholder="Aktivitas..."></textarea>
                </td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Utama" class="w-3 h-3 text-blue-600 rounded"></td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Periodik" class="w-3 h-3 text-blue-600 rounded"></td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Bukan Tupoksi" class="w-3 h-3 text-blue-600 rounded"></td>
                <td class="p-1">${periodeHTML}</td>
                <td class="p-1"><input type="number" name="items[${idx}][frekuensi_per_tahun]" value="" min="1" required class="inp-frekuensi w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 text-center bg-white" onwheel="this.blur()" oninput="calculateVolume(this.closest('tr'))"></td>
                <td class="p-1"><input type="number" name="items[${idx}][durasi_menit]" value="" min="0" required class="inp-durasi w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 text-center bg-white" onwheel="this.blur()" oninput="calculateVolume(this.closest('tr'))"></td>
                <td class="p-1"><input type="number" name="items[${idx}][jumlah_sdm]" value="1" min="1" required class="inp-sdm w-full text-[10px] border-gray-300 rounded focus:ring-blue-500 p-1 text-center bg-white" onwheel="this.blur()" oninput="calculateVolume(this.closest('tr'))"></td>
                <td class="p-1"><input type="number" name="items[${idx}][volume_kerja]" value="0" readonly class="inp-volume w-full text-[10px] border-none bg-transparent font-bold text-blue-600 text-center focus:ring-0 p-1"></td>
                <td class="text-center align-middle">
                    <button type="button" onclick="this.closest('tr').remove(); document.querySelector('[x-data]').__x.$data.newRowsCount--; if(tableBody.querySelectorAll('tr:not(#emptyRow)').length === 0) emptyRow.style.display='table-row';" class="text-gray-400 hover:text-red-500 p-1">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(tr);
            tr.querySelectorAll('textarea').forEach(el => autoResize(el));
            tr.querySelector('textarea[name*="[tupoksi]"]').focus();
        }
    </script>
    <style>
        .animate-pulse-once { animation: pulse-once 0.5s ease-in-out; }
        @keyframes pulse-once { 0% { background-color: rgba(59, 130, 246, 0.2); } 100% { background-color: transparent; } }
    </style>
    @endsection
</x-app-layout>
