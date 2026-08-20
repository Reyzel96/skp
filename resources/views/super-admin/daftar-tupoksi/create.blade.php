<x-app-layout>
    @section('page-title', 'Tambah Kamus Tupoksi')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .table-input {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            border: none !important;
            padding: 6px 4px;
            font-size: 10px;
            background: transparent;
            resize: none;
            overflow: hidden;
            min-height: 40px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .table-input:focus {
            outline: 2px solid #3b82f6 !important;
            background-color: #f0f9ff;
        }
        /* Hapus fitur scroll number (spinner) */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1 !important;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            text-align: center;
            vertical-align: middle;
            padding: 6px 2px !important;
            color: #475569;
            line-height: 1.2;
        }
        td {
            border: 1px solid #cbd5e1 !important;
            padding: 0 !important;
            vertical-align: top;
        }
        .checkbox-cell { text-align: center; vertical-align: middle !important; }
        [x-cloak] { display: none !important; }
        .drag-active { border-color: #3b82f6 !important; background-color: #eff6ff !important; }
    </style>

    <div class="w-full max-w-full mx-auto p-4 font-sans" x-data="{ importModal: false, isDragging: false }">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-black text-gray-800 uppercase">Tambah Kamus Tupoksi</h2>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.daftar-tupoksi.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-bold">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
                <button type="button" @click="importModal = true" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-bold shadow transition">
                    <i class="fas fa-file-import mr-2"></i> Import Excel
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-600 p-4 rounded mb-6 border-l-4 border-red-500 shadow-sm">
                <ul class="list-disc pl-4 text-sm font-medium">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- MODAL IMPORT EXCEL --}}
        <div x-show="importModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4 relative" @click.away="importModal = false">
                <button type="button" @click="importModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div class="text-center mb-4">
                    <h3 class="text-xl font-black text-gray-800"><i class="fas fa-file-excel text-green-600 mr-2"></i>Import Daftar Tupoksi</h3>
                    <p class="text-xs text-gray-500 mt-1">Gunakan template baku Excel SKP Tendik.</p>
                </div>
                <div class="border-2 border-dashed rounded-xl p-10 flex flex-col items-center justify-center text-center transition-all duration-200 relative overflow-hidden group"
                    :class="isDragging ? 'drag-active' : 'border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-blue-400'"
                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; processExcelFile($event.dataTransfer.files[0], () => importModal = false)">

                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-3 transition-transform group-hover:scale-110" :class="isDragging ? 'scale-110 text-blue-600' : ''"></i>
                    <p class="text-sm font-bold text-gray-700 mb-1">Tarik & Lepaskan File Excel di sini</p>
                    <p class="text-xs text-gray-500 mb-4">Atau</p>
                    <label class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow cursor-pointer hover:bg-blue-700 transition text-sm font-bold">
                        Cari File Komputer
                        <input type="file" class="hidden" accept=".xlsx, .xls, .csv" @change="processExcelFile($event.target.files[0], () => importModal = false); $event.target.value = '';">
                    </label>
                </div>
            </div>
        </div>

        <form action="{{ route('superadmin.daftar-tupoksi.bulk-store') }}" method="POST" id="mainForm" onsubmit="return handleFormSubmit(this);">
            @csrf
            <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse bg-white">
                        <thead>
                            <tr>
                                <th rowspan="2" class="w-8">No</th>
                                <th rowspan="2" class="min-w-[120px]">Jabatan</th>
                                <th rowspan="2" class="min-w-[180px]">Tupoksi Utama</th>
                                <th rowspan="2" class="min-w-[180px]">Jenis Aktivitas</th>
                                <th colspan="3" class="text-center">Kategori</th>
                                <th rowspan="2" class="min-w-[80px]">Periode</th>
                                <th rowspan="2" class="w-16">Freq/ Thn</th>
                                <th rowspan="2" class="w-16">Dur (Mnt)</th>
                                <th rowspan="2" class="w-16">Jml SDM</th>
                                <th rowspan="2" class="w-20">Vol Kerja</th>
                                <th rowspan="2" class="w-10"></th>
                            </tr>
                            <tr>
                                <th class="w-12 text-[8px]">Utama</th>
                                <th class="w-12 text-[8px]">Periode</th>
                                <th class="w-12 text-[8px]">Bukan</th>
                            </tr>
                        </thead>
                        <tbody id="dynamic-form-container"></tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50 p-4 rounded-lg border w-full">
                <button type="button" id="btn-add-row" class="px-5 py-2 bg-white border border-blue-500 text-blue-600 rounded-md text-xs font-bold hover:bg-blue-50 transition">
                    <i class="fas fa-plus mr-1"></i> Tambah Baris Manual
                </button>

                <button type="submit" class="px-10 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-lg hover:bg-blue-700 transition uppercase tracking-widest">
                    <i class="fas fa-save mr-1"></i> Simpan Kamus (<span id="count-display">0</span>)
                </button>
            </div>
        </form>
    </div>

    <script>
        let rowCount = 0;
        const container = document.getElementById('dynamic-form-container');
        const countDisplay = document.getElementById('count-display');
        const availableRoles = @json($roles);

        function handleFormSubmit(form) {
            if (!confirm('Konfirmasi Penyimpanan\n\nApakah Anda yakin sudah mengecek seluruh data dengan teliti?')) {
                return false;
            }

            const items = [];
            const rows = container.querySelectorAll('tr');
            
            if (rows.length === 0) {
                alert('Tidak ada data untuk disimpan.');
                return false;
            }

            rows.forEach((row, index) => {
                // Gunakan querySelector dengan name yang fleksibel karena index bisa berubah
                const bidang = row.querySelector('select[name*="[bidang]"]').value;
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

            // Tambahkan payload JSON ke form
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

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        function updateCounter() { countDisplay.innerText = container.children.length; }

        function reindexForms() {
            const rows = container.children;
            for (let i = 0; i < rows.length; i++) {
                rows[i].querySelector('.row-num').innerText = i + 1;
                const inputs = rows[i].querySelectorAll('[name^="items["]');
                inputs.forEach(input => {
                    input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
                });
            }
            rowCount = rows.length;
            updateCounter();
        }

        function calculateRowVolume(tr) {
            const freq = parseFloat(tr.querySelector('.inp-frekuensi').value) || 0;
            const dur = parseFloat(tr.querySelector('.inp-durasi').value) || 0;
            const sdm = parseFloat(tr.querySelector('.inp-sdm').value) || 0;
            tr.querySelector('.inp-volume').value = freq * dur * sdm;
        }

        function checkRowValidity(tr) {
            const bidang = tr.querySelector('.inp-bidang').value;
            const tupoksi = tr.querySelector('.inp-tupoksi').value.trim();
            const aktivitas = tr.querySelector('.inp-aktivitas').value.trim();
            const isValid = bidang !== "" && tupoksi !== "" && aktivitas !== "";
            
            const dependents = tr.querySelectorAll('.inp-kategori, .inp-periode, .inp-frekuensi, .inp-durasi, .inp-sdm');
            dependents.forEach(el => {
                el.disabled = !isValid;
                if (!isValid) {
                    el.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                } else {
                    el.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                }
            });
        }

        function createFormRow(data = {}) {
            const idx = rowCount++;
            const tr = document.createElement('tr');
            tr.className = "hover:bg-slate-50 transition-colors";

            let roleOptionsHTML = '<option value="">-- Pilih --</option>';
            
            // Group roles by prodi/kategori
            const groupedRoles = availableRoles.reduce((acc, role) => {
                let name = role.name;
                if (name.toLowerCase().includes('rektor')) {
                    (acc['Rektorat'] = acc['Rektorat'] || []).push(role);
                    return acc;
                }
                
                let match = name.match(/(D3|D4|S1|S2)\s+(.+)/i);
                if (match) {
                    let prodi = match[1].toUpperCase() + ' ' + match[2].toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                    (acc[prodi] = acc[prodi] || []).push(role);
                } else {
                    let kat = role.kategori ? (role.kategori.charAt(0).toUpperCase() + role.kategori.slice(1)) : 'Lainnya';
                    let key = 'Umum / ' + kat;
                    (acc[key] = acc[key] || []).push(role);
                }
                return acc;
            }, {});

            if (groupedRoles['Rektorat']) {
                roleOptionsHTML += `<optgroup label="Rektorat">`;
                groupedRoles['Rektorat'].forEach(role => {
                    const selected = (data.bidang && data.bidang.toLowerCase() === role.name.toLowerCase()) ? 'selected' : '';
                    let rname = role.name.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                    roleOptionsHTML += `<option value="${role.name}" ${selected}>${rname}</option>`;
                });
                roleOptionsHTML += `</optgroup>`;
                delete groupedRoles['Rektorat'];
            }

            for (const groupName in groupedRoles) {
                roleOptionsHTML += `<optgroup label="${groupName}">`;
                groupedRoles[groupName].forEach(role => {
                    const selected = (data.bidang && data.bidang.toLowerCase() === role.name.toLowerCase()) ? 'selected' : '';
                    let rname = role.name.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                    roleOptionsHTML += `<option value="${role.name}" ${selected}>${rname}</option>`;
                });
                roleOptionsHTML += `</optgroup>`;
            }

            const periodeOptions = ["Blok", "Bulan", "Bulanan", "Dokumen", "Event", "Hari", "Harian", "Isidetal", "Kegiatan", "Konten", "Mingguan", "Periode", "Semester", "Skripsi", "Surat", "Surat Keputusan", "Tahun", "Tahunan", "Triwulan"];
            let periodeOptionsHTML = '<option value="">-- Pilih --</option>';
            periodeOptions.forEach(p => {
                const selected = (data.periode && data.periode.toLowerCase() === p.toLowerCase()) ? 'selected' : '';
                periodeOptionsHTML += `<option value="${p}" ${selected}>${p}</option>`;
            });

            tr.innerHTML = `
                <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${container.children.length + 1}</td>
                <td><select name="items[${idx}][bidang]" required class="inp-bidang table-input font-semibold text-blue-800" onchange="checkRowValidity(this.closest('tr'))">${roleOptionsHTML}</select></td>
                <td><textarea name="items[${idx}][tupoksi]" required class="inp-tupoksi table-input" oninput="autoResize(this); checkRowValidity(this.closest('tr'))">${data.tupoksi || ''}</textarea></td>
                <td><textarea name="items[${idx}][jenis_aktivitas]" required class="inp-aktivitas table-input" oninput="autoResize(this); checkRowValidity(this.closest('tr'))">${data.jenis_aktivitas || ''}</textarea></td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Utama" ${data.utama === true ? 'checked' : ''} class="inp-kategori w-3 h-3 text-blue-600 rounded"></td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Periodik" ${data.periodik ? 'checked' : ''} class="inp-kategori w-3 h-3 text-blue-600 rounded"></td>
                <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Bukan Tupoksi" ${data.bukan_tupoksi ? 'checked' : ''} class="inp-kategori w-3 h-3 text-blue-600 rounded"></td>
                <td><select name="items[${idx}][periode]" required class="inp-periode table-input">${periodeOptionsHTML}</select></td>
                <td><input type="number" name="items[${idx}][frekuensi_per_tahun]" value="${data.frekuensi || ''}" min="0" required class="inp-frekuensi table-input text-center" onwheel="this.blur()" oninput="calculateRowVolume(this.closest('tr'))"></td>
                <td><input type="number" name="items[${idx}][durasi_menit]" value="${data.durasi || ''}" min="0" required class="inp-durasi table-input text-center" onwheel="this.blur()" oninput="calculateRowVolume(this.closest('tr'))"></td>
                <td><input type="number" name="items[${idx}][jumlah_sdm]" value="${data.jumlah_sdm || 1}" min="1" required class="inp-sdm table-input text-center" onwheel="this.blur()" oninput="calculateRowVolume(this.closest('tr'))"></td>
                <td><input type="number" name="items[${idx}][volume_kerja]" value="${data.volume_kerja || 0}" min="0" readonly class="inp-volume table-input text-center bg-gray-100 font-bold text-blue-600 cursor-not-allowed" onwheel="this.blur()"></td>
                <td class="text-center align-middle"><button type="button" class="text-red-400 hover:text-red-600" onclick="this.closest('tr').remove(); reindexForms();"><i class="fas fa-trash-alt text-xs"></i></button></td>
            `;
            container.appendChild(tr);
            tr.querySelectorAll('textarea').forEach(el => autoResize(el));
            
            // Hitung volume awal jika ada data (misal dari Import)
            checkRowValidity(tr);
            calculateRowVolume(tr);
            updateCounter();
        }

        document.getElementById('btn-add-row').addEventListener('click', () => createFormRow());

        window.processExcelFile = function(file, closeModalCallback) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const json = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });

                    let headerRowIndex = -1;
                    for (let r = 0; r < Math.min(5, json.length); r++) {
                        const rowStr = json[r].map(h => String(h).trim().toLowerCase()).join(' ');
                        if (rowStr.includes('tupoksi') && rowStr.includes('aktivitas')) { headerRowIndex = r; break; }
                    }

                    if (headerRowIndex === -1) { alert("Format Excel tidak sesuai!"); return; }

                    container.innerHTML = ''; rowCount = 0;
                    let lastBidang = "";
                    for (let i = headerRowIndex + 1; i < json.length; i++) {
                        const row = json[i];
                        let currentBidang = String(row[1] || "").trim();
                        if (currentBidang !== "") lastBidang = currentBidang; else currentBidang = lastBidang;
                        
                        // Normalisasi Bidang agar lebih akurat
                        let lowerBidang = currentBidang.toLowerCase();
                        if (lowerBidang.startsWith("dosen")) {
                            currentBidang = "Dosen";
                        } else if (lowerBidang.includes("keuangan")) {
                            currentBidang = "Admin Keuangan Fakultas";
                        } else if (lowerBidang.includes("laboratorium") || lowerBidang.includes("laboran")) {
                            currentBidang = "Laboran";
                        } else if (lowerBidang.startsWith("administrasi prodi")) {
                            currentBidang = "Administrasi Prodi";
                        }

                        let currentTupoksi = String(row[2] || "").trim();
                        let currentAktivitas = String(row[3] || "").trim();
                        if (!currentTupoksi || !currentAktivitas) continue;

                        createFormRow({
                            bidang: currentBidang,
                            tupoksi: currentTupoksi,
                            jenis_aktivitas: currentAktivitas,
                            utama: String(row[4] || "").toLowerCase().includes('v'),
                            periodik: String(row[5] || "").toLowerCase().includes('v'),
                            bukan_tupoksi: String(row[6] || "").toLowerCase().includes('v'),
                            periode: String(row[7] || "").trim(),
                            frekuensi: parseInt(row[8]),
                            durasi: parseInt(row[9]),
                            jumlah_sdm: parseInt(row[10]) || 1,
                            volume_kerja: parseInt(row[11]) || 0
                        });
                    }
                    if(closeModalCallback) closeModalCallback();
                } catch (error) { console.error(error); alert("Gagal memproses file."); }
            };
            reader.readAsArrayBuffer(file);
        };

        createFormRow();
    </script>
    <script>
        // Restore data dari session jika ada error (menggunakan items_json)
        document.addEventListener('DOMContentLoaded', function() {
            @if(old('items_json'))
                try {
                    const oldItemsStr = @json(old('items_json'));
                    const oldItems = oldItemsStr ? JSON.parse(oldItemsStr) : [];
                    if (oldItems && oldItems.length > 0) {
                        container.innerHTML = ''; 
                        rowCount = 0;
                        oldItems.forEach(item => {
                            createFormRow({
                                bidang: item.bidang,
                                tupoksi: item.tupoksi,
                                jenis_aktivitas: item.jenis_aktivitas,
                                utama: item.kategori && item.kategori.includes('Utama'),
                                periodik: item.kategori && item.kategori.includes('Periodik'),
                                bukan_tupoksi: item.kategori && item.kategori.includes('Bukan Tupoksi'),
                                periode: item.periode,
                                frekuensi: item.frekuensi_per_tahun,
                                durasi: item.durasi_menit,
                                jumlah_sdm: item.jumlah_sdm,
                                volume_kerja: item.volume_kerja
                            });
                        });
                    }
                } catch (e) {
                    console.error('Gagal me-restore data lama:', e);
                }
            @endif
        });
    </script>
    @endsection
</x-app-layout>
