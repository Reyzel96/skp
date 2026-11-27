<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            /* Menghilangkan panah atas/bawah (spinner) bawaan browser pada input number */
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input[type=number] {
                -moz-appearance: textfield; /* Khusus untuk Firefox */
            }
        </style>

        {{-- Tugas 14: wrapper w-full penuh (padding 24px dari main#print-area); tanpa max-w --}}
        <div class="w-full">
            @php
                // Tugas 9: atribut min/max kalender batas waktu dirender server-side
                // (bukan hanya via JS) agar kalender sudah terbatas sejak halaman dibuka.
                $maksHariForm = (int) config('periode.maks_hari_setelah_selesai', \App\Models\PeriodeSkp::MAKS_HARI_SETELAH_SELESAI);
                $mulaiForm = old('mulai');
                $selesaiForm = old('selesai');
                $batasPengisianForm = old('batas_pengisian');
                $batasPenilaianForm = old('batas_penilaian');
                $batasMaksForm = $selesaiForm ? \Carbon\Carbon::parse($selesaiForm)->addDays($maksHariForm) : null;
                $batasMaksLabel = $batasMaksForm?->format('d/m/Y');
                $diLuarRentangPengisian = $batasPengisianForm && (($mulaiForm && $batasPengisianForm < $mulaiForm) || ($batasMaksForm && $batasPengisianForm > $batasMaksForm->toDateString()));
                $diLuarRentangPenilaian = $batasPenilaianForm && (($mulaiForm && $batasPenilaianForm < $mulaiForm) || ($batasMaksForm && $batasPenilaianForm > $batasMaksForm->toDateString()));
                // Tugas 10/11: kelas chip terpilih/tidak (segmented button)
                $chipOn = 'bg-blue-600 text-white border-blue-600 shadow-sm';
                $chipOff = 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
                // Tugas 11: nilai awal strip ringkasan dirender server-side (untuk test render).
                $ringkasanNama = old('tipe') == 'tahunan' ? 'Tahunan ' . old('tahun', date('Y'))
                    : (old('tipe') == 'semester' ? (old('semester') == 'genap' ? 'Genap' : 'Ganjil') . ' ' . old('tahun', date('Y'))
                    : (old('tipe') == 'bulanan' ? 'Triwulan ' . ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][old('bulan')] ?? '') . ' ' . old('tahun', date('Y')) : '—'));
                $ringkasanStatusMap = ['draft' => ['bg-gray-100 text-gray-600', 'Draft'], 'aktif' => ['bg-emerald-100 text-emerald-700', 'Aktif'], 'selesai' => ['bg-rose-100 text-rose-700', 'Selesai']];
                [$ringkasanStatusCls, $ringkasanStatusTxt] = $ringkasanStatusMap[old('status')] ?? ['bg-gray-100 text-gray-600', '—'];
                $ringkasanRentang = ($mulaiForm && $selesaiForm)
                    ? \Carbon\Carbon::parse($mulaiForm)->format('d/m/Y') . ' s.d. ' . \Carbon\Carbon::parse($selesaiForm)->format('d/m/Y')
                    : '—';
                $ringkasanDurasi = ($mulaiForm && $selesaiForm)
                    ? \Carbon\Carbon::parse($mulaiForm)->diffInDays(\Carbon\Carbon::parse($selesaiForm)) + 1 . ' hari'
                    : '—';
                // Tugas 13: deadline kosong → teks eksplisit 'Deadline belum diisi'.
                $ringkasanDeadline = ($batasPengisianForm || $batasPenilaianForm)
                    ? 'Pengisian: ' . ($batasPengisianForm ? \Carbon\Carbon::parse($batasPengisianForm)->format('d/m/Y') : '—')
                    . ' · Penilaian: ' . ($batasPenilaianForm ? \Carbon\Carbon::parse($batasPenilaianForm)->format('d/m/Y') : '—')
                    : 'Deadline belum diisi';
            @endphp

            {{-- Header 1 baris: judul kiri, Batal + Simpan kanan (sentence case) --}}
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <h1 class="text-xl font-bold text-gray-900">Buat Periode SKP Baru</h1>
                <div class="flex items-center gap-2 ml-auto">
                    <a href="{{ route('superadmin.periode-skp.index') }}"
                        class="bg-white text-gray-700 h-10 px-4 rounded-lg font-semibold hover:bg-gray-100 transition-all text-center border border-gray-300 inline-flex items-center">
                        Batal
                    </a>
                    <button type="submit" form="form-periode" data-tombol-simpan
                        class="bg-blue-600 text-white h-10 px-5 rounded-lg font-semibold hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all shadow inline-flex items-center">
                        Simpan
                    </button>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-4 shadow-sm">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold text-red-800 mb-1">Periode gagal dibuat</h3>
                            <ul class="text-sm text-red-700 space-y-1 font-medium list-disc ml-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tugas 14 (B): flash 'error' dari periodeBentrok / catch controller —
                 redirect back hanya membawa flash ini (BUKAN error bag), jadi WAJIB
                 ditampilkan agar kegagalan simpan tidak terasa "diam-diam". --}}
            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-4 shadow-sm">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold text-red-800 mb-1">Periode gagal dibuat</h3>
                            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tugas 15: pesan form invalid (HTML5) — muncul via JS bila submit
                 diblokir validasi browser; field invalid pertama difokuskan. --}}
            <div id="pesan-form-invalid" class="hidden bg-amber-50 border-l-4 border-amber-500 rounded-lg p-4 mb-4 shadow-sm">
                <h3 class="font-bold text-amber-800 mb-0.5">Periksa kembali isian formulir</h3>
                <p class="text-sm text-amber-700 font-medium">Ada isian yang belum lengkap atau tidak sesuai — kolom yang bermasalah telah ditandai dan difokuskan.</p>
            </div>

            {{-- Strip ringkasan horizontal 1 baris --}}
            <div class="bg-white rounded-xl shadow border border-gray-200 px-4 py-2.5 mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm"
                aria-label="Ringkasan periode">
                <span id="ringkasan-nama" class="text-base font-bold text-gray-900">{{ $ringkasanNama }}</span>
                <span id="ringkasan-status" class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $ringkasanStatusCls }}">{{ $ringkasanStatusTxt }}</span>
                <span class="flex items-center gap-2 text-gray-600"><i class="fas fa-calendar-range text-xs text-gray-400"></i><span id="ringkasan-rentang" class="font-medium text-gray-800">{{ $ringkasanRentang }}</span></span>
                <span class="flex items-center gap-2 text-gray-600"><i class="fas fa-clock text-xs text-gray-400"></i><span id="ringkasan-durasi" class="font-medium text-gray-800">{{ $ringkasanDurasi }}</span></span>
                <span class="flex items-center gap-2 text-gray-600"><i class="fas fa-hourglass-half text-xs text-gray-400"></i><span id="ringkasan-deadline" class="font-medium text-gray-800">{{ $ringkasanDeadline }}</span></span>
                <span id="area-peringatan" class="flex items-center gap-3 ml-auto">
                    @if ($diLuarRentangPengisian)
                        <p id="peringatan-batas-pengisian" class="text-xs text-red-600 font-bold">Peringatan: batas pengisian di luar rentang yang diizinkan ({{ $mulaiForm ? $mulaiForm : '…' }} s.d. {{ $batasMaksForm?->toDateString() }}). Perbaiki sebelum menyimpan.</p>
                    @else
                        <p id="peringatan-batas-pengisian" class="hidden text-xs text-red-600 font-bold"></p>
                    @endif
                    @if ($diLuarRentangPenilaian)
                        <p id="peringatan-batas-penilaian" class="text-xs text-red-600 font-bold">Peringatan: batas penilaian di luar rentang yang diizinkan ({{ $mulaiForm ? $mulaiForm : '…' }} s.d. {{ $batasMaksForm?->toDateString() }}). Perbaiki sebelum menyimpan.</p>
                    @else
                        <p id="peringatan-batas-penilaian" class="hidden text-xs text-red-600 font-bold"></p>
                    @endif
                </span>
            </div>

            {{-- T16: data-turbo="false" — submit form TIDAK di-intercept Turbo Drive;
                 onsubmit confirm() DIHAPUS (penghalang submit). --}}
            <form id="form-periode" action="{{ route('superadmin.periode-skp.store') }}" method="POST"
                data-turbo="false">
                @csrf

                {{-- Tugas 14 (A): grid 2 kolom >=1024px (lg) — kiri Jenis & waktu (1.1fr),
                     kanan Batas waktu + Status bertumpuk (1fr); <lg 1 kolom. --}}
                <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_1fr] gap-4 items-stretch">
                    {{-- ============ KIRI: JENIS DAN WAKTU ============ --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col h-full">
                        <h2 class="text-base font-bold text-gray-800 mb-4">Jenis dan waktu</h2>

                        <div class="space-y-[18px]">
                            <div>
                                <label class="block text-[13px] font-medium text-gray-600 mb-2">Tipe periode *</label>
                                <div class="grid grid-cols-3 gap-2" role="group" aria-label="Tipe periode">
                                    @foreach(['tahunan' => 'Tahunan', 'semester' => 'Semester', 'bulanan' => 'Triwulan'] as $nilaiTipe => $labelTipe)
                                        <button type="button" data-tipe="{{ $nilaiTipe }}" aria-pressed="{{ old('tipe') === $nilaiTipe ? 'true' : 'false' }}" title="{{ $labelTipe }}"
                                            class="h-10 px-3 rounded-lg border font-medium text-sm transition-all {{ old('tipe') === $nilaiTipe ? $chipOn : $chipOff }}">
                                            {{ $labelTipe }}
                                        </button>
                                    @endforeach
                                </div>
                                <select name="tipe" id="tipe" required class="sr-only" tabindex="-1" aria-hidden="true">
                                    <option value="tahunan" {{ old('tipe') == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                                    <option value="semester" {{ old('tipe') == 'semester' ? 'selected' : '' }}>Semester</option>
                                    <option value="bulanan" {{ old('tipe') == 'bulanan' ? 'selected' : '' }}>Triwulan</option>
                                </select>
                            </div>

                            <div class="flex items-end gap-4">
                                <div>
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2" for="tahun">Tahun *</label>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" id="tahun-kurang" aria-label="Kurangi tahun"
                                            class="w-10 h-10 flex-shrink-0 rounded-lg border border-gray-300 bg-white font-medium text-gray-600 hover:bg-gray-50 transition">−</button>
                                        <input type="number" name="tahun" id="tahun" value="{{ old('tahun', date('Y')) }}" min="1900"
                                            onkeydown="if(event.key === '-' || event.key === '+' || event.key === 'e' || event.key === 'E') event.preventDefault();"
                                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg font-semibold text-base text-blue-700 text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="2026" required>
                                        <button type="button" id="tahun-tambah" aria-label="Tambah tahun"
                                            class="w-10 h-10 flex-shrink-0 rounded-lg border border-gray-300 bg-white font-medium text-gray-600 hover:bg-gray-50 transition">+</button>
                                    </div>
                                </div>

                                <div id="section-semester" class="{{ old('tipe') == 'semester' ? '' : 'hidden' }} flex-1">
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2">Semester *</label>
                                    <div class="grid grid-cols-2 gap-2" role="group" aria-label="Semester">
                                        @foreach(['ganjil' => 'Ganjil', 'genap' => 'Genap'] as $nilaiSms => $labelSms)
                                            <button type="button" data-semester="{{ $nilaiSms }}" aria-pressed="{{ old('semester') === $nilaiSms ? 'true' : 'false' }}" title="Semester {{ $labelSms }} ({{ $nilaiSms === 'genap' ? '1 Mar s.d. 31 Agu' : '1 Sep s.d. akhir Feb tahun berikutnya' }})"
                                                class="h-10 px-3 rounded-lg border font-medium text-sm transition-all {{ old('semester') === $nilaiSms ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : $chipOff }}">
                                                {{ $labelSms }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <select name="semester" id="semester" class="sr-only" tabindex="-1" aria-hidden="true">
                                        <option value="ganjil" {{ old('semester') == 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="genap" {{ old('semester') == 'genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                </div>

                                <div id="section-bulanan" class="{{ old('tipe') == 'bulanan' ? '' : 'hidden' }} flex-1">
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2">Triwulan *</label>
                                    <div class="grid grid-cols-4 gap-2" role="group" aria-label="Triwulan">
                                        @foreach([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $tw => $roman)
                                            <button type="button" data-triwulan="{{ $tw }}" aria-pressed="{{ old('bulan') == $tw ? 'true' : 'false' }}" title="Triwulan {{ $roman }} {{ date('Y') }}"
                                                class="h-10 px-2 rounded-lg border font-medium text-sm transition-all {{ old('bulan') == $tw ? 'bg-teal-600 text-white border-teal-600 shadow-sm' : $chipOff }}">
                                                {{ $roman }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <select name="bulan" id="bulan" class="sr-only" tabindex="-1" aria-hidden="true">
                                        @foreach([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $tw => $roman)
                                            <option value="{{ $tw }}" {{ old('bulan') == $tw ? 'selected' : '' }}>Triwulan {{ $roman }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2" for="mulai">Tanggal mulai *</label>
                                    <input type="date" name="mulai" id="mulai" value="{{ old('mulai') }}" required
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2" for="selesai">Tanggal selesai *</label>
                                    <input type="date" name="selesai" id="selesai" value="{{ old('selesai') }}" required
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ KANAN: BATAS WAKTU + STATUS BERTUMPUK ============ --}}
                    <div class="flex flex-col gap-4">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                <h2 class="text-base font-bold text-gray-800">Batas waktu</h2>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" data-tambah-hari="3"
                                        class="h-8 px-2.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 transition">+3 hari</button>
                                    <button type="button" data-tambah-hari="7"
                                        class="h-8 px-2.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 transition">+7 hari</button>
                                </div>
                            </div>

                            <div class="space-y-[18px]">
                                <div>
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2" for="batas_pengisian" title="Pegawai tidak bisa lagi menyimpan realisasi SKP-nya setelah tanggal ini.">Pengisian *</label>
                                    <input type="date" name="batas_pengisian" id="batas_pengisian"
                                        value="{{ old('batas_pengisian') }}" required
                                        @if ($mulaiForm) min="{{ $mulaiForm }}" @endif
                                        @if ($batasMaksForm) max="{{ $batasMaksForm->toDateString() }}" @endif
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($batasMaksLabel)
                                        <p class="text-xs text-gray-500 mt-1.5" data-teks-paling-lambat="batas_pengisian">Maks. {{ $batasMaksLabel }}</p>
                                    @else
                                        <p class="text-xs text-gray-500 mt-1.5 hidden" data-teks-paling-lambat="batas_pengisian"></p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-[13px] font-medium text-gray-600 mb-2" for="batas_penilaian" title="Atasan tidak bisa lagi memverifikasi/mengubah nilai pegawai setelah tanggal ini.">Penilaian *</label>
                                    <input type="date" name="batas_penilaian" id="batas_penilaian"
                                        value="{{ old('batas_penilaian') }}" required
                                        @if ($mulaiForm) min="{{ $batasPengisianForm ?: $mulaiForm }}" @endif
                                        @if ($batasMaksForm) max="{{ $batasMaksForm->toDateString() }}" @endif
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($batasMaksLabel)
                                        <p class="text-xs text-gray-500 mt-1.5" data-teks-paling-lambat="batas_penilaian">Maks. {{ $batasMaksLabel }}</p>
                                    @else
                                        <p class="text-xs text-gray-500 mt-1.5 hidden" data-teks-paling-lambat="batas_penilaian"></p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex-1">
                            <h2 class="text-base font-bold text-gray-800 mb-4">Status</h2>

                            <div class="grid grid-cols-3 gap-2 mb-4" role="group" aria-label="Status periode">
                                @foreach(['draft' => ['Draft', 'bg-gray-600 text-white border-gray-600'], 'aktif' => ['Aktif', 'bg-emerald-600 text-white border-emerald-600'], 'selesai' => ['Selesai', 'bg-rose-600 text-white border-rose-600']] as $nilaiStatus => [$labelStatus, $kelasStatus])
                                    <button type="button" data-status="{{ $nilaiStatus }}" aria-pressed="{{ old('status') === $nilaiStatus ? 'true' : 'false' }}" title="Draft = tersembunyi dari pegawai. Aktif = bisa diisi. Selesai = dikunci."
                                        class="h-10 px-3 rounded-lg border font-medium text-sm transition-all {{ old('status') === $nilaiStatus ? $kelasStatus : $chipOff }}">
                                        {{ $labelStatus }}
                                    </button>
                                @endforeach
                            </div>
                            <select name="status" id="status" class="sr-only" tabindex="-1" aria-hidden="true">
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft (Tersembunyi)</option>
                                <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif (Bisa Diisi)</option>
                                <option value="selesai" {{ old('status') == 'selesai' ? 'selected' : '' }}>Selesai (Dikunci)</option>
                            </select>

                            {{-- Tugas 14 (A): label override via tautan kecil pembuka field --}}
                            <button type="button" id="tampilkan-label-khusus" aria-expanded="false"
                                class="text-[13px] font-medium text-blue-600 hover:text-blue-800 mb-3">
                                + Label khusus (opsional)
                            </button>
                            <div id="panel-label-khusus" class="hidden">
                                <p class="text-xs text-gray-500 mb-3 font-medium">Kosongkan untuk otomatis — contoh: <span id="contoh-label" class="font-medium text-gray-700">…</span>.</p>
                                <div class="space-y-[18px]">
                                    <div>
                                        <label class="block text-[13px] font-medium text-gray-600 mb-1.5" for="label_triwulan">Label triwulan</label>
                                        <input type="text" name="label_triwulan" id="label_triwulan"
                                            value="{{ old('label_triwulan') }}"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-medium text-gray-600 mb-1.5" for="label_periode_penilaian">Label periode penilaian</label>
                                        <input type="text" name="label_periode_penilaian" id="label_periode_penilaian"
                                            value="{{ old('label_periode_penilaian') }}"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <script>
                // ===== Tugas 11: BUG 2 — perhitungan tanggal JS aman timezone (WIB).
                // addDaysIso memakai komponen lokal murni, TANPA toISOString (yang
                // menggeser mundur 1 hari di UTC+7: 2027-12-07 → 2027-12-06).
                function addDaysIso(isoDate, days) {
                    const [y, m, d] = isoDate.split('-').map(Number);
                    const tgl = new Date(y, m - 1, d + days);
                    return tgl.getFullYear() + '-' +
                        String(tgl.getMonth() + 1).padStart(2, '0') + '-' +
                        String(tgl.getDate()).padStart(2, '0');
                }

                // ===== Tugas 15: init KOMPATIBEL TURBO DRIVE =====
                // Turbo Drive mengganti <body> saat navigasi internal TANPA reload
                // dokumen → DOMContentLoaded tidak fire ulang, sedangkan inline
                // script ini dieksekusi ulang oleh Turbo pada body baru. Pemicu
                // init keduanya: DOMContentLoaded (reload penuh — module app.js
                // sudah dieksekusi → flatpickr tersedia) dan eksekusi langsung
                // (navigasi Turbo — window.flatpickr sudah ada sejak load pertama).
                function initPeriodeForm() {
                    if (document.body.dataset.periodeFormInited) return;
                    document.body.dataset.periodeFormInited = '1';

                    if (!window.flatpickr) {
                        console.error('Flatpickr belum termuat — tanggal memakai input date bawaan browser.');
                    }

                    const formPeriode = document.getElementById('form-periode');
                    const tipeSelect = document.getElementById('tipe');
                    const tahunInput = document.getElementById('tahun');
                    const semesterSelect = document.getElementById('semester');
                    const bulanSelect = document.getElementById('bulan');
                    const statusSelect = document.getElementById('status');

                    const sectionSemester = document.getElementById('section-semester');
                    const sectionBulanan = document.getElementById('section-bulanan');

                    const inputMulai = document.getElementById('mulai');
                    const inputSelesai = document.getElementById('selesai');
                    const inputBatasPengisian = document.getElementById('batas_pengisian');
                    const inputBatasPenilaian = document.getElementById('batas_penilaian');
                    const peringatanPengisian = document.getElementById('peringatan-batas-pengisian');
                    const peringatanPenilaian = document.getElementById('peringatan-batas-penilaian');
                    // Sinkron dengan config('periode.maks_hari_setelah_selesai') & validasi server
                    const MAKS_HARI_SETELAH_SELESAI = {{ (int) config('periode.maks_hari_setelah_selesai', \App\Models\PeriodeSkp::MAKS_HARI_SETELAH_SELESAI) }};
                    // Tugas 14 (A): default batas pengisian = selesai+5, penilaian = selesai+7.
                    const PENGISIAN_HARI_DEFAULT = 5;

                    // ===== flatpickr — tampilan dd/mm/yyyy, nilai terkirim tetap Y-m-d =====
                    // T16: tiap fitur punya try/catch sendiri — satu fitur gagal
                    // tidak menghentikan fitur lain (termasuk submit form).
                    const opsiFlatpickr = {
                        altInput: true,
                        altFormat: 'd/m/Y',
                        dateFormat: 'Y-m-d',
                        locale: window.flatpickrIndonesian,
                        disableMobile: true,
                        allowInput: false,
                    };
                    let fpMulai = null, fpSelesai = null, fpPengisian = null, fpPenilaian = null;
                    try {
                        if (window.flatpickr) {
                            fpMulai = window.flatpickr(inputMulai, opsiFlatpickr);
                            fpSelesai = window.flatpickr(inputSelesai, opsiFlatpickr);
                            fpPengisian = window.flatpickr(inputBatasPengisian, opsiFlatpickr);
                            fpPenilaian = window.flatpickr(inputBatasPenilaian, opsiFlatpickr);
                        }
                    } catch (e) {
                        console.error('flatpickr gagal dimuat (form tetap berfungsi dgn input date native):', e);
                    }

                    function setTanggal(input, fp, iso) {
                        if (fp) {
                            fp.setDate(iso, false);
                        } else {
                            input.value = iso;
                        }
                    }

                    function rentangDefault() {
                        const tipe = tipeSelect.value;
                        const tahun = tahunInput.value;
                        if (!tahun || tahun.length < 4) return null;

                        if (tipe === 'tahunan') {
                            return { mulai: `${tahun}-01-01`, selesai: `${tahun}-12-31` };
                        }
                        if (tipe === 'semester') {
                            // Tugas 17: kalender akademik — Genap = 01-03 s.d. 31-08 tahun sama,
                            // Ganjil = 01-09 s.d. 28/29-02 TAHUN BERIKUTNYA (tahun = tahun mulai).
                            if (semesterSelect.value === 'genap') {
                                return { mulai: `${tahun}-03-01`, selesai: `${tahun}-08-31` };
                            }
                            const tahunAkhir = Number(tahun) + 1;
                            const hariAkhir = new Date(tahunAkhir, 2, 0).getDate();
                            return { mulai: `${tahun}-09-01`, selesai: `${tahunAkhir}-02-${String(hariAkhir).padStart(2, '0')}` };
                        }
                        if (tipe === 'bulanan') {
                            const awal = { 1: '01', 2: '04', 3: '07', 4: '10' }[bulanSelect.value];
                            const akhir = { 1: '03', 2: '06', 3: '09', 4: '12' }[bulanSelect.value];
                            if (!awal) return null;
                            const hariAkhir = new Date(Number(tahun), Number(akhir), 0).getDate();
                            return { mulai: `${tahun}-${awal}-01`, selesai: `${tahun}-${akhir}-${String(hariAkhir).padStart(2, '0')}` };
                        }
                        return null;
                    }

                    // Tugas 14 (A): batas pengisian/penilaian juga punya penanda manual,
                    // agar default +5/+7 tidak menimpa nilai yang sudah diedit admin.
                    [inputMulai, inputSelesai, inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                        input.addEventListener('input', () => { input.dataset.manual = '1'; });
                        input.addEventListener('change', () => { input.dataset.manual = '1'; });
                    });

                    function isiTanggalDefault() {
                        const range = rentangDefault();
                        if (!range) return;
                        if (!inputMulai.value || inputMulai.dataset.manual !== '1') {
                            setTanggal(inputMulai, fpMulai, range.mulai);
                        }
                        if (!inputSelesai.value || inputSelesai.dataset.manual !== '1') {
                            setTanggal(inputSelesai, fpSelesai, range.selesai);
                        }
                        // Tugas 14 (A): batas default — pengisian = selesai+5 hari,
                        // penilaian = selesai+7 hari (hanya bila kosong / belum manual).
                        if (!inputBatasPengisian.value || inputBatasPengisian.dataset.manual !== '1') {
                            setTanggal(inputBatasPengisian, fpPengisian, addDaysIso(range.selesai, PENGISIAN_HARI_DEFAULT));
                        }
                        if (!inputBatasPenilaian.value || inputBatasPenilaian.dataset.manual !== '1') {
                            setTanggal(inputBatasPenilaian, fpPenilaian, addDaysIso(range.selesai, MAKS_HARI_SETELAH_SELESAI));
                        }
                        updateDateConstraints();
                    }

                    function toggleSections() {
                        const tipe = tipeSelect.value;
                        sectionSemester.classList.toggle('hidden', tipe !== 'semester');
                        sectionBulanan.classList.toggle('hidden', tipe !== 'bulanan');

                        semesterSelect.required = (tipe === 'semester');
                        bulanSelect.required = (tipe === 'bulanan');

                        isiTanggalDefault();
                    }

                    // ===== chip segmented → select asli =====
                    function setAktif(btn, aktif, kelasAktif) {
                        const kelasNonaktif = ['bg-white', 'text-gray-700', 'border-gray-300'];
                        kelasAktif.split(' ').forEach(c => btn.classList.toggle(c, aktif));
                        kelasNonaktif.forEach(c => btn.classList.toggle(c, !aktif));
                        btn.setAttribute('aria-pressed', aktif ? 'true' : 'false');
                    }

                    function sinkronChips() {
                        document.querySelectorAll('[data-tipe]').forEach(b =>
                            setAktif(b, b.dataset.tipe === tipeSelect.value, 'bg-blue-600 text-white border-blue-600'));
                        document.querySelectorAll('[data-semester]').forEach(b =>
                            setAktif(b, b.dataset.semester === semesterSelect.value, 'bg-indigo-600 text-white border-indigo-600'));
                        document.querySelectorAll('[data-triwulan]').forEach(b =>
                            setAktif(b, Number(b.dataset.triwulan) === Number(bulanSelect.value), 'bg-teal-600 text-white border-teal-600'));
                        document.querySelectorAll('[data-status]').forEach(b => {
                            const warna = {
                                draft: 'bg-gray-600 text-white border-gray-600',
                                aktif: 'bg-emerald-600 text-white border-emerald-600',
                                selesai: 'bg-rose-600 text-white border-rose-600',
                            }[b.dataset.status];
                            setAktif(b, b.dataset.status === statusSelect.value, warna);
                        });
                    }

                    function pilih(selectEl, nilai) {
                        selectEl.value = nilai;
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                        sinkronChips();
                        perbaruiRingkasan();
                    }

                    document.querySelectorAll('[data-tipe]').forEach(b =>
                        b.addEventListener('click', () => pilih(tipeSelect, b.dataset.tipe)));
                    document.querySelectorAll('[data-semester]').forEach(b =>
                        b.addEventListener('click', () => pilih(semesterSelect, b.dataset.semester)));
                    document.querySelectorAll('[data-triwulan]').forEach(b =>
                        b.addEventListener('click', () => pilih(bulanSelect, b.dataset.triwulan)));
                    document.querySelectorAll('[data-status]').forEach(b =>
                        b.addEventListener('click', () => pilih(statusSelect, b.dataset.status)));
                    // T16: pemasangan listener di-wrap try/catch — kegagalan satu fitur
                    // tidak menghentikan sisa init (submit form tetap bekerja).

                    // Tahun: tombol cepat -/+
                    function ubahTahun(delta) {
                        const v = parseInt(tahunInput.value, 10);
                        if (isNaN(v)) return;
                        tahunInput.value = Math.max(1900, v + delta);
                        tahunInput.dispatchEvent(new Event('input', { bubbles: true }));
                        perbaruiRingkasan();
                    }
                    document.getElementById('tahun-kurang')?.addEventListener('click', () => ubahTahun(-1));
                    document.getElementById('tahun-tambah')?.addEventListener('click', () => ubahTahun(1));

                    // Tombol cepat +3/+7 hari dari tanggal selesai (mengisi kedua batas).
                    document.querySelectorAll('[data-tambah-hari]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            if (!inputSelesai.value) return;
                            const target = addDaysIso(inputSelesai.value, Number(btn.dataset.tambahHari));
                            setTanggal(inputBatasPengisian, fpPengisian, target);
                            setTanggal(inputBatasPenilaian, fpPenilaian, target);
                            updateDateConstraints();
                            perbaruiRingkasan();
                        });
                    });

                    // Tugas 14 (A): tautan '+ Label khusus (opsional)' membuka panel field.
                    const tampilLabelBtn = document.getElementById('tampilkan-label-khusus');
                    const panelLabel = document.getElementById('panel-label-khusus');
                    tampilLabelBtn?.addEventListener('click', () => {
                        const terbuka = panelLabel.classList.toggle('hidden') === false;
                        tampilLabelBtn.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
                        if (terbuka) tampilLabelBtn.classList.add('hidden');
                    });

                    tipeSelect.addEventListener('change', () => {
                        toggleSections();
                        sinkronChips();
                        perbaruiRingkasan();
                    });
                    semesterSelect.addEventListener('change', () => {
                        isiTanggalDefault();
                        sinkronChips();
                        perbaruiRingkasan();
                    });
                    bulanSelect.addEventListener('change', () => {
                        isiTanggalDefault();
                        sinkronChips();
                        perbaruiRingkasan();
                    });
                    statusSelect.addEventListener('change', () => {
                        sinkronChips();
                        perbaruiRingkasan();
                    });

                    tahunInput.addEventListener('wheel', function(e) {
                        e.preventDefault();
                    }, {
                        passive: false
                    });

                    // ===== strip ringkasan live (format dd/mm/yyyy) =====
                    function formatTanggalIndonesia(isoDate) {
                        const [y, m, d] = isoDate.split('-').map(Number);
                        return String(d).padStart(2, '0') + '/' + String(m).padStart(2, '0') + '/' + y;
                    }

                    function namaPeriodeLive() {
                        const tahun = tahunInput.value;
                        if (!tahun || tahun.length < 4) return null;
                        const tipe = tipeSelect.value;
                        if (tipe === 'tahunan') return 'Tahunan ' + tahun;
                        if (tipe === 'semester') return (semesterSelect.value === 'genap' ? 'Genap' : 'Ganjil') + ' ' + tahun; // Tugas 17: label Ganjil/Genap
                        if (tipe === 'bulanan') {
                            const roman = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' }[bulanSelect.value] || '';
                            return 'Triwulan ' + roman + ' ' + tahun;
                        }
                        return null;
                    }

                    function perbaruiRingkasan() {
                        const nama = namaPeriodeLive();
                        const elNama = document.getElementById('ringkasan-nama');
                        const elContoh = document.getElementById('contoh-label');
                        if (elNama) elNama.textContent = nama ?? '—';
                        if (elContoh) elContoh.textContent = nama ?? '…';

                        const elStatus = document.getElementById('ringkasan-status');
                        if (elStatus) {
                            const map = {
                                draft: ['bg-gray-100 text-gray-600', 'Draft'],
                                aktif: ['bg-emerald-100 text-emerald-700', 'Aktif'],
                                selesai: ['bg-rose-100 text-rose-700', 'Selesai'],
                            };
                            const [kelas, label] = map[statusSelect.value] ?? ['bg-gray-100 text-gray-600', '—'];
                            elStatus.className = 'inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider ' + kelas;
                            elStatus.textContent = label;
                        }

                        const elRentang = document.getElementById('ringkasan-rentang');
                        const elDurasi = document.getElementById('ringkasan-durasi');
                        const elDeadline = document.getElementById('ringkasan-deadline');
                        if (inputMulai.value && inputSelesai.value) {
                            elRentang.textContent = formatTanggalIndonesia(inputMulai.value) + ' s.d. ' + formatTanggalIndonesia(inputSelesai.value);
                            const [y1, m1, d1] = inputMulai.value.split('-').map(Number);
                            const [y2, m2, d2] = inputSelesai.value.split('-').map(Number);
                            const hari = Math.round((new Date(y2, m2 - 1, d2) - new Date(y1, m1 - 1, d1)) / 86400000) + 1;
                            elDurasi.textContent = hari + ' hari';
                        } else {
                            elRentang.textContent = '—';
                            elDurasi.textContent = '—';
                        }
                        if (inputBatasPengisian.value || inputBatasPenilaian.value) {
                            elDeadline.textContent =
                                'Pengisian: ' + (inputBatasPengisian.value ? formatTanggalIndonesia(inputBatasPengisian.value) : '—') +
                                ' · Penilaian: ' + (inputBatasPenilaian.value ? formatTanggalIndonesia(inputBatasPenilaian.value) : '—');
                        } else {
                            // Tugas 13: deadline kosong → teks eksplisit.
                            elDeadline.textContent = 'Deadline belum diisi';
                        }
                    }

                    // Run on load — T16: tiap fitur init try/catch terpisah.
                    try { toggleSections(); } catch (e) { console.error('toggleSections:', e); }
                    try { sinkronChips(); } catch (e) { console.error('sinkronChips:', e); }
                    try { perbaruiRingkasan(); } catch (e) { console.error('perbaruiRingkasan:', e); }
                    try { updateDateConstraints(); } catch (e) { console.error('updateDateConstraints:', e); }

                    tahunInput.addEventListener('input', () => {
                        isiTanggalDefault();
                        updateDateConstraints();
                        validateDates();
                        perbaruiRingkasan();
                    });

                    [inputMulai, inputSelesai, inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                        input.addEventListener('change', () => {
                            updateDateConstraints();
                            validateDates();
                            perbaruiRingkasan();
                        });
                    });

                    function formatTanggalPanjang(isoDate) {
                        return new Date(isoDate + 'T00:00:00').toLocaleDateString('id-ID', {
                            day: 'numeric', month: 'long', year: 'numeric'
                        });
                    }

                    function perbaruiPeringatan(input, elPeringatan, min, max, labelField) {
                        if (!elPeringatan) return;
                        const val = input.value;
                        if (val && ((min && val < min) || (max && val > max))) {
                            elPeringatan.textContent = `Peringatan: batas ${labelField} di luar rentang yang diizinkan (${min ?? '…'} s.d. ${max ?? '…'}). Perbaiki sebelum menyimpan.`;
                            elPeringatan.classList.remove('hidden');
                        } else {
                            elPeringatan.classList.add('hidden');
                        }
                    }

                    function updateDateConstraints() {
                        const valMulai = inputMulai.value;
                        const valSelesai = inputSelesai.value;
                        const valPengisian = inputBatasPengisian.value;

                        if (valMulai) {
                            inputSelesai.min = valMulai;
                            fpSelesai?.set('minDate', valMulai);
                        } else {
                            inputSelesai.removeAttribute('min');
                            fpSelesai?.set('minDate', null);
                        }

                        if (valSelesai) {
                            const maxBatas = addDaysIso(valSelesai, MAKS_HARI_SETELAH_SELESAI);
                            const maxBatasLabel = formatTanggalIndonesia(maxBatas);
                            inputBatasPengisian.min = valMulai || '';
                            inputBatasPenilaian.min = valPengisian || valMulai || '';
                            inputBatasPengisian.max = maxBatas;
                            inputBatasPenilaian.max = maxBatas;
                            fpPengisian?.set('minDate', valMulai || null);
                            fpPengisian?.set('maxDate', maxBatas);
                            fpPenilaian?.set('minDate', valPengisian || valMulai || null);
                            fpPenilaian?.set('maxDate', maxBatas);
                            // Tugas 14 (A): teks bantuan singkat "Maks. {d/m/Y}".
                            [inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                                const teks = input.parentElement.querySelector('[data-teks-paling-lambat]');
                                if (teks) {
                                    teks.textContent = 'Maks. ' + maxBatasLabel;
                                    teks.classList.remove('hidden');
                                }
                            });
                        } else {
                            inputBatasPengisian.removeAttribute('min');
                            inputBatasPengisian.removeAttribute('max');
                            inputBatasPenilaian.removeAttribute('max');
                            fpPengisian?.set('minDate', null);
                            fpPengisian?.set('maxDate', null);
                            fpPenilaian?.set('maxDate', null);
                        }

                        if (valPengisian) {
                            inputBatasPenilaian.min = valPengisian;
                        } else if (valMulai) {
                            inputBatasPenilaian.min = valMulai;
                        } else {
                            inputBatasPenilaian.removeAttribute('min');
                        }

                        perbaruiPeringatan(inputBatasPengisian, peringatanPengisian, valMulai, inputBatasPengisian.max, 'pengisian');
                        perbaruiPeringatan(inputBatasPenilaian, peringatanPenilaian, inputBatasPenilaian.min, inputBatasPenilaian.max, 'penilaian');
                    }

                    function validateDates() {
                        // Tidak lagi mengubah nilai field secara diam-diam.
                        // Semua batasan (tidak boleh sebelum mulai / melewati selesai)
                        // divalidasi di server (controller) dengan pesan error yang jelas.
                        updateDateConstraints();
                    }

                    // ===== T16: anti double-click DI EVENT 'submit' (bukan click) =====
                    // Tanpa preventDefault; disable ditunda setTimeout(...,0) supaya
                    // submit tetap terkirim; re-enable 3 detik bila masih di halaman.
                    // Fallback invalid tetap: bila form tidak valid, tampilkan pesan +
                    // fokus field invalid pertama (tanpa mengunci tombol).
                    formPeriode.addEventListener('invalid', (e) => {
                        const kotak = document.getElementById('pesan-form-invalid');
                        if (kotak) {
                            kotak.classList.remove('hidden');
                            kotak.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                        const invalid = formPeriode.querySelector(':invalid');
                        if (invalid) {
                            invalid.focus({ preventScroll: false });
                            invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, true);
                    formPeriode.addEventListener('submit', () => {
                        document.querySelectorAll('[data-tombol-simpan]').forEach(btn => {
                            if (btn.dataset.mengirim === '1') return;
                            btn.dataset.mengirim = '1';
                            // setTimeout(...,0): disable SETELAH event submit selesai —
                            // tidak mengganggu pengiriman form.
                            setTimeout(() => {
                                btn.disabled = true;
                                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
                            }, 0);
                            // Re-enable 3 detik (jika masih di halaman yang sama).
                            setTimeout(() => {
                                btn.disabled = false;
                                btn.dataset.mengirim = '';
                            }, 3000);
                        });
                    });
                }

                // Pemicu ganda (Tugas 15): reload penuh → DOMContentLoaded;
                // navigasi Turbo Drive → eksekusi langsung (script re-run pada body baru).
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPeriodeForm);
                } else {
                    initPeriodeForm();
                }
                // T16: penanda init dihapus saat Turbo menyimpan halaman ke cache,
                // supaya init jalan lagi saat halaman dibuka kembali dari cache.
                document.addEventListener('turbo:before-cache', () => {
                    delete document.body.dataset.periodeFormInited;
                });
            </script>
        </div>
    @endsection
</x-app-layout>
