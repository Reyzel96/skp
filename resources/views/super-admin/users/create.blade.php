<x-app-layout>
    @section('page-title', 'Tambah pegawai')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        @php
            /*
            | Tugas 25: pengelompokan jabatan utama tetap dari data DB ($jabatanUtamaRoles,
            | kategori='utama') — nama grup & urutan dihitung dari nama role, TANPA
            | daftar jabatan hard-code. Role yang strukturalnya sudah dipegang orang
            | lain disembunyikan (server tetap memvalidasi di usersStore).
            */
            $groupedRoles = [
                'Pimpinan / Universitas' => [],
                'Dekanat / Fakultas' => [],
            ];
            $prodiGroups = [];
            $otherGroups = ['Unit Pelaksana Fakultas (UPF)' => []];

            foreach ($jabatanUtamaRoles as $role) {
                $isDosenRole = str_contains(strtolower($role->name), 'dosen');
                $isAvailable = $isDosenRole || $role->users_count === 0;
                if (!$isAvailable) continue;

                $name = strtolower($role->name);

                if (str_contains($name, 'rektor') || str_contains($name, 'super admin')) {
                    $groupedRoles['Pimpinan / Universitas'][] = $role;
                } elseif (str_contains($name, 'dekan') || str_contains($name, 'wadek')) {
                    $groupedRoles['Dekanat / Fakultas'][] = $role;
                } elseif (str_contains($name, 'upf')) {
                    $otherGroups['Unit Pelaksana Fakultas (UPF)'][] = $role;
                } else {
                    // Asumsi prodi
                    $prodiName = str_ireplace(['kaprodi ', 'sekprodi ', 'kepala lab ', 'dosen '], '', $role->name);
                    $prodiName = ucwords(strtolower(trim($prodiName)));
                    $prodiGroups['Program Studi ' . $prodiName][] = $role;
                }
            }

            ksort($prodiGroups);
            $finalGroups = array_merge($groupedRoles, $prodiGroups, $otherGroups);

            // Rektor selalu paling atas pada grup "Pimpinan / Universitas"
            if (isset($finalGroups['Pimpinan / Universitas'])) {
                usort($finalGroups['Pimpinan / Universitas'], function($a, $b) {
                    $aIsRektor = str_contains(strtolower($a->name), 'rektor');
                    $bIsRektor = str_contains(strtolower($b->name), 'rektor');
                    if ($aIsRektor && !$bIsRektor) return -1;
                    if (!$bIsRektor && $aIsRektor) return 1;
                    return strcmp($a->name, $b->name);
                });
            }

            // Tugas 25 (#15): state awal input manual pangkat — tampil bila old
            // berisi teks manual TANPA pangkat_golongan_id terpilih.
            $oldPangkatId = old('pangkat_golongan_id');
            $oldPangkatManual = old('pangkat_golongan');
            $manualTerisi = $oldPangkatManual && (!$oldPangkatId || $oldPangkatId === 'manual');
            $adaError = $errors->any();
        @endphp

        <div class="w-full">
            {{-- Header 1 baris: judul kiri (tombol Batal/Simpan hanya di bawah form) --}}
            <div class="flex flex-wrap items-center gap-3 mb-2">
                <h1 class="text-xl font-bold text-gray-900">Tambah pegawai</h1>
            </div>
            <p class="text-[13px] text-gray-500 mb-4">Lengkapi data akun, jabatan, dan profil pegawai. Kolom bertanda * wajib diisi.</p>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-4 shadow-sm" role="alert">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold text-red-800 mb-1">Pegawai gagal disimpan</h3>
                            <ul class="text-sm text-red-700 space-y-1 font-medium list-disc ml-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pesan form invalid (HTML5) — via JS bila submit diblokir browser --}}
            <div id="pesan-form-invalid" class="hidden bg-amber-50 border-l-4 border-amber-500 rounded-lg p-4 mb-4 shadow-sm">
                <h3 class="font-bold text-amber-800 mb-0.5">Periksa kembali isian formulir</h3>
                <p class="text-sm text-amber-700 font-medium">Ada isian yang belum lengkap — kolom yang bermasalah telah ditandai dan difokuskan.</p>
            </div>

            {{-- Strip Ringkasan horizontal 1 baris (pola periode-skp) — MURNI TAMPILAN:
                 tidak punya atribut name, tidak di dalam form, tidak mungkin terkirim. --}}
            <div class="bg-white rounded-xl shadow border border-gray-200 px-4 py-2.5 mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm"
                aria-label="Ringkasan pegawai">
                <span class="text-xs font-medium text-gray-500">Nama</span>
                <span id="ringkasan-nama" class="font-semibold text-gray-800">{{ old('nama') ?: '—' }}</span>
                <span class="text-xs font-medium text-gray-500">Jabatan utama</span>
                <span id="ringkasan-jabatan" class="font-semibold text-gray-800">—</span>
                <span class="text-xs font-medium text-gray-500">Peran tendik</span>
                <span id="ringkasan-tendik" class="font-semibold text-gray-800">—</span>
                <span class="text-xs font-medium text-gray-500">Unit kerja</span>
                <span id="ringkasan-unit" class="font-semibold text-gray-800">{{ old('unit_kerja') ?: '—' }}</span>
            </div>

            {{-- Tugas 25: data-turbo="false" (submit tidak di-intercept Turbo Drive);
                 TIDAK ada onsubmit-confirm (penghalang submit dihapus, pola T16). --}}
            <form id="form-user-create" action="{{ route('superadmin.users.store') }}" method="POST"
                data-turbo="false" novalidate>
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_1fr] gap-4 items-start">

                    {{-- ============ KIRI: DATA PEGAWAI + AKUN ============ --}}
                    <div class="flex flex-col gap-4">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h2 class="text-base font-bold text-gray-800 mb-2">Data pegawai</h2>

                            <div class="space-y-3">
                                <div>
                                    <label for="field-nama" class="block text-[13px] font-medium text-gray-600 mb-1">Nama lengkap &amp; gelar *</label>
                                    <input type="text" name="nama" id="field-nama" value="{{ old('nama') }}" required maxlength="255"
                                        autocomplete="name" placeholder="Contoh: Dr. Budi Santoso, M.Kom."
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nama') border-red-500 @enderror"
                                        aria-describedby="error-nama">
                                    @error('nama')
                                        <p id="error-nama" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="field-nip" class="block text-[13px] font-medium text-gray-600 mb-1">NIP *</label>
                                        <input type="text" name="nip" id="field-nip" value="{{ old('nip') }}" maxlength="20" required
                                            inputmode="numeric" autocomplete="off" placeholder="18 digit angka"
                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nip') border-red-500 @enderror"
                                            aria-describedby="error-nip">
                                        @error('nip')
                                            <p id="error-nip" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="field-unit-kerja" class="block text-[13px] font-medium text-gray-600 mb-1">Unit kerja / penempatan</label>
                                        <input type="text" name="unit_kerja" id="field-unit-kerja" value="{{ old('unit_kerja') }}" maxlength="100"
                                            placeholder="Contoh: Fakultas Teknik"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="jenjang_jabatan_id" class="block text-[13px] font-medium text-gray-600 mb-1">Jenjang jabatan</label>                                        <select name="jenjang_jabatan_id" id="jenjang_jabatan_id"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">-- Pilih jenjang jabatan --</option>
                                            @foreach ($jenjangJabatans as $jenjang)
                                                <option value="{{ $jenjang->id }}"
                                                    {{ old('jenjang_jabatan_id') == $jenjang->id ? 'selected' : '' }}>
                                                    {{ $jenjang->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($jenjangJabatans->isEmpty())
                                            <p class="text-xs text-amber-600 font-medium mt-1.5">Belum ada data jenjang jabatan. Tambahkan terlebih dahulu.</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label for="pangkat_golongan_id" class="block text-[13px] font-medium text-gray-600 mb-1">Pangkat / golongan</label>
                                        {{-- Tugas 25 (#2): restore old('pangkat_golongan_id') server-side.
                                             Disabled selama belum ada jenjang terpilih ATAU tidak ada old id
                                             (nilai disabled tidak terkirim — sama seperti perilaku lama). --}}
                                        <select name="pangkat_golongan_id" id="pangkat_golongan_id"
                                            class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white @error('pangkat_golongan_id') border-red-500 @enderror"
                                            @if (!old('jenjang_jabatan_id') && !$oldPangkatId) disabled @endif
                                            aria-describedby="hint-pangkat @error('pangkat_golongan_id') error-pangkat-id @enderror">
                                            <option value="">-- Pilih dulu jenjang jabatan --</option>
                                            @if ($oldPangkatId && $oldPangkatId !== 'manual')
                                                <option value="{{ $oldPangkatId }}" selected>(terpilih sebelumnya — id {{ $oldPangkatId }})</option>
                                            @endif
                                            @if ($oldPangkatId === 'manual')
                                                <option value="manual" selected>Lainnya / input manual</option>
                                            @endif
                                        </select>
                                        <p id="hint-pangkat" class="text-xs text-gray-500 mt-1">Pilih jenjang jabatan dahulu untuk memunculkan daftar pangkat.</p>
                                        @error('pangkat_golongan_id')
                                            <p id="error-pangkat-id" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Input manual pangkat (Tugas 25 #15: tampil bila old manual terisi) --}}
                                <div id="pangkat_golongan_manual_wrapper" class="{{ $manualTerisi ? '' : 'hidden' }}">
                                    <label for="pangkat_golongan_manual" class="block text-[13px] font-medium text-gray-600 mb-1">Pangkat / golongan (manual)</label>
                                    <input type="text" name="pangkat_golongan" id="pangkat_golongan_manual"
                                        value="{{ $oldPangkatManual }}" maxlength="100"
                                        placeholder="Contoh: Penata Tk. I / III/d"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('pangkat_golongan') border-red-500 @enderror"
                                        @error('pangkat_golongan') aria-invalid="true" @enderror>
                                    @error('pangkat_golongan')
                                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="jabatan_fungsional" class="block text-[13px] font-medium text-gray-600 mb-1">Jabatan fungsional</label>
                                    <select name="jabatan_fungsional" id="jabatan_fungsional"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">-- Tidak ada / non-dosen --</option>
                                        @foreach ($jabatanFungsional as $jf)
                                            <option value="{{ $jf }}"
                                                {{ old('jabatan_fungsional') == $jf ? 'selected' : '' }}>{{ $jf }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h2 class="text-base font-bold text-gray-800 mb-2">Akun</h2>

                            <div class="space-y-3">
                                <div>
                                    <label for="field-email" class="block text-[13px] font-medium text-gray-600 mb-1">Email kampus *</label>
                                    <input type="email" name="email" id="field-email" value="{{ old('email') }}" required
                                        autocomplete="email" placeholder="nama@kampus.ac.id"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                                        aria-describedby="error-email">
                                    @error('email')
                                        <p id="error-email" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="field-password" class="block text-[13px] font-medium text-gray-600 mb-1">Password *</label>
                                        <div class="relative" x-data="{ showPassword: false }">
                                            <input type="password" :type="showPassword ? 'text' : 'password'" name="password" id="field-password"
                                                required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"
                                                class="w-full h-10 pl-3 pr-10 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                                                aria-describedby="error-password">
                                            <button type="button" @click="showPassword = !showPassword" aria-label="Tampilkan atau sembunyikan password"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors">
                                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <p id="error-password" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="field-password-confirmation" class="block text-[13px] font-medium text-gray-600 mb-1">Konfirmasi password *</label>
                                        <div class="relative" x-data="{ showPassword: false }">
                                            <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="field-password-confirmation"
                                                required minlength="8" autocomplete="new-password" placeholder="Ulangi password"
                                                class="w-full h-10 pl-3 pr-10 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <button type="button" @click="showPassword = !showPassword" aria-label="Tampilkan atau sembunyikan konfirmasi password"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors">
                                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ KANAN: JABATAN DAN PERAN ============ --}}
                    <div class="flex flex-col gap-4">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h2 class="text-base font-bold text-gray-800 mb-2">Jabatan dan peran</h2>

                            <div class="space-y-3">
                                <div>
                                    <label for="role_utama" class="block text-[13px] font-medium text-gray-600 mb-1">Jabatan utama</label>
                                    <p class="text-xs text-gray-500 mb-1">Jabatan struktural (selain Dosen) hanya boleh dipegang 1 orang.</p>
                                    <select name="role_utama" id="role_utama"
                                        class="w-full h-10 px-3 border border-gray-300 rounded-lg text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white @error('role_utama') border-red-500 @enderror"
                                        aria-describedby="error-role-utama">
                                        <option value="">-- Tidak ada jabatan utama (hanya tendik) --</option>
                                        @foreach ($finalGroups as $groupName => $rolesInGroup)
                                            @if(count($rolesInGroup) > 0)
                                                <optgroup label="{{ $groupName }}">
                                                    @foreach ($rolesInGroup as $role)
                                                        <option value="{{ $role->name }}"
                                                            {{ old('role_utama') == $role->name ? 'selected' : '' }}>
                                                            {{ ucwords(strtolower($role->name)) }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('role_utama')
                                        <p id="error-role-utama" class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="block text-[13px] font-medium text-gray-600">Peran tambahan / tendik (opsional)</span>
                                        <span id="lencana-tendik" class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">0 dipilih</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-1.5">Pengguna dapat merangkap lebih dari satu peran tenaga kependidikan.</p>

                                    @if ($peranTendikRoles->isEmpty())
                                        <p class="text-xs text-amber-600 font-medium">Belum ada peran tendik. Tambahkan di menu Kelola Jabatan.</p>
                                    @else
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1.5">
                                            @foreach ($peranTendikRoles as $role)
                                                <label data-label-tendik
                                                    class="inline-flex items-center gap-2 p-2 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                                    <input type="checkbox" name="roles_tendik[]" value="{{ $role->name }}"
                                                        data-tendik-checkbox
                                                        class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 flex-shrink-0"
                                                        {{ is_array(old('roles_tendik')) && in_array($role->name, old('roles_tendik')) ? 'checked' : '' }}>
                                                    <span class="text-xs font-medium text-gray-700 leading-snug">{{ $role->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @error('roles_tendik')
                                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol di bawah form (desktop), rata kanan sejajar tepi kartu --}}
                <div class="hidden md:flex gap-2 justify-end mt-4 pb-2">
                    <a href="{{ route('superadmin.users.index') }}"
                        class="bg-white text-gray-700 h-10 px-4 rounded-lg font-semibold hover:bg-gray-100 transition-all text-center border border-gray-300 inline-flex items-center">
                        Batal
                    </a>
                    <button type="submit" form="form-user-create" data-tombol-simpan
                        class="bg-blue-600 text-white h-10 px-5 rounded-lg font-semibold hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all shadow inline-flex items-center">
                        Simpan
                    </button>
                </div>
            </form>

            {{-- Bar aksi sticky mobile (di luar form, tombol menunjuk form-user-create);
                 padding bawah konten mencegah bar menutupi field terakhir. --}}
            <div class="md:hidden sticky bottom-0 -mx-6 px-6 py-3 bg-white/95 backdrop-blur border-t border-gray-200 flex gap-2 z-10">
                <a href="{{ route('superadmin.users.index') }}"
                    class="flex-1 bg-white text-gray-700 h-10 rounded-lg font-semibold hover:bg-gray-100 transition-all text-center border border-gray-300 inline-flex items-center justify-center">
                    Batal
                </a>
                <button type="submit" form="form-user-create" data-tombol-simpan
                    class="flex-1 bg-blue-600 text-white h-10 rounded-lg font-semibold hover:bg-blue-700 transition-all shadow inline-flex items-center justify-center">
                    Simpan
                </button>
            </div>
            <div class="h-2 md:h-0"></div>
        </div>
    @endsection

    @push('scripts')
    <script>
        // ===== Tugas 25: init kompatibel Turbo Drive =====
        // Pemicu ganda: DOMContentLoaded (reload penuh) + eksekusi langsung
        // (navigasi Turbo — script inline dieksekusi ulang pada body baru).
        // Guard dataset di body + cleanup di turbo:before-cache.
        function initUserCreateForm() {
            if (document.body.dataset.userCreateFormInited) return;
            document.body.dataset.userCreateFormInited = '1';

            var form = document.getElementById('form-user-create');
            if (!form) return;

            // ===== Fitur 1: mutual exclusivity Jabatan utama <-> Peran tendik =====
            // (perilaku dipertahankan persis dari versi lama)
            try {
                var roleUtamaSelect = document.getElementById('role_utama');
                var tendikCheckboxes = document.querySelectorAll('[data-tendik-checkbox]');
                var lencanaTendik = document.getElementById('lencana-tendik');

                function jumlahTendikTerpilih() {
                    var n = 0;
                    tendikCheckboxes.forEach(function(cb) { if (cb.checked) n++; });
                    return n;
                }

                function perbaruiLencana() {
                    if (!lencanaTendik) return;
                    var n = jumlahTendikTerpilih();
                    lencanaTendik.textContent = n + ' dipilih';
                }

                function updateMutualExclusivity() {
                    var isRoleUtamaSelected = roleUtamaSelect.value !== '';
                    var isAnyTendikChecked = jumlahTendikTerpilih() > 0;

                    tendikCheckboxes.forEach(function(cb) {
                        cb.disabled = isRoleUtamaSelected;
                        cb.closest('label').classList.toggle('opacity-50', isRoleUtamaSelected);
                        cb.closest('label').classList.toggle('cursor-not-allowed', isRoleUtamaSelected);
                    });

                    roleUtamaSelect.disabled = isAnyTendikChecked;
                    roleUtamaSelect.classList.toggle('bg-gray-100', isAnyTendikChecked);
                    roleUtamaSelect.classList.toggle('cursor-not-allowed', isAnyTendikChecked);

                    perbaruiLencana();
                }

                roleUtamaSelect.addEventListener('change', function() {
                    if (this.value !== '') {
                        tendikCheckboxes.forEach(function(cb) { cb.checked = false; });
                    }
                    updateMutualExclusivity();
                    perbaruiRingkasan();
                });

                tendikCheckboxes.forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        if (this.checked) {
                            roleUtamaSelect.value = '';
                        }
                        updateMutualExclusivity();
                        perbaruiRingkasan();
                    });
                });

                // Jalankan saat load (menangani old data)
                updateMutualExclusivity();
            } catch (e) {
                console.error('users/create: mutual exclusivity gagal', e);
            }

            // ===== Fitur 2: dropdown dependen Jenjang -> Pangkat/Golongan =====
            // Tugas 25 (#11): saat init JANGAN menghapus nilai old/input manual;
            // reset hanya saat user benar-benar mengganti jenjang.
            try {
                var jenjangSelect = document.getElementById('jenjang_jabatan_id');
                var pangkatSelect = document.getElementById('pangkat_golongan_id');
                var manualWrapper = document.getElementById('pangkat_golongan_manual_wrapper');
                var manualInput = document.getElementById('pangkat_golongan_manual');

                function setManualTerlihat(terlihat) {
                    manualWrapper.classList.toggle('hidden', !terlihat);
                    manualInput.required = terlihat;
                }

                jenjangSelect.addEventListener('change', function() {
                    var jenjangId = this.value;
                    // Reset HANYA saat penggantian oleh user (bukan saat init)
                    pangkatSelect.innerHTML = '<option value="">-- Pilih pangkat / golongan --</option>';
                    setManualTerlihat(false);
                    manualInput.value = '';

                    if (jenjangId) {
                        pangkatSelect.disabled = false;
                        var urlPangkatBase = '{{ route("lookup.pangkat-golongan.by-jenjang", ["jenjang_jabatan_id" => "__ID__"]) }}'.replace('__ID__', jenjangId);
                fetch(urlPangkatBase)
                            .then(function(r) {
                                if (!r.ok) throw new Error('HTTP ' + r.status);
                                return r.json();
                            })
                            .then(function(data) {
                                data.forEach(function(pg) {
                                    var opt = document.createElement('option');
                                    opt.value = pg.id;
                                    opt.textContent = pg.kode_pangkat + ' — ' + pg.nama_pangkat;
                                    pangkatSelect.appendChild(opt);
                                });
                                var manualOpt = document.createElement('option');
                                manualOpt.value = 'manual';
                                manualOpt.textContent = 'Lainnya / input manual';
                                pangkatSelect.appendChild(manualOpt);
                            })
                            .catch(function(err) {
                                console.error('users/create: gagal memuat pangkat', err);
                                var opt = document.createElement('option');
                                opt.value = 'manual';
                                opt.textContent = 'Gagal memuat — gunakan input manual';
                                pangkatSelect.appendChild(opt);
                                setManualTerlihat(true);
                            });
                    } else {
                        pangkatSelect.disabled = true;
                        pangkatSelect.innerHTML = '<option value="">-- Pilih dulu jenjang jabatan --</option>';
                    }
                });

                pangkatSelect.addEventListener('change', function() {
                    setManualTerlihat(this.value === 'manual');
                    if (this.value !== 'manual') manualInput.value = '';
                });

                // Init: bila old jenjang terpilih tapi select pangkat disabled
                // (karena tidak ada old id), aktifkan supaya user bisa memilih.
                if (jenjangSelect.value && pangkatSelect.disabled) {
                    pangkatSelect.disabled = false;
                }
                // Bila old manual terlihat, pastikan required sinkron.
                if (!manualWrapper.classList.contains('hidden')) {
                    manualInput.required = true;
                }
            } catch (e) {
                console.error('users/create: dropdown pangkat gagal', e);
            }

            // ===== Fitur 3: kartu Ringkasan (read-only, bukan field form) =====
            function perbaruiRingkasan() {
                try {
                    var elNama = document.getElementById('ringkasan-nama');
                    var elJabatan = document.getElementById('ringkasan-jabatan');
                    var elTendik = document.getElementById('ringkasan-tendik');
                    var elUnit = document.getElementById('ringkasan-unit');
                    var nama = document.getElementById('field-nama');
                    var unit = document.getElementById('field-unit-kerja');

                    if (elNama && nama) elNama.textContent = nama.value.trim() || '—';
                    if (elUnit && unit) elUnit.textContent = unit.value.trim() || '—';
                    if (elJabatan) {
                        var opt = roleUtamaSelect.options[roleUtamaSelect.selectedIndex];
                        elJabatan.textContent = roleUtamaSelect.value ? (opt ? opt.textContent : '—') : '—';
                    }
                    if (elTendik) {
                        var dipilih = [];
                        document.querySelectorAll('[data-tendik-checkbox]:checked').forEach(function(cb) {
                            dipilih.push(cb.closest('label').querySelector('span').textContent.trim());
                        });
                        elTendik.textContent = dipilih.length ? dipilih.join(', ') : '—';
                    }
                } catch (e) {
                    console.error('users/create: ringkasan gagal', e);
                }
            }

            try {
                ['field-nama', 'field-unit-kerja'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.addEventListener('input', perbaruiRingkasan);
                });
                perbaruiRingkasan();
            } catch (e) {
                console.error('users/create: init ringkasan gagal', e);
            }

            // ===== Fitur 4: pesan invalid + fokus field pertama (T16 pola) =====
            try {
                form.addEventListener('invalid', function() {
                    var kotak = document.getElementById('pesan-form-invalid');
                    if (kotak) {
                        kotak.classList.remove('hidden');
                        kotak.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    var invalid = form.querySelector(':invalid');
                    if (invalid) {
                        invalid.focus({ preventScroll: false });
                        invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, true);
            } catch (e) {
                console.error('users/create: handler invalid gagal', e);
            }

            // ===== Fitur 5: anti double-click DI EVENT 'submit' (T16 pola) =====
            // Tanpa preventDefault; disable ditunda setTimeout(...,0); re-enable 3 dtk.
            try {
                form.addEventListener('submit', function() {
                    document.querySelectorAll('[data-tombol-simpan]').forEach(function(btn) {
                        if (btn.dataset.mengirim === '1') return;
                        btn.dataset.mengirim = '1';
                        setTimeout(function() {
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
                        }, 0);
                        setTimeout(function() {
                            btn.disabled = false;
                            btn.dataset.mengirim = '';
                        }, 3000);
                    });
                });
            } catch (e) {
                console.error('users/create: anti double-click gagal', e);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUserCreateForm);
        } else {
            initUserCreateForm();
        }
        document.addEventListener('turbo:before-cache', function() {
            delete document.body.dataset.userCreateFormInited;
        });
    </script>
    @endpush
</x-app-layout>
