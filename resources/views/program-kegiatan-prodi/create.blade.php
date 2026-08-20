<x-app-layout>
    @section('page-title', 'Buat Usulan Prodi')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <style>
        .table-input {
            width: 100%; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 12px;
            background: #f9fafb; border-radius: 8px; transition: all 0.2s ease;
            font-weight: 700; line-height: 1.4; display: block;
        }
        .table-input:focus { outline: none; border-color: #3b82f6; background-color: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        
        textarea.table-input { resize: none; overflow: hidden; min-height: 80px; }

        /* Hide spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-6">
        <div>
            <a href="{{ route('kegiatan-prodi.my-pengajuan') }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-widest flex items-center gap-2 mb-4 transition-all text-left">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
            <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight text-left">Buat <span class="text-blue-600">Usulan Prodi</span></h2>
            <p class="text-gray-500 text-sm font-bold mt-1 uppercase tracking-widest text-left">Master: {{ $kegiatan->nama_kegiatan }}</p>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-indigo-50 px-4 py-2 rounded-xl border border-indigo-100 flex flex-col items-center min-w-[100px]">
                <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">TA</span>
                <span class="text-sm font-black text-indigo-800">{{ $kegiatan->tahun_anggaran }}</span>
            </div>
            <div class="bg-blue-50 px-4 py-2 rounded-xl border border-blue-100 flex flex-col items-center min-w-[100px]">
                <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest">Semester</span>
                <span class="text-sm font-black text-blue-800">{{ $kegiatan->semester ?? '-' }}</span>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-5 rounded-2xl shadow-sm mb-8 animate-fadeIn">
            <div class="flex items-center gap-3 mb-2 text-red-800 font-bold">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 class="text-base uppercase tracking-tight">Gagal Mengirim Usulan</h3>
            </div>
            <ul class="list-disc ml-8 text-xs text-red-600 font-black space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- PERBAIKAN LOGIKA: Deteksi Prodi & Batas Anggaran --}}
    @php
        $user = auth()->user();
        $detectedProdiBase = '';
        foreach ($user->getRoleNames() as $role) {
            $lowerRole = strtolower($role);
            if (str_contains($lowerRole, 'informatika')) $detectedProdiBase = 'Informatika';
            elseif (str_contains($lowerRole, 'sipil')) $detectedProdiBase = 'Sipil';
            elseif (str_contains($lowerRole, 'mesin')) $detectedProdiBase = 'Mesin';
            elseif (str_contains($lowerRole, 'perkapalan')) $detectedProdiBase = 'Perkapalan';
            elseif (str_contains($lowerRole, 'industri')) $detectedProdiBase = 'Industri';
            elseif (str_contains($lowerRole, 'elektro') || str_contains($lowerRole, 'eletreo')) $detectedProdiBase = 'Eletreo';
            elseif (str_contains($lowerRole, 'arsitek')) $detectedProdiBase = 'Arsite'; 
        }

        // Cari Key yang tepat di alokasi_custom (untuk menangani typo di DB seperti 'Arsiterktur')
        $exactProdiName = '';
        $customAmount = 0;
        if ($detectedProdiBase && $kegiatan->alokasi_custom) {
            foreach($kegiatan->alokasi_custom as $pName => $val) {
                if (str_contains(strtolower($pName), strtolower($detectedProdiBase))) {
                    $exactProdiName = $pName;
                    $customAmount = (int)$val;
                    break;
                }
            }
        }

        // Tentukan batas anggaran final
        $batasAnggaranProdi = ($kegiatan->tipe_alokasi === 'Custom') 
            ? $customAmount 
            : ($kegiatan->per_prodi > 0 ? (int)$kegiatan->per_prodi : (int)$kegiatan->anggaran_pagu);
            
        // Nama display untuk box informasi
        $displayProdiInfo = $exactProdiName ?: ($detectedProdiBase ?: 'Prodi Anda');
    @endphp

    <div class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-2xl flex items-center gap-5 mb-8 shadow-sm">
        <div class="bg-amber-100 p-3 rounded-xl text-amber-600 shadow-inner"><i class="fas fa-coins text-xl"></i></div>
        <div class="text-left">
            <h4 class="text-sm font-black text-amber-900 uppercase tracking-widest">Alokasi Anggaran Prodi</h4>
            <p class="text-[11px] font-bold text-amber-700 uppercase mt-1">
                Batas Maksimal untuk <span class="text-amber-900">{{ $displayProdiInfo }}</span>: 
                <span class="bg-amber-200 px-2 py-0.5 rounded text-amber-950 font-mono font-black">Rp {{ number_format($batasAnggaranProdi, 0, ',', '.') }}</span>
            </p>
        </div>
    </div>

    <form action="{{ route('kegiatan-prodi.store', $kegiatan->id) }}" method="POST" enctype="multipart/form-data" x-data="{ proposalType: 'file' }">
        @csrf

        {{-- Hidden Fields (Mandatory Background Data) --}}
        <input type="hidden" name="semester" value="{{ $kegiatan->semester }}">
        <input type="hidden" name="tahun_anggaran" value="{{ $kegiatan->tahun_anggaran }}">
        <input type="hidden" name="tahun_akademik" value="{{ $kegiatan->tahun_akademik }}">
        <input type="hidden" name="indikator" value="{{ $kegiatan->indikator }}">
        <input type="hidden" name="target_capaian" value="{{ $kegiatan->target_capaian }}">
        <input type="hidden" name="waktu_pelaksanaan" value="{{ $kegiatan->waktu_pelaksanaan }}">

        {{-- TABLE FORM --}}
        <div class="bg-white border border-gray-200 rounded-3xl shadow-xl overflow-hidden mb-12">
            <div class="bg-gray-50 border-b border-gray-200 px-8 py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-list-alt text-blue-600"></i>
                    <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Input Usulan Program (Format Tabel)</h3>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="bg-gray-50/50 text-[10px] font-black text-gray-500 uppercase tracking-tight border-b border-gray-200">
                            <th class="px-6 py-4 w-[180px] border-r border-gray-200">Program Studi</th>
                            <th class="px-6 py-4 min-w-[250px] border-r border-gray-200 bg-blue-50/30 text-blue-800">Nama Usulan Program</th>
                            <th class="px-6 py-4 w-[180px] border-r border-gray-200">Tanggal Implementasi</th>
                            <th class="px-6 py-4 min-w-[300px] border-r border-gray-200">Indikator Keberhasilan</th>
                            <th class="px-6 py-4 w-[200px] text-right bg-emerald-50/30 text-emerald-800">Usulan Anggaran (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr class="align-top">
                            {{-- 1. Prodi (Read-only) --}}
                            <td class="px-3 py-4 border-r border-gray-100">
                                @php
                                    $selectedProdiId = null;
                                    $foundProdi = $prodis->filter(function($p) use ($detectedProdiBase) {
                                        return str_contains(strtolower($p->nama), strtolower($detectedProdiBase));
                                    })->first();
                                    if ($foundProdi) $selectedProdiId = $foundProdi->id;
                                @endphp
                                <div class="relative">
                                    <input type="text" class="table-input bg-gray-100 text-gray-500 cursor-not-allowed uppercase" value="{{ $displayProdiInfo }}" readonly>
                                    <input type="hidden" name="prodi_id" value="{{ $selectedProdiId }}">
                                    <i class="fas fa-university absolute right-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                </div>
                            </td>

                            {{-- 2. Nama Usulan --}}
                            <td class="px-3 py-4 border-r border-gray-100 bg-blue-50/5">
                                <textarea name="nama_kegiatan_prodi" class="table-input border-blue-100 focus:border-blue-500 text-blue-900" 
                                    placeholder="Masukkan nama usulan..." required>{{ old('nama_kegiatan_prodi', $kegiatan->nama_kegiatan) }}</textarea>
                            </td>

                            {{-- 3. Tanggal Implementasi --}}
                            <td class="px-3 py-4 border-r border-gray-100">
                                <div class="relative">
                                    <input type="date" name="tanggal_implementasi" value="{{ old('tanggal_implementasi', date('Y-m-d')) }}" 
                                        class="table-input cursor-pointer" required>
                                </div>
                            </td>

                            {{-- 4. Indikator Keberhasilan --}}
                            <td class="px-3 py-4 border-r border-gray-100">
                                <textarea name="indikator_keberhasilan" class="table-input" 
                                    placeholder="Tuliskan indikator pencapaian..." required>{{ old('indikator_keberhasilan') }}</textarea>
                            </td>

                            {{-- 5. Usulan Anggaran --}}
                            <td class="px-3 py-4 bg-emerald-50/10">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-xs">Rp</span>
                                    <input type="number" name="anggaran_usulan_prodi" value="{{ old('anggaran_usulan_prodi') }}" 
                                        class="table-input pl-9 text-right text-emerald-800 border-emerald-100 font-black font-mono" 
                                        placeholder="0" required max="{{ $batasAnggaranProdi }}">
                                </div>
                                <div class="mt-2 text-[8px] font-bold text-emerald-600 uppercase tracking-tighter text-right italic">
                                    Maks: Rp {{ number_format($batasAnggaranProdi, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-8 py-3 border-t border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold italic text-left">* Seluruh data di atas wajib diisi sesuai dengan usulan program kerja program studi.</p>
            </div>
        </div>

        {{-- PROPOSAL SECTION --}}
        <div class="bg-white border border-gray-200 rounded-[2.5rem] p-10 md:p-12 shadow-sm mb-12">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div class="flex items-center gap-5 text-left">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 shadow-inner"><i class="fas fa-file-alt text-2xl"></i></div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Dokumen Proposal</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Pilih metode penyusunan proposal kegiatan</p>
                    </div>
                </div>
                <div class="flex bg-gray-100 p-1.5 rounded-2xl border border-gray-200 shadow-inner w-full md:w-auto">
                    <button type="button" @click="proposalType = 'file'" :class="proposalType === 'file' ? 'bg-white text-blue-600 shadow-md' : 'text-gray-500'" class="flex-1 md:flex-none px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Upload PDF</button>
                    <button type="button" @click="proposalType = 'manual'" :class="proposalType === 'manual' ? 'bg-white text-blue-600 shadow-md' : 'text-gray-500'" class="flex-1 md:flex-none px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Ketik Manual</button>
                </div>
            </div>

            <input type="hidden" name="proposal_type" :value="proposalType">

            <div x-show="proposalType === 'file'" x-transition class="animate-in fade-in slide-in-from-bottom-4 duration-300">
                <div class="relative group">
                    <input id="proposalInput" type="file" name="proposal_file" accept="application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :required="proposalType === 'file'">
                    <div id="uploadBox" class="bg-gray-50 border-3 border-dashed border-gray-200 rounded-3xl p-16 text-center transition-all group-hover:border-blue-400 group-hover:bg-blue-50/50">
                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 mb-4 transition-colors group-hover:text-blue-500"></i>
                        <p id="uploadText" class="text-sm font-black text-gray-500 uppercase tracking-widest group-hover:text-blue-700">Tarik file atau klik di sini</p>
                        <p class="text-[10px] font-bold text-gray-400 mt-2 uppercase tracking-tighter">Hanya PDF (Maks. 10MB)</p>
                        <p id="fileName" class="text-xs text-emerald-600 mt-6 font-black uppercase hidden bg-emerald-50 px-4 py-2 rounded-full inline-block"></p>
                    </div>
                </div>
            </div>

            <div x-show="proposalType === 'manual'" x-transition class="animate-in fade-in slide-in-from-bottom-4 duration-300" style="display:none;">
                <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100 flex items-center gap-4 mb-6 text-left">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-blue-600 shadow-sm shrink-0"><i class="fas fa-lightbulb"></i></div>
                    <p class="text-[11px] text-blue-800 font-bold uppercase tracking-tight leading-relaxed">Gunakan editor di bawah untuk menyusun narasi usulan Anda secara langsung.</p>
                </div>
                <input id="proposal_manual" type="hidden" name="proposal_manual" value="{{ old('proposal_manual') }}">
                <trix-editor input="proposal_manual" placeholder="Tuliskan detail usulan program di sini..."></trix-editor>
            </div>
        </div>

        <div class="flex justify-center mb-20">
            <button type="submit" class="w-full max-w-md bg-blue-600 hover:bg-blue-700 text-white py-6 rounded-[2rem] font-black text-sm uppercase tracking-widest shadow-2xl shadow-blue-600/20 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-4">
                <i class="fas fa-paper-plane"></i> Kirim Usulan Program
            </button>
        </div>
    </form>

    <script>
        const input = document.getElementById('proposalInput');
        const fileNameText = document.getElementById('fileName');
        const uploadText = document.getElementById('uploadText');
        const uploadBox = document.getElementById('uploadBox');

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.type !== 'application/pdf') { alert('File harus PDF!'); this.value = ''; return; }
            if (file.size > 10 * 1024 * 1024) { alert('Ukuran file maksimal 10MB!'); this.value = ''; return; }
            fileNameText.classList.remove('hidden');
            fileNameText.innerText = "✓ " + file.name;
            uploadText.innerText = "File siap diupload";
            uploadBox.classList.add('border-emerald-500', 'bg-emerald-50/30');
        });

        document.addEventListener('wheel', function(event) { if (document.activeElement.type === 'number') { document.activeElement.blur(); } }, { passive: false });
    </script>
    @endsection
</x-app-layout>
