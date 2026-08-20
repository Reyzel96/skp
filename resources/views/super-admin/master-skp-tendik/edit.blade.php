<x-app-layout>
    @section('page-title', 'Edit Tupoksi Pegawai')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
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

            th {
                background-color: #f8fafc;
                border: 1px solid #cbd5e1 !important;
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.025em;
                text-align: center;
                vertical-align: middle;
                padding: 4px 2px !important;
                color: #475569;
                line-height: 1.1;
                word-wrap: break-word;
            }

            td {
                border: 1px solid #cbd5e1 !important;
                padding: 0 !important;
                vertical-align: top;
            }

            .checkbox-cell {
                text-align: center;
                vertical-align: middle !important;
            }

            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            ::-webkit-scrollbar-thumb {
                background-color: #cbd5e1;
                border-radius: 3px;
            }

            [x-cloak] {
                display: none !important;
            }
        </style>

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans">
            @php
                $roles = $user->getRoleNames()->implode(', ');
                $fungsional = $user->profile->jabatan_fungsional ?? null;
                $displayJabatan = $fungsional ? "$roles / $fungsional" : $roles;
            @endphp
            <form action="{{ route('superadmin.master-skp-tendik.update', $user->id) }}" method="POST" id="mainForm"
                class="w-full grid grid-cols-1" x-data="{
                    confirmModal: false,
                    pegawaiName: '{{ $user->profile->nama_lengkap ?? $user->name }}',
                    pegawaiNIP: '{{ $user->profile->nip ?? '-' }}',
                    pegawaiPangkat: '{{ $user->profile->pangkat_golongan ?? '-' }}',
                    pegawaiJabatan: '{{ $displayJabatan ?: '-' }}',
                }">
                @csrf
                @method('PUT')

                {{-- INFORMASI PEGAWAI --}}
                <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-6">
                    <div class="bg-white">
                        <div class="grid grid-cols-[40px_1fr] border-b border-gray-800 font-bold bg-blue-50 uppercase">
                            <div class="p-2 text-center border-r border-gray-800"><i
                                    class="fas fa-info-circle text-blue-800"></i></div>
                            <div class="p-2 text-blue-800 text-center">Informasi SKP Tendik (Mode Edit)</div>
                        </div>
                        <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                            <div class="p-2 text-center border-r border-gray-400">1</div>
                            <div class="p-2">Nama</div>
                            <div class="p-2 font-semibold" x-text="pegawaiName"></div>
                        </div>
                        <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                            <div class="p-2 text-center border-r border-gray-400">2</div>
                            <div class="p-2">NIP</div>
                            <div class="p-2 font-semibold" x-text="pegawaiNIP"></div>
                        </div>
                        <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                            <div class="p-2 text-center border-r border-gray-400">3</div>
                            <div class="p-2">Pangkat/Gol</div>
                            <div class="p-2 font-semibold" x-text="pegawaiPangkat"></div>
                        </div>
                        <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                            <div class="p-2 text-center border-r border-gray-400">4</div>
                            <div class="p-2">Jabatan</div>
                            <div class="p-2 font-semibold" x-text="pegawaiJabatan"></div>
                        </div>
                        <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                            <div class="p-2 text-center border-r border-gray-400">5</div>
                            <div class="p-2">Unit Kerja</div>
                            <div class="p-2 font-semibold">Universitas Muhammadiyah Surabaya</div>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg shadow-sm">
                        <p class="font-bold text-sm mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Gagal Memperbarui Data.</p>
                        <ul class="list-disc list-inside text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">📝 Edit Target Tugas Tendik</h2>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                            class="w-full sm:w-auto text-center bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600 transition text-xs font-bold">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden">
                    <table class="w-full table-fixed border-collapse bg-white">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 3%;">No</th>
                                <th rowspan="2" style="width: 10%;">Bidang</th>
                                <th rowspan="2" style="width: 18%;">Tupoksi</th>
                                <th rowspan="2" style="width: 16%;">Jenis Aktivitas Unit Kerja</th>
                                <th rowspan="2" style="width: 8%;">Penilai</th>
                                <th colspan="3" style="width: 9%;" class="border-b border-slate-300">Kategori</th>
                                <th rowspan="2" style="width: 6%;">Periode</th>
                                <th rowspan="2" style="width: 6%;">Frekuensi /Tahun</th>
                                <th rowspan="2" style="width: 5%;">Durasi (mnt)</th>
                                <th rowspan="2" style="width: 5%;">Jumlah SDM</th>
                                <th rowspan="2" style="width: 5%;">Volume Kerja</th>
                                <th rowspan="2" style="width: 4%;" class="bg-white border-none"></th>
                            </tr>
                            <tr>
                                <th style="width: 3%;" class="text-[8px] px-0">Utm</th>
                                <th style="width: 3%;" class="text-[8px] px-0">Prd</th>
                                <th style="width: 3%;" class="text-[8px] px-0">Bkn</th>
                            </tr>
                        </thead>
                        <tbody id="dynamic-form-container"></tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50 p-4 rounded-lg border w-full">
                    <button type="button" id="btn-add-row"
                        class="w-full sm:w-auto px-5 py-2 bg-white border border-blue-500 text-blue-600 rounded-md text-xs font-bold hover:bg-blue-50 transition">
                        <i class="fas fa-plus mr-1"></i> Tambah Baris Manual
                    </button>

                    <button type="button"
                        @click="
                            if(container.children.length === 0) {
                                alert('Gagal! Tabel masih kosong.');
                            } else if(document.getElementById('mainForm').reportValidity()) {
                                confirmModal = true;
                            }
                        "
                        class="w-full sm:w-auto px-10 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-lg hover:bg-blue-700 transition uppercase tracking-widest text-center">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan (<span id="count-display">0</span>)
                    </button>
                </div>

                {{-- MODAL KONFIRMASI SIMPAN --}}
                <div x-show="confirmModal"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm" x-cloak>
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 text-center"
                        @click.away="confirmModal = false">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-blue-50 shadow-sm">
                            <i class="fas fa-question-circle text-2xl text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-800 mb-2">Simpan Perubahan?</h3>
                        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Seluruh rincian tugas akan diperbarui sesuai dengan data terbaru di tabel.</p>

                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button type="button" @click="confirmModal = false"
                                class="px-6 py-2.5 bg-gray-100 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition w-full sm:w-auto">Batal</button>
                            <button type="button" @click="document.getElementById('mainForm').submit()"
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition w-full sm:w-auto shadow-lg">
                                <i class="fas fa-check-circle mr-2"></i> Ya, Perbarui
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            let rowCount = 0;
            const container = document.getElementById('dynamic-form-container');
            const countDisplay = document.getElementById('count-display');

            const rawBidang = @json($bidangList ?? []);
            const availableBidang = Array.isArray(rawBidang) ? rawBidang : Object.values(rawBidang);
            const penilaiRoles = @json($penilaiRoles ?? []);
            const existingActivities = @json($activities);

            function autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }

            function updateCounter() {
                countDisplay.innerText = container.children.length;
            }

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

            function validateNumber(e) {
                if (['-', '.', ',', 'e', 'E'].includes(e.key)) e.preventDefault();
            }

            function createFormRow(data = {}) {
                const idx = rowCount++;
                const tr = document.createElement('tr');
                const isApproved = (data.status_approval === 'approved');
                tr.className = `hover:bg-slate-50 transition-colors form-row-item ${isApproved ? 'bg-emerald-50/30' : ''}`;

                let bidangOptionsHTML = '<option value="">-- Pilih --</option>';
                availableBidang.forEach(bidangName => {
                    const dataBidang = data.role_name ? data.role_name : '';
                    const selected = (dataBidang && dataBidang === bidangName) ? 'selected' : '';
                    bidangOptionsHTML += `<option value="${bidangName}" ${selected}>${bidangName}</option>`;
                });

                let penilaiHTML = '<option value="">-- Pilih --</option>';
                penilaiRoles.forEach(roleItem => {
                    const penilai = (typeof roleItem === 'object' && roleItem.name) ? roleItem.name : String(roleItem);
                    const selected = (data.penilai_role && data.penilai_role.trim().toLowerCase() === penilai.toLowerCase()) ? 'selected' : '';
                    penilaiHTML += `<option value="${penilai}" ${selected}>${penilai}</option>`;
                });

                const periodeOptions = ["Bulanan", "Harian", "Kegiatan", "Mingguan", "Semester", "Tahunan", "Triwulan"];
                let periodeOptionsHTML = '<option value="">-- Pilih --</option>';
                periodeOptions.forEach(p => {
                    const selected = (data.periode && data.periode.toLowerCase() === p.toLowerCase()) ? 'selected' : '';
                    periodeOptionsHTML += `<option value="${p.toLowerCase()}" ${selected}>${p}</option>`;
                });

                const disabledAttr = isApproved ? 'disabled' : '';

                tr.innerHTML = `
                    <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${container.children.length + 1}</td>
                    <td>
                        <select name="items[${idx}][role_name]" required class="table-input font-semibold text-blue-800"
                            onchange="fetchTupoksiByBidang(this)" ${disabledAttr}>
                            ${bidangOptionsHTML}
                        </select>
                    </td>
                    <td>
                        <select name="items[${idx}][tupoksi_select]" class="table-input font-bold text-gray-800"
                            onchange="fillTupoksiData(this)" ${disabledAttr}>
                            <option value="">-- Pilih Tupoksi --</option>
                        </select>
                        <input type="hidden" name="items[${idx}][tupoksi]" value="${data.tupoksi || ''}" ${disabledAttr}>
                    </td>
                    <td><textarea name="items[${idx}][jenis_aktivitas]" required class="table-input leading-snug" oninput="autoResize(this)" placeholder="Isi aktivitas..." ${disabledAttr}>${data.jenis_aktivitas || ''}</textarea></td>
                    <td><select name="items[${idx}][penilai_role]" required class="table-input font-semibold text-emerald-700 bg-emerald-50/30" ${disabledAttr}>${penilaiHTML}</select></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Utama" ${ (data.kategori && data.kategori.includes('Utama')) ? 'checked' : '' } class="w-3 h-3 text-blue-600 rounded category-checkbox" ${disabledAttr}></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Periodik" ${ (data.kategori && data.kategori.includes('Periodik')) ? 'checked' : '' } class="w-3 h-3 text-blue-600 rounded category-checkbox" ${disabledAttr}></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Bukan Tupoksi" ${ (data.kategori && data.kategori.includes('Bukan Tupoksi')) ? 'checked' : '' } class="w-3 h-3 text-blue-600 rounded category-checkbox" ${disabledAttr}></td>

                    <td><select name="items[${idx}][periode]" required class="table-input text-center px-1" ${disabledAttr}>${periodeOptionsHTML}</select></td>
                    <td><input type="number" name="items[${idx}][frekuensi_per_tahun]" value="${data.frekuensi_per_tahun || ''}" min="1" step="1" required class="table-input text-center px-1" oninput="calcVol(this)" ${disabledAttr}></td>
                    <td><input type="number" name="items[${idx}][durasi_menit]" value="${data.durasi_menit || ''}" min="1" step="1" required class="table-input text-center px-1" oninput="calcVol(this)" ${disabledAttr}></td>
                    <td><input type="number" name="items[${idx}][jumlah_sdm]" value="1" readonly class="table-input text-center px-1 bg-gray-100 font-bold text-gray-500"></td>
                    <td class="bg-slate-50 text-right px-2 font-bold text-indigo-700 text-[9px] vol-display pt-3">${ ((data.frekuensi_per_tahun || 0) * (data.durasi_menit || 0) * 1).toLocaleString('id-ID') }</td>
                    <td class="text-center align-middle">
                        ${isApproved ? 
                            '<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded text-[8px] font-black uppercase border border-emerald-200">Locked</span>' : 
                            `<button type="button" class="text-red-400 hover:text-red-600 transition" onclick="this.closest('tr').remove(); reindexForms();">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>`
                        }
                    </td>
                `;
                container.appendChild(tr);
                tr.querySelectorAll('textarea').forEach(el => autoResize(el));

                const roleSelect = tr.querySelector('select[name*="role_name"]');
                if(roleSelect.value !== "") {
                    fetchTupoksiByBidang(roleSelect, data.tupoksi);
                }
                updateCounter();
            }

            window.fetchTupoksiByBidang = function(el, selectedTupoksi = null) {
                const tr = el.closest('tr');
                const tupoksiSelect = tr.querySelector('select[name*="tupoksi_select"]');
                const bidang = el.value;
                if(bidang === "") {
                    tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                    return;
                }
                tupoksiSelect.innerHTML = '<option value="">Memuat...</option>';
                fetch('/superadmin/daftar-tupoksi/bidang/' + encodeURIComponent(bidang))
                    .then(res => res.json())
                    .then(data => {
                        tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                        data.forEach(item => {
                            const isSelected = (selectedTupoksi && selectedTupoksi === item.tupoksi) ? 'selected' : '';
                            const option = document.createElement('option');
                            option.value = JSON.stringify(item);
                            option.text = item.tupoksi;
                            if(isSelected) option.selected = true;
                            tupoksiSelect.appendChild(option);
                        });
                    });
            }

            window.fillTupoksiData = function(el) {
                const tr = el.closest('tr');
                const hiddenTupoksi = tr.querySelector('input[name*="[tupoksi]"]');
                const aktivitasTextarea = tr.querySelector('textarea[name*="jenis_aktivitas"]');
                const periodeSelect = tr.querySelector('select[name*="periode"]');
                const frekuensiInput = tr.querySelector('input[name*="frekuensi_per_tahun"]');
                const durasiInput = tr.querySelector('input[name*="durasi_menit"]');

                if(el.value === "") {
                    hiddenTupoksi.value = "";
                    return;
                }

                const data = JSON.parse(el.value);
                
                // Cek duplikasi tupoksi
                const selectedTupoksi = data.tupoksi;
                const allTupoksiInputs = document.querySelectorAll('input[name*="[tupoksi]"]');
                let isDuplicate = false;
                allTupoksiInputs.forEach(input => {
                    if (input !== hiddenTupoksi && input.value === selectedTupoksi) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    if (!confirm("Tupoksi sudah ada, apakah anda yakin untuk menambahkan lagi?")) {
                        el.value = "";
                        hiddenTupoksi.value = "";
                        return;
                    }
                }

                hiddenTupoksi.value = data.tupoksi;
                aktivitasTextarea.value = data.jenis_aktivitas;
                autoResize(aktivitasTextarea);
                if(data.periode) periodeSelect.value = data.periode.toLowerCase();
                frekuensiInput.value = data.frekuensi_per_tahun;
                durasiInput.value = data.durasi_menit;

                const checkboxes = tr.querySelectorAll('.category-checkbox');
                checkboxes.forEach(cb => cb.checked = false);
                if(data.kategori && Array.isArray(data.kategori)) {
                    data.kategori.forEach(kat => {
                        const target = tr.querySelector(`.category-checkbox[value="${kat}"]`);
                        if(target) target.checked = true;
                    });
                }
                calcVol(frekuensiInput);
            }

            window.calcVol = function(el) {
                const tr = el.closest('tr');
                const f = parseInt(tr.querySelector('[name*="frekuensi_per_tahun"]').value) || 0;
                const d = parseInt(tr.querySelector('[name*="durasi_menit"]').value) || 0;
                tr.querySelector('.vol-display').innerText = (f * d * 1).toLocaleString('id-ID');
            }

            document.getElementById('btn-add-row').addEventListener('click', () => createFormRow());

            // Pre-fill existing data
            if(existingActivities.length > 0) {
                existingActivities.forEach(act => createFormRow(act));
            } else {
                createFormRow();
            }
        </script>
    @endsection
</x-app-layout>
