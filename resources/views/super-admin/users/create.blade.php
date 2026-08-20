<x-app-layout>
    @section('page-title', 'Tambah User Baru')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="max-w-6xl mx-auto p-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6">
                    <strong>❌ Error:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Tambah Pegawai</h1>
                <p class="text-gray-600 mb-8">Lengkapi data autentikasi dan profil akademik pegawai.</p>

                <form action="{{ route('superadmin.users.store') }}" method="POST" onsubmit="return confirm('Konfirmasi Penyimpanan\n\nApakah Anda yakin sudah mengecek seluruh data dengan teliti?');">
                    @csrf

                    {{-- BAGIAN 1: JABATAN UTAMA & PERAN TAMBAHAN --}}
                    <div class="space-y-8 mb-8 pb-6 border-b border-gray-100">
                        <div>
                            <label class="block text-base font-bold text-gray-800 mb-2">🎯 Jabatan Utama</label>
                            <p class="text-sm text-gray-500 mb-2">Pilih jabatan struktural atau jabatan akademik utama (Dosen). Jabatan struktural (selain Dosen) hanya boleh dipegang oleh 1 orang.</p>

                            @php
                                $groupedRoles = [
                                    'Pimpinan / Universitas' => [],
                                    'Dekanat / Fakultas' => [],
                                ];
                                $prodiGroups = [];
                                $otherGroups = ['Unit Pelaksana Fakultas (UPF)' => []];

                                foreach ($jabatanUtamaRoles as $role) {
                                    $isDosenRole = str_contains(strtolower($role->name), 'dosen');
                                    $isAvailable = $isDosenRole || $role->users->isEmpty();
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

                                // Make sure Rektor is at the absolute top of the "Pimpinan / Universitas"
                                if (isset($finalGroups['Pimpinan / Universitas'])) {
                                    usort($finalGroups['Pimpinan / Universitas'], function($a, $b) {
                                        $aIsRektor = str_contains(strtolower($a->name), 'rektor');
                                        $bIsRektor = str_contains(strtolower($b->name), 'rektor');
                                        if ($aIsRektor && !$bIsRektor) return -1;
                                        if (!$aIsRektor && $bIsRektor) return 1;
                                        return strcmp($a->name, $b->name);
                                    });
                                }
                            @endphp

                            <select name="role_utama"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-semibold text-sm">
                                <option value="">-- Tidak Ada Jabatan Utama (Hanya Tendik) --</option>
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
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Peran Tambahan / Tendik (Opsional)</h3>
                            <p class="text-sm text-gray-600 mb-4">Seorang pengguna dapat merangkap lebih dari satu peran tenaga kependidikan di bawah ini.</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach ($peranTendikRoles as $role)
                                    <label
                                        class="inline-flex items-center p-3 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input type="checkbox" name="roles_tendik[]" value="{{ $role->name }}"
                                            class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                            {{ is_array(old('roles_tendik')) && in_array($role->name, old('roles_tendik')) ? 'checked' : '' }}>
                                        <span class="ml-3 text-sm font-medium text-gray-700">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roles_tendik')
                                <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- BAGIAN 2: DATA LOGIN --}}
                    <div class="mb-8">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email Kampus *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                                @error('email')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Password *</label>
                                <div class="relative" x-data="{ showPassword: false }">
                                    <input type="password" :type="showPassword ? 'text' : 'password'" name="password" required minlength="8"
                                        class="w-full p-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                                        <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password *</label>
                                <div class="relative" x-data="{ showPassword: false }">
                                    <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" required minlength="8"
                                        class="w-full p-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                                        <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BAGIAN 3: PROFIL PEGAWAI --}}
                    <div class="mb-8 pt-6 border-t border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap & Gelar *</label>
                                <input type="text" name="nama" value="{{ old('nama') }}" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">NIP *</label>
                                <input type="text" name="nip" value="{{ old('nip') }}" maxlength="20" required
                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pangkat / Golongan</label>
                                <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan') }}"
                                    placeholder="Contoh: Penata Tk. I / III/d"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Unit Kerja / Penempatan</label>
                                <input type="text" name="unit_kerja" value="{{ old('unit_kerja') }}"
                                    placeholder="Contoh: Fakultas Teknik / Pusat Komputer"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- BAGIAN 4: JABATAN FUNGSIONAL --}}
                    <div class="space-y-8 pt-6 border-t border-gray-100">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jabatan Fungsional</label>
                            <select name="jabatan_fungsional"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Tidak Ada / Non-Dosen --</option>
                                @foreach ($jabatanFungsional as $jf)
                                    <option value="{{ $jf }}"
                                        {{ old('jabatan_fungsional') == $jf ? 'selected' : '' }}>{{ $jf }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-8"></div>

                    {{-- BUTTONS --}}
                    <div class="flex gap-4 pt-4 border-t border-gray-200">
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white py-4 px-8 rounded-xl text-lg font-bold shadow-lg hover:bg-blue-700 transition-all duration-200">
                            SIMPAN PEGAWAI
                        </button>
                        <a href="{{ route('superadmin.users.index') }}"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-4 px-8 rounded-xl text-lg font-bold text-center transition-all duration-200">
                            BATAL
                        </a>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleUtamaSelect = document.querySelector('select[name="role_utama"]');
            const tendikCheckboxes = document.querySelectorAll('input[name="roles_tendik[]"]');
            const tendikSection = roleUtamaSelect.closest('form').querySelector('.bg-yellow-50');

            function updateMutualExclusivity() {
                const isRoleUtamaSelected = roleUtamaSelect.value !== "";
                let isAnyTendikChecked = false;
                tendikCheckboxes.forEach(cb => {
                    if (cb.checked) isAnyTendikChecked = true;
                });

                // Jika Role Utama dipilih, disable Tendik
                if (isRoleUtamaSelected) {
                    tendikCheckboxes.forEach(cb => {
                        cb.disabled = true;
                        cb.closest('label').classList.add('opacity-50', 'cursor-not-allowed');
                    });
                } else {
                    tendikCheckboxes.forEach(cb => {
                        cb.disabled = false;
                        cb.closest('label').classList.remove('opacity-50', 'cursor-not-allowed');
                    });
                }

                // Jika ada Tendik diceklis, disable Role Utama
                if (isAnyTendikChecked) {
                    roleUtamaSelect.disabled = true;
                    roleUtamaSelect.classList.add('bg-gray-100', 'cursor-not-allowed');
                } else {
                    roleUtamaSelect.disabled = false;
                    roleUtamaSelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            }

            roleUtamaSelect.addEventListener('change', function() {
                if (this.value !== "") {
                    tendikCheckboxes.forEach(cb => cb.checked = false);
                }
                updateMutualExclusivity();
            });

            tendikCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (this.checked) {
                        roleUtamaSelect.value = "";
                    }
                    updateMutualExclusivity();
                });
            });

            // Jalankan saat load (untuk menangani 'old' data)
            updateMutualExclusivity();
        });
    </script>
    @endpush
</x-app-layout>
