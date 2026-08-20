<x-app-layout>
    @section('page-title', 'Edit Tupoksi')

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
    </style>

    <div class="w-full max-w-full mx-auto p-4 font-sans">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-black text-gray-800 uppercase">Edit Tupoksi</h2>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.daftar-tupoksi.show-detail', $daftarTupoksi->bidang) }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-bold">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-600 p-4 rounded mb-6 border-l-4 border-red-500 shadow-sm">
                <ul class="list-disc pl-4 text-sm font-medium">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('superadmin.daftar-tupoksi.update', $daftarTupoksi->id) }}" method="POST" id="editForm" onsubmit="return confirm('Konfirmasi Penyimpanan\n\nApakah Anda yakin sudah mengecek seluruh data dengan teliti?');">
            @csrf
            @method('PUT')
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
                            </tr>
                            <tr>
                                <th class="w-12 text-[8px]">Utama</th>
                                <th class="w-12 text-[8px]">Periode</th>
                                <th class="w-12 text-[8px]">Bukan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="text-center text-[9px] font-bold text-slate-400 pt-3">1</td>
                            <td>
                                <select name="bidang" required class="table-input font-semibold text-blue-800 inp-bidang" onchange="checkValidity()">
                                    <option value="">-- Pilih --</option>
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
                                                @php
                                                    $isSelected = ($daftarTupoksi->bidang == $role->name);
                                                    if ($role->name === 'Dosen' && str_starts_with(strtolower($daftarTupoksi->bidang), 'dosen ')) {
                                                        $isSelected = true;
                                                    }
                                                @endphp
                                                <option value="{{ $role->name }}" {{ $isSelected ? 'selected' : '' }}>{{ ucwords(strtolower($role->name)) }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif

                                    @foreach($groupedRoles as $groupName => $group)
                                        <optgroup label="{{ $groupName }}">
                                            @foreach($group as $role)
                                                @php
                                                    $isSelected = ($daftarTupoksi->bidang == $role->name);
                                                    if ($role->name === 'Dosen' && str_starts_with(strtolower($daftarTupoksi->bidang), 'dosen ')) {
                                                        $isSelected = true;
                                                    }
                                                @endphp
                                                <option value="{{ $role->name }}" {{ $isSelected ? 'selected' : '' }}>{{ ucwords(strtolower($role->name)) }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <textarea name="tupoksi" required class="table-input inp-tupoksi" oninput="autoResize(this); checkValidity()">{{ old('tupoksi', $daftarTupoksi->tupoksi) }}</textarea>
                            </td>
                            <td>
                                <textarea name="jenis_aktivitas" required class="table-input inp-aktivitas" oninput="autoResize(this); checkValidity()">{{ old('jenis_aktivitas', $daftarTupoksi->jenis_aktivitas) }}</textarea>
                            </td>
                            @php $kat = is_array($daftarTupoksi->kategori) ? $daftarTupoksi->kategori : []; @endphp
                            <td class="checkbox-cell">
                                <input type="checkbox" name="kategori[]" value="Utama" {{ in_array('Utama', $kat) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded inp-kategori">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="kategori[]" value="Periodik" {{ in_array('Periodik', $kat) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded inp-kategori">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="kategori[]" value="Bukan Tupoksi" {{ in_array('Bukan Tupoksi', $kat) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded inp-kategori">
                            </td>
                            <td>
                                <select name="periode" required class="table-input inp-periode">
                                    <option value="">-- Pilih --</option>
                                    @foreach(["Blok", "Bulan", "Bulanan", "Dokumen", "Event", "Hari", "Harian", "Isidetal", "Kegiatan", "Konten", "Mingguan", "Periode", "Semester", "Skripsi", "Surat", "Surat Keputusan", "Tahun", "Tahunan", "Triwulan"] as $p)
                                        <option value="{{ $p }}" {{ $daftarTupoksi->periode == $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="frekuensi_per_tahun" value="{{ old('frekuensi_per_tahun', $daftarTupoksi->frekuensi_per_tahun) }}" min="1" required class="table-input text-center inp-frekuensi" onwheel="this.blur()" oninput="calculateVolume()">
                            </td>
                            <td>
                                <input type="number" name="durasi_menit" value="{{ old('durasi_menit', $daftarTupoksi->durasi_menit) }}" min="1" required class="table-input text-center inp-durasi" onwheel="this.blur()" oninput="calculateVolume()">
                            </td>
                            <td>
                                <input type="number" name="jumlah_sdm" value="{{ old('jumlah_sdm', $daftarTupoksi->jumlah_sdm) }}" min="1" required class="table-input text-center inp-sdm" onwheel="this.blur()" oninput="calculateVolume()">
                            </td>
                            <td>
                                <input type="number" name="volume_kerja" value="{{ old('volume_kerja', $daftarTupoksi->volume_kerja) }}" min="0" readonly class="table-input text-center bg-gray-100 font-bold text-blue-600 cursor-not-allowed inp-volume" onwheel="this.blur()">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="px-10 py-3 bg-blue-600 text-white rounded-xl text-sm font-black shadow-lg hover:bg-blue-700 transition uppercase tracking-widest">
                    <i class="fas fa-save mr-2"></i> Perbarui Tupoksi
                </button>
            </div>
        </form>
    </div>

    <script>
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        function calculateVolume() {
            const freq = parseFloat(document.querySelector('.inp-frekuensi').value) || 0;
            const dur = parseFloat(document.querySelector('.inp-durasi').value) || 0;
            const sdm = parseFloat(document.querySelector('.inp-sdm').value) || 0;
            document.querySelector('.inp-volume').value = freq * dur * sdm;
        }

        function checkValidity() {
            const bidang = document.querySelector('.inp-bidang').value;
            const tupoksi = document.querySelector('.inp-tupoksi').value.trim();
            const aktivitas = document.querySelector('.inp-aktivitas').value.trim();
            const isValid = bidang !== "" && tupoksi !== "" && aktivitas !== "";
            
            const dependents = document.querySelectorAll('.inp-kategori, .inp-periode, .inp-frekuensi, .inp-durasi, .inp-sdm');
            dependents.forEach(el => {
                el.disabled = !isValid;
                if (!isValid) {
                    el.classList.add('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                } else {
                    el.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                }
            });
        }

        document.querySelectorAll('textarea').forEach(el => autoResize(el));
        checkValidity();
    </script>
    @endsection
</x-app-layout>
