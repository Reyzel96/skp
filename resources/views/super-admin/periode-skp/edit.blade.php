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

        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border-t-4 border-blue-600">
                <div class="flex items-center mb-8">
                    <a href="{{ route('superadmin.periode-skp.index') }}"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800 font-bold transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali ke Daftar Periode
                    </a>
                </div>

                <div class="flex items-center gap-4 mb-2">
                    <h1 class="text-3xl font-black text-gray-900">Edit Periode SKP</h1>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-mono border border-gray-200">
                        ID: #{{ $periodeSkp->id }}
                    </span>
                </div>
                <p class="text-gray-600 mb-8 font-medium">Sesuaikan pengaturan rentang waktu SKP dan batas waktu (deadline) pengisian oleh pegawai.</p>

                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-5 mb-8 shadow-sm">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h3 class="font-bold text-red-800 mb-1">Gagal Menyimpan Data!</h3>
                                <ul class="text-sm text-red-700 space-y-1 font-medium list-disc ml-4">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form id="form-periode" action="{{ route('superadmin.periode-skp.update', $periodeSkp->id) }}" method="POST"
                    class="space-y-6" onsubmit="return confirm('Konfirmasi Penyimpanan\n\nApakah Anda yakin sudah mengecek seluruh data dengan teliti?');">
                    @csrf
                    @method('PUT')

                    <div class="bg-blue-50/50 p-6 rounded-xl border border-blue-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Tipe Periode *</label>
                                <select name="tipe" id="tipe" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl font-bold text-gray-800 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <option value="tahunan" {{ old('tipe', $periodeSkp->tipe) == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                                    <option value="semester" {{ old('tipe', $periodeSkp->tipe) == 'semester' ? 'selected' : '' }}>Semester</option>
                                    <option value="bulanan" {{ old('tipe', $periodeSkp->tipe) == 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Tahun Acuan *</label>
                                <input type="number" name="tahun" id="tahun" value="{{ old('tahun', $periodeSkp->tahun) }}" min="1900"
                                    onkeydown="if(event.key === '-' || event.key === '+' || event.key === 'e' || event.key === 'E') event.preventDefault();"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl font-bold text-xl text-blue-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                    placeholder="Contoh: 2024" required>
                            </div>
                        </div>
                    </div>

                    <div id="section-semester" class="{{ old('tipe', $periodeSkp->tipe) == 'semester' ? '' : 'hidden' }} bg-indigo-50/50 p-6 rounded-xl border border-indigo-100">
                        <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Pilih Semester *</label>
                        <select name="semester" id="semester"
                            class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-xl font-bold text-gray-800 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="ganjil" {{ old('semester', $periodeSkp->semester) == 'ganjil' ? 'selected' : '' }}>Semester Ganjil (Gasal)</option>
                            <option value="genap" {{ old('semester', $periodeSkp->semester) == 'genap' ? 'selected' : '' }}>Semester Genap</option>
                        </select>
                    </div>

                    <div id="section-bulanan" class="{{ old('tipe', $periodeSkp->tipe) == 'bulanan' ? '' : 'hidden' }} bg-teal-50/50 p-6 rounded-xl border border-teal-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Dari Bulan *</label>
                                <select name="bulan" id="bulan"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl font-bold text-gray-800 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ old('bulan', $periodeSkp->bulan) == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Sampai Bulan (Opsional)</label>
                                <select name="bulan_akhir" id="bulan_akhir"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl font-bold text-gray-800 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <option value="">-- Hanya 1 Bulan --</option>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ old('bulan_akhir', $periodeSkp->bulan_akhir) == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-2 font-medium">Pilih jika periode ini mencakup rentang beberapa bulan.</p>
                            </div>
                        </div>
                    </div>

                    <div id="section-detail-semester" class="{{ old('tipe', $periodeSkp->tipe) == 'tahunan' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-xl border border-dashed border-gray-300">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-widest">Semester Genap</label>
                            <input type="text" name="semester_genap" id="semester_genap" value="{{ old('semester_genap', $periodeSkp->semester_genap) }}" readonly
                                class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-xl text-gray-600 font-bold focus:ring-0 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-widest">Semester Gasal</label>
                            <input type="text" name="semester_gasal" id="semester_gasal" value="{{ old('semester_gasal', $periodeSkp->semester_gasal) }}" readonly
                                class="w-full px-4 py-3 bg-gray-200 border border-gray-300 rounded-xl text-gray-600 font-bold focus:ring-0 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3 uppercase tracking-widest">Tanggal Mulai Berlaku *</label>
                            <input type="date" name="mulai" id="mulai" value="{{ old('mulai', $periodeSkp->mulai->format('Y-m-d')) }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3 uppercase tracking-widest">Tanggal Selesai Berlaku *</label>
                            <input type="date" name="selesai" id="selesai" value="{{ old('selesai', $periodeSkp->selesai->format('Y-m-d')) }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <hr class="border-gray-200 my-8">

                    <h3 class="text-xl font-black text-gray-800 border-l-4 border-orange-500 pl-3">Pengaturan Batas Waktu (Deadline)</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest mb-3">Batas Akhir Pengisian SKP *</label>
                            <input type="date" name="batas_pengisian" id="batas_pengisian"
                                value="{{ old('batas_pengisian', $periodeSkp->batas_pengisian?->format('Y-m-d')) }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-2 font-medium">Setelah tanggal ini, pegawai tidak bisa lagi menyimpan realisasi SKP-nya.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 uppercase tracking-widest mb-3">Batas Akhir Penilaian SKP *</label>
                            <input type="date" name="batas_penilaian" id="batas_penilaian"
                                value="{{ old('batas_penilaian', $periodeSkp->batas_penilaian?->format('Y-m-d')) }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-2 font-medium">Setelah tanggal ini, atasan tidak bisa lagi memverifikasi/mengubah nilai pegawai.</p>
                        </div>
                    </div>

                    <hr class="border-gray-200 my-8">

                    <div>
                        <label class="block text-sm font-black text-gray-700 uppercase tracking-widest mb-3">Status Publikasi Periode</label>
                        <select name="status"
                            class="w-full md:w-1/3 px-4 py-3 border border-gray-300 rounded-xl font-bold text-gray-800 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="draft" {{ old('status', $periodeSkp->status) == 'draft' ? 'selected' : '' }}>Draft (Tersembunyi)</option>
                            <option value="aktif" {{ old('status', $periodeSkp->status) == 'aktif' ? 'selected' : '' }}>Aktif (Bisa Diisi)</option>
                            <option value="selesai" {{ old('status', $periodeSkp->status) == 'selesai' ? 'selected' : '' }}>Selesai (Dikunci)</option>
                        </select>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4 pt-6 mt-8 border-t border-gray-100">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-4 px-6 rounded-xl font-black text-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all shadow-lg hover:-translate-y-1">
                            <i class="fas fa-save mr-2"></i> SIMPAN PERUBAHAN PERIODE
                        </button>
                        <a href="{{ route('superadmin.periode-skp.index') }}"
                            class="md:w-1/3 bg-gray-100 text-gray-700 py-4 px-6 rounded-xl font-bold hover:bg-gray-200 focus:ring-4 focus:ring-gray-200 transition-all text-center border border-gray-300">
                            BATAL
                        </a>
                    </div>
                </form>

                <script>
                    const formPeriode = document.getElementById('form-periode');
                    const tipeSelect = document.getElementById('tipe');
                    const tahunInput = document.getElementById('tahun');
                    const semesterSelect = document.getElementById('semester');
                    const bulanSelect = document.getElementById('bulan');

                    const sectionSemester = document.getElementById('section-semester');
                    const sectionBulanan = document.getElementById('section-bulanan');
                    const sectionDetailSemester = document.getElementById('section-detail-semester');

                    const genapInput = document.getElementById('semester_genap');
                    const gasalInput = document.getElementById('semester_gasal');

                    const inputMulai = document.getElementById('mulai');
                    const inputSelesai = document.getElementById('selesai');
                    const inputBatasPengisian = document.getElementById('batas_pengisian');
                    const inputBatasPenilaian = document.getElementById('batas_penilaian');
                    const bulanAkhirSelect = document.getElementById('bulan_akhir');

                    function toggleSections() {
                        const tipe = tipeSelect.value;
                        sectionSemester.classList.toggle('hidden', tipe !== 'semester');
                        sectionBulanan.classList.toggle('hidden', tipe !== 'bulanan');
                        sectionDetailSemester.classList.toggle('hidden', tipe !== 'tahunan');

                        // Reset required attributes
                        semesterSelect.required = (tipe === 'semester');
                        bulanSelect.required = (tipe === 'bulanan');

                        updateMonthConstraints();
                    }

                    tipeSelect.addEventListener('change', toggleSections);

                    function updateMonthConstraints() {
                        const tipe = tipeSelect.value;
                        const tahun = tahunInput.value;
                        const startMonth = bulanSelect.value;
                        const endMonth = bulanAkhirSelect.value || startMonth;

                        if (tipe === 'bulanan' && tahun && startMonth) {
                            const lastDayOfEndMonth = new Date(tahun, endMonth, 0).getDate();
                            
                            const minDate = `${tahun}-${String(startMonth).padStart(2, '0')}-01`;
                            const maxDate = `${tahun}-${String(endMonth).padStart(2, '0')}-${String(lastDayOfEndMonth).padStart(2, '0')}`;

                            // Mulai Berlaku selalu terkunci di bulan awal
                            inputMulai.min = minDate;
                            inputMulai.max = `${tahun}-${String(startMonth).padStart(2, '0')}-${new Date(tahun, startMonth, 0).getDate()}`;

                            inputSelesai.min = minDate;
                            inputSelesai.max = maxDate;
                            
                            // Batas pengisian dan penilaian bebas agar bisa melebihi bulan berjalan
                            [inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                                input.min = '';
                                input.max = '';
                            });
                        } else {
                            [inputMulai, inputSelesai, inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                                input.min = '';
                                input.max = '';
                            });
                            updateDateConstraints();
                        }
                    }

                    bulanSelect.addEventListener('change', updateMonthConstraints);
                    bulanAkhirSelect.addEventListener('change', updateMonthConstraints);

                    tahunInput.addEventListener('wheel', function(e) {
                        e.preventDefault();
                    }, {
                        passive: false
                    });

                    function updateSemesters() {
                        const tahun = parseInt(tahunInput.value);
                        if (tahun && tahun > 1900) {
                            genapInput.value = `Semester Genap ${tahun - 1}/${tahun}`;
                            gasalInput.value = `Semester Gasal ${tahun}/${tahun + 1}`;
                        } else {
                            genapInput.value = '';
                            gasalInput.value = '';
                        }
                    }

                    function syncYearToDates() {
                        const newYear = tahunInput.value;
                        if (!newYear || newYear.length < 4) return;

                        [inputMulai, inputSelesai, inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                            if (input.value) {
                                const parts = input.value.split('-');
                                if (parts.length === 3) {
                                    parts[0] = newYear;
                                    input.value = parts.join('-');
                                }
                            }
                        });
                    }

                    function syncDateToYear(e) {
                        const dateVal = e.target.value;
                        if (dateVal) {
                            const year = dateVal.split('-')[0];
                            if (year && year !== tahunInput.value) {
                                tahunInput.value = year;
                                updateSemesters();
                            }
                        }
                    }

                    function updateDateConstraints() {
                        const valMulai = inputMulai.value;
                        const valPengisian = inputBatasPengisian.value;

                        // Tanggal Selesai minimal adalah Tanggal Mulai
                        if (valMulai) {
                            inputSelesai.min = valMulai;
                        } else {
                            inputSelesai.removeAttribute('min');
                        }

                        // Batas Penilaian minimal adalah Batas Pengisian
                        if (valPengisian) {
                            inputBatasPenilaian.min = valPengisian;
                        } else {
                            inputBatasPenilaian.removeAttribute('min');
                        }
                    }

                    [inputMulai, inputSelesai, inputBatasPengisian, inputBatasPenilaian].forEach(input => {
                        input.addEventListener('change', (e) => {
                            syncDateToYear(e);
                            updateDateConstraints();
                            validateDates();
                        });
                    });

                    // Run on load
                    toggleSections();
                    updateSemesters();
                    updateDateConstraints();

                    tahunInput.addEventListener('input', () => {
                        updateSemesters();
                        syncYearToDates();
                        updateMonthConstraints();
                        updateDateConstraints();
                        validateDates();
                    });

                    function validateDates() {
                        // Tidak lagi mengubah nilai field secara diam-diam.
                        // Semua batasan (tidak boleh sebelum mulai / melewati selesai)
                        // divalidasi di server (controller) dengan pesan error yang jelas.
                        updateDateConstraints();
                    }
                </script>
            </div>
        </div>
    @endsection
</x-app-layout>
