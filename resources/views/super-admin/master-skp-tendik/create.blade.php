<x-app-layout>
    @section('page-title', 'Tambah Tupoksi Pegawai')

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

            .drag-active {
                border-color: #3b82f6 !important;
                background-color: #eff6ff !important;
            }
        </style>

        <div class="w-full max-w-full mx-auto p-4 min-w-0 font-sans">
            <form action="{{ route('superadmin.master-skp-tendik.store') }}" method="POST" id="mainForm"
                class="w-full grid grid-cols-1" x-data="{
                    setupModal: {{ old('user_id') ? 'false' : 'true' }},
                    confirmModal: false,
                    pegawaiId: '{{ old('user_id') }}',
                    pegawaiName: '......................',
                    pegawaiNIP: '......................',
                    pegawaiPangkat: '......................',
                    pegawaiJabatan: '......................',
                    periodeId: '',
                    periodeText: '......................',

                    get isValidSetup() { return this.pegawaiId !== '' && this.periodeId !== ''; }
                }">
                @csrf

                <input type="hidden" name="periode_skp_id" x-model="periodeId">

                {{-- 1. MODAL PERSIAPAN AWAL (PILIH TENDIK & PERIODE) --}}
                <div x-show="setupModal"
                    class="fixed inset-0 z-[50] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm" x-cloak>
                    <div
                        class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full mx-4 transform transition-all max-h-[90vh] overflow-y-auto">
                        <div class="text-center mb-6">
                            <h2 class="text-2xl font-black text-gray-800">Persiapan Awal</h2>
                        </div>

                        <div class="space-y-6">
                            @php
                                $sortedUsersTendik = $usersTendik->sortBy(
                                    fn($u) => $u->profile->nama_lengkap ?? $u->name,
                                );
                            @endphp

                            <div class="mb-4 text-left">
                                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pilih Periode SKP
                                    *</label>
                                <select x-model="periodeId"
                                    @change="periodeText = $event.target.options[$event.target.selectedIndex].text" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold bg-white text-sm">
                                    <option value="">-- Pilih Periode SKP --</option>
                                    @foreach ($periode as $p)
                                        <option value="{{ $p->id }}">
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

                            <div class="border border-gray-800 rounded-lg overflow-hidden mt-2 shadow-sm">
                                <div class="bg-white">
                                    <div
                                        class="grid grid-cols-[40px_1fr] border-b border-gray-800 font-bold bg-blue-50 uppercase">
                                        <div class="p-2 text-center border-r border-gray-800"><i
                                                class="fas fa-user text-blue-800"></i></div>
                                        <div class="p-2 text-blue-800 text-center">Pilih Pegawai Tendik *</div>
                                    </div>
                                    <div class="p-2 border-b border-gray-400 bg-blue-50">
                                        <select name="user_id" x-model="pegawaiId"
                                            @change="
                                                let opt = $el.options[$el.selectedIndex];
                                                pegawaiName = opt.dataset.nama || '......................';
                                                pegawaiNIP = opt.dataset.nip || '......................';
                                                pegawaiPangkat = opt.dataset.pangkat || '......................';
                                                pegawaiJabatan = opt.dataset.jabatan || '......................';
                                            "
                                            required
                                            class="w-full px-2 py-1.5 border border-blue-300 rounded focus:ring-2 focus:ring-blue-500 font-bold text-blue-800 bg-white text-xs">
                                            <option value="">-- Pilih Tendik --</option>
                                            @foreach ($sortedUsersTendik as $ut)
                                                @php
                                                    $roles = $ut->getRoleNames()->implode(', ');
                                                    $fungsional = $ut->profile->jabatan_fungsional ?? null;
                                                    $displayJabatan = $fungsional ? "$roles / $fungsional" : $roles;
                                                @endphp
                                                <option value="{{ $ut->id }}"
                                                    data-nama="{{ $ut->profile->nama_lengkap ?? $ut->name }}"
                                                    data-nip="{{ $ut->profile->nip ?? '-' }}"
                                                    data-pangkat="{{ $ut->profile->pangkat_golongan ?? '-' }}"
                                                    data-jabatan="{{ $displayJabatan ?: '-' }}">
                                                    ({{ $ut->profile->unit_kerja ?? 'Unit' }})
                                                    {{ $ut->profile->nama_lengkap ?? $ut->name }}
                                                </option>
                                            @endforeach
                                        </select>
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
                                </div>
                            </div>

                            <div
                                class="pt-6 border-t border-gray-200 flex flex-col md:flex-row justify-center items-center gap-3">
                                <a href="{{ route('superadmin.master-skp-tendik.index') }}"
                                    class="w-full md:w-auto px-8 py-3 bg-gray-500 text-white text-center rounded-xl font-bold shadow-lg hover:bg-gray-600 transition">
                                    <i class="fas fa-times mr-2"></i> Batal
                                </a>
                                <button type="button" :disabled="!isValidSetup" @click="setupModal = false"
                                    class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white rounded-xl font-bold shadow-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    Lanjutkan Pengisian <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. MODAL KONFIRMASI SIMPAN (VERIFIKASI 2 LANGKAH) --}}
                <div x-show="confirmModal"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm" x-cloak>
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 text-center"
                        @click.away="confirmModal = false">
                        <div
                            class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-yellow-50 shadow-sm">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-600"></i>
                        </div>
                        <h3 class="text-xl font-black text-gray-800 mb-2">Konfirmasi Penyimpanan</h3>
                        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Apakah Anda yakin sudah mengecek seluruh data
                            (Bidang, Tupoksi, Penilai, dll) dengan teliti?</p>

                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button type="button" @click="confirmModal = false"
                                class="px-6 py-2.5 bg-gray-100 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition w-full sm:w-auto">Cek
                                Kembali</button>
                            <button type="button" @click="document.getElementById('mainForm').submit()"
                                class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition w-full sm:w-auto shadow-lg">
                                <i class="fas fa-check-circle mr-2"></i> Ya, Simpan Sekarang
                            </button>
                        </div>
                    </div>
                </div>

                {{-- MAIN CONTENT (Ditampilkan setelah modal ditutup) --}}
                <div x-show="!setupModal" x-cloak>
                    <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-6">
                        <div class="bg-white">
                            <div class="grid grid-cols-[40px_1fr] border-b border-gray-800 font-bold bg-blue-50 uppercase">
                                <div class="p-2 text-center border-r border-gray-800"><i
                                        class="fas fa-info-circle text-blue-800"></i></div>
                                <div class="p-2 text-blue-800 text-center">Informasi SKP Tendik</div>
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
                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                <div class="p-2 text-center border-r border-gray-400">5</div>
                                <div class="p-2">Unit Kerja</div>
                                <div class="p-2 font-semibold">Universitas Muhammadiyah Surabaya</div>
                            </div>
                            <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                                <div class="p-2 text-center border-r border-gray-400">6</div>
                                <div class="p-2">Periode SKP</div>
                                <div class="p-2 font-semibold text-blue-600" x-text="periodeText"></div>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-lg shadow-sm">
                            <p class="font-bold text-sm mb-2"><i class="fas fa-exclamation-circle mr-1"></i> Gagal
                                Menyimpan Data. Periksa hal berikut:</p>
                            <ul class="list-disc list-inside text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">📝 Buat Target Tugas Tendik</h2>
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
                                    alert('Gagal! Tabel masih kosong. Silakan tambah baris terlebih dahulu.');
                                } else if(document.getElementById('mainForm').reportValidity()) {
                                    confirmModal = true;
                                }
                            "
                            class="w-full sm:w-auto px-10 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-lg hover:bg-blue-700 transition uppercase tracking-widest text-center">
                            <i class="fas fa-save mr-1"></i> Simpan Data (<span id="count-display">0</span>)
                        </button>
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
                tr.className = "hover:bg-slate-50 transition-colors form-row-item";

                let bidangOptionsHTML = '<option value="">-- Pilih --</option>';
                availableBidang.forEach(bidangName => {
                    const selected = (data.bidang && data.bidang === bidangName) ? 'selected' : '';
                    bidangOptionsHTML += `<option value="${bidangName}" ${selected}>${bidangName}</option>`;
                });

                let penilaiHTML = '<option value="">-- Pilih --</option>';
                penilaiRoles.forEach(roleItem => {
                    const penilai = (typeof roleItem === 'object' && roleItem.name) ? roleItem.name : String(roleItem);
                    const selected = (data.penilai_role && data.penilai_role.trim().toLowerCase() === penilai
                        .toLowerCase()) ? 'selected' : '';
                    penilaiHTML += `<option value="${penilai}" ${selected}>${penilai}</option>`;
                });

                const periodeOptions = ["Bulanan", "Harian", "Kegiatan", "Mingguan", "Semester", "Tahunan", "Triwulan"];
                let periodeOptionsHTML = '<option value="">-- Pilih --</option>';
                periodeOptions.forEach(p => {
                    const selected = (data.periode && data.periode.toLowerCase() === p.toLowerCase()) ? 'selected' : '';
                    periodeOptionsHTML += `<option value="${p.toLowerCase()}" ${selected}>${p}</option>`;
                });

                tr.innerHTML = `
                    <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${container.children.length + 1}</td>
                    <td>
                        <select name="items[${idx}][role_name]" required class="table-input font-semibold text-blue-800"
                            onchange="fetchTupoksiByBidang(this)">
                            ${bidangOptionsHTML}
                        </select>
                    </td>
                    <td>
                        <select name="items[${idx}][tupoksi_select]" class="table-input font-bold text-gray-800"
                            onchange="fillTupoksiData(this)">
                            <option value="">-- Pilih Tupoksi --</option>
                        </select>
                        <input type="hidden" name="items[${idx}][tupoksi]" value="${data.tupoksi || ''}">
                    </td>
                    <td><textarea name="items[${idx}][jenis_aktivitas]" required disabled class="table-input leading-snug bg-gray-50 cursor-not-allowed opacity-60" oninput="autoResize(this)" placeholder="Pilih tupoksi dahulu...">${data.jenis_aktivitas || ''}</textarea></td>
                    <td><select name="items[${idx}][penilai_role]" required disabled class="table-input font-semibold text-emerald-700 bg-gray-50 cursor-not-allowed opacity-60">${penilaiHTML}</select></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Utama" disabled ${data.utama ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60"></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Periodik" disabled ${data.periodik ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60"></td>
                    <td class="checkbox-cell"><input type="checkbox" name="items[${idx}][kategori][]" value="Bukan Tupoksi" disabled ${data.bukan_tupoksi ? 'checked' : ''} class="w-3 h-3 text-blue-600 rounded category-checkbox opacity-60"></td>

                    <td>
                        <select name="items[${idx}][periode]" required disabled class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60">
                            ${periodeOptionsHTML}
                        </select>
                    </td>

                    <td><input type="number" name="items[${idx}][frekuensi_per_tahun]" value="${data.frekuensi || ''}" min="1" step="1" required disabled class="table-input text-center required-number px-1 bg-gray-50 cursor-not-allowed opacity-60" onkeypress="validateNumber(event)" oninput="calcVol(this)" placeholder="..."></td>
                    <td><input type="number" name="items[${idx}][durasi_menit]" value="${data.durasi || ''}" min="1" step="1" required disabled class="table-input text-center required-number px-1 bg-gray-50 cursor-not-allowed opacity-60" onkeypress="validateNumber(event)" oninput="calcVol(this)" placeholder="..."></td>

                    <td><input type="number" name="items[${idx}][jumlah_sdm]" value="1" readonly tabindex="-1" class="table-input text-center px-1 bg-gray-100 cursor-not-allowed font-bold text-gray-500"></td>

                    <td class="bg-slate-50 text-right px-2 font-bold text-indigo-700 text-[9px] vol-display pt-3">${((data.frekuensi || 0) * (data.durasi || 0) * 1).toLocaleString('id-ID')}</td>
                    <td class="text-center bg-white border-none align-middle">
                        <button type="button" class="text-red-400 hover:text-red-600 transition" onclick="this.closest('tr').remove(); reindexForms();">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
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
                const penilaiSelect = tr.querySelector('select[name*="penilai_role"]');
                const checkboxes = tr.querySelectorAll('.category-checkbox');

                const allLockedInputs = [aktivitasTextarea, penilaiSelect, periodeSelect, frekuensiInput, durasiInput];

                if(el.value === "") {
                    hiddenTupoksi.value = "";
                    allLockedInputs.forEach(input => {
                        input.disabled = true;
                        input.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                    });
                    checkboxes.forEach(cb => {
                        cb.disabled = true;
                        cb.classList.add('opacity-60');
                    });
                    return;
                }

                // Enable fields
                allLockedInputs.forEach(input => {
                    input.disabled = false;
                    input.classList.remove('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                });
                checkboxes.forEach(cb => {
                    cb.disabled = false;
                    cb.classList.remove('opacity-60');
                });
                penilaiSelect.classList.add('bg-emerald-50/30');

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
                        // Lock again
                        allLockedInputs.forEach(input => {
                            input.disabled = true;
                            input.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                        });
                        checkboxes.forEach(cb => {
                            cb.disabled = true;
                            cb.classList.add('opacity-60');
                        });
                        return;
                    }
                }

                hiddenTupoksi.value = data.tupoksi;
                aktivitasTextarea.value = data.jenis_aktivitas;
                autoResize(aktivitasTextarea);

                if(data.periode) {
                    periodeSelect.value = data.periode.toLowerCase();
                }

                frekuensiInput.value = data.frekuensi_per_tahun;
                durasiInput.value = data.durasi_menit;

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
                const s = 1;
                tr.querySelector('.vol-display').innerText = (f * d * s).toLocaleString('id-ID');
            }

            document.getElementById('btn-add-row').addEventListener('click', () => createFormRow());

            createFormRow();
        </script>
    @endsection
</x-app-layout>
