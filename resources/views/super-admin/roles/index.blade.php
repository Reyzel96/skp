<x-app-layout>
    @section('page-title', 'Kelola Jabatan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            [x-cloak] { display: none !important; }
        </style>
        <script>
            // Peta permission per dashboard dari server (SuperAdminController::DASHBOARD_PERMISSIONS).
            // Sumber kebenaran tetap di server; JS hanya menghitung pratinjau selisih.
            window.__dashboardPermissions = @js($dashboardPermissions);
        </script>
        <div x-data="{ 
            showEditModal: false, 
            editData: { id: '', name: '', kategori: '', dashboard_route: '' },
            editIsBuiltIn: false,
            editUrl: '',
            dashboardAwal: '',
            permissionAwal: [],
            openEdit(role, isBuiltIn = false) {
                this.editData = { 
                    id: role.id, 
                    name: role.name, 
                    kategori: role.kategori, 
                    dashboard_route: role.dashboard_route 
                };
                this.editIsBuiltIn = isBuiltIn;
                this.editUrl = `/superadmin/roles/${role.id}`;
                this.dashboardAwal = role.dashboard_route || '';
                // Permission non-peta yang dimiliki role saat ini (tidak disentuh sync selisih).
                const peta = window.__dashboardPermissions || {};
                const petaFlat = Object.values(peta).flat();
                this.permissionAwal = (role.permissions || [])
                    .map(p => p.name)
                    .filter(n => !petaFlat.includes(n));
                this.showEditModal = true;
            },
            get selisihPermission() {
                try {
                    const peta = window.__dashboardPermissions || {};
                    const lama = peta[this.dashboardAwal] || [];
                    const baru = peta[this.editData.dashboard_route] || [];
                    const dicabut = lama.filter(n => !baru.includes(n));
                    const ditambah = baru.filter(n => !lama.includes(n));
                    return { dicabut, ditambah, berubah: this.dashboardAwal !== this.editData.dashboard_route };
                } catch (e) {
                    console.error('roles: hitung selisih gagal', e);
                    return { dicabut: [], ditambah: [], berubah: false };
                }
            },
            get totalPermissionAkhir() {
                try {
                    const peta = window.__dashboardPermissions || {};
                    const baru = peta[this.editData.dashboard_route] || [];
                    return new Set([...this.permissionAwal, ...baru]).size;
                } catch (e) {
                    return 0;
                }
            },
            konfirmasiSebelumSimpan(event) {
                try {
                    const s = this.selisihPermission;
                    if (!s.berubah) return; // tanpa perubahan dashboard: submit langsung
                    if (s.dicabut.length === 0 && s.ditambah.length === 0) return;
                    let pesan = 'Konfirmasi Perubahan Dashboard\n\n';
                    pesan += 'Dashboard: ' + (this.dashboardAwal || '-') + ' → ' + this.editData.dashboard_route + '\n';
                    if (s.dicabut.length > 0) pesan += '\nDicabut: ' + s.dicabut.join(', ');
                    if (s.ditambah.length > 0) pesan += '\nDitambah: ' + s.ditambah.join(', ');
                    pesan += '\n\nPermission lainnya tidak berubah.';
                    if (!confirm(pesan)) {
                        event.preventDefault();
                    }
                } catch (e) {
                    console.error('roles: konfirmasi gagal', e);
                    // Biarkan submit jalan (fallback: server tetap sumber kebenaran).
                }
            }
        }">
        <div class="space-y-8">

            {{-- FORM TAMBAH JABATAN BARU --}}
            <div class="w-full">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Buat Jabatan Baru</h2>

                    <form action="{{ route('superadmin.roles.store') }}" method="POST" onsubmit="return confirm('Konfirmasi Penyimpanan\n\nApakah Anda yakin sudah mengecek seluruh data dengan teliti?');">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Jabatan *</label>
                                <input type="text" name="name" required placeholder="Cth: Plt. Dekan"
                                    class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                @error('name')
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori Jabatan *</label>
                                <select name="kategori" required
                                    class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="utama">Jabatan Utama (Struktural/Dosen)</option>
                                    <option value="tendik">Peran Tendik (Staf/Administrasi)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Gunakan Template Dashboard *</label>
                                <select name="dashboard_route" required
                                    class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @foreach ($baseDashboards as $route => $label)
                                        <option value="{{ $route }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                class="bg-blue-600 text-white font-bold py-2.5 px-8 rounded-lg hover:bg-blue-700 transition shadow-md">
                                <i class="fas fa-save mr-2"></i> Simpan Jabatan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- TABEL DAFTAR JABATAN YANG ADA --}}
            <div class="w-full">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center flex-wrap gap-2">
                        <h2 class="text-lg font-bold text-gray-800">Daftar Jabatan Tersedia</h2>
                        <div class="flex items-center gap-3">
                            <button type="button" id="btn-bulk-delete-role" class="bg-red-600 text-white px-3 py-1.5 text-sm rounded-lg font-bold hover:bg-red-700 transition shadow-sm flex items-center hidden" onclick="if(confirm('Yakin ingin menghapus semua jabatan yang dipilih?')) document.getElementById('bulk-delete-role-form').submit();">
                                <i class="fas fa-trash-alt mr-2"></i> Hapus Terpilih (<span id="count-selected-role">0</span>)
                            </button>
                            <span class="text-xs font-semibold text-gray-500 bg-gray-200 px-3 py-1 rounded-full">{{ $roles->count() }} Total Jabatan</span>
                        </div>
                    </div>
                    <form id="bulk-delete-role-form" action="{{ route('superadmin.roles.bulk-destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-10">
                                            <input type="checkbox" id="check-all-role" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nama Jabatan</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kategori</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($roles as $role)
                                    @php $isBuiltIn = in_array($role->name, $builtInRoles); @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($role->users_count == 0 && !$isBuiltIn)
                                                <input type="checkbox" name="ids[]" value="{{ $role->id }}" class="role-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $role->name }}
                                            @if($isBuiltIn)
                                                <span class="text-[10px] text-gray-600 font-semibold bg-gray-100 px-2 py-0.5 rounded border border-gray-200 ml-1" title="Role bawaan sistem: tidak dapat di-rename atau dihapus">Bawaan</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($role->kategori == 'utama')
                                                <span
                                                    class="px-2 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-800">Utama</span>
                                            @else
                                                <span
                                                    class="px-2 py-1 text-xs font-bold rounded-md bg-yellow-100 text-yellow-800">Tendik</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                            @if($role->name === 'Super Admin')
                                                <span class="text-[10px] text-gray-500 font-semibold bg-gray-50 px-2 py-1 rounded border border-gray-200 cursor-not-allowed" title="Role Super Admin terkunci penuh: tidak dapat diedit, di-rename, atau dihapus (mencegah admin mengunci dirinya sendiri).">Terkunci</span>
                                            @else
                                                <button type="button" @click="openEdit(@js($role), @js($isBuiltIn))" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </button>
                                            @endif
                                
                                            @if(!$isBuiltIn && $role->users_count == 0)
                                                {{-- Form hapus per-baris di LUAR form bulk: nested <form>
                                                     invalid di HTML (parser menutup form bulk lebih awal,
                                                     checkbox ids[] tidak pernah terkirim). Tombol memakai
                                                     attribute form= (HTML5) → submit native TANPA JS. --}}
                                                <button type="submit" form="hapus-role-{{ $role->id }}"
                                                    class="text-red-600 hover:text-red-900 ml-2" title="Hapus jabatan ini">
                                                    <i class="fas fa-trash mr-1"></i>Hapus
                                                </button>
                                            @elseif($isBuiltIn)
                                                <span class="text-[10px] text-gray-500 font-semibold bg-gray-50 px-2 py-1 rounded border border-gray-200 ml-2" title="Role bawaan sistem tidak dapat dihapus">Terlindungi</span>
                                            @else
                                                <span class="text-[10px] text-orange-600 font-semibold bg-orange-50 px-2 py-1 rounded border border-orange-200 ml-2" title="Digunakan oleh {{ $role->users_count }} pegawai">Sedang Digunakan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </form>

                    {{-- Form hapus per-baris: DI LUAR form bulk (nested <form> invalid di
                         HTML — parser menutup form bulk lebih awal sehingga id form nested
                         tidak pernah ada di DOM). Diakses tombol Hapus via attribute form=,
                         submit native TANPA JS, konfirmasi via onsubmit. --}}
                    @foreach ($roles as $role)
                        @if(!in_array($role->name, $builtInRoles) && $role->users_count == 0)
                            <form id="hapus-role-{{ $role->id }}" action="{{ route('superadmin.roles.destroy', $role->id) }}" method="POST" class="hidden" onsubmit="return confirm('Hapus jabatan ini? Tindakan ini tidak dapat dibatalkan.');">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>

            <script>
                // Turbo Drive mengganti <body> tanpa reload — DOMContentLoaded tidak
                // terpicu ulang. Pola init: jalankan saat DOM siap ATAU turbo:load,
                // dengan guard dataset anti double-bind + cleanup before-cache.
                (function () {
                    function initRolesPage() {
                        try {
                            const table = document.getElementById('bulk-delete-role-form');
                            if (!table || table.dataset.rolesInit) return;
                            table.dataset.rolesInit = '1';

                            const checkAll = document.getElementById('check-all-role');
                            const btnBulkDelete = document.getElementById('btn-bulk-delete-role');
                            const countSelected = document.getElementById('count-selected-role');
                            if (!checkAll || !btnBulkDelete || !countSelected) return;

                            function updateBulkDeleteButton() {
                                try {
                                    const checkedCount = document.querySelectorAll('.role-checkbox:checked').length;
                                    countSelected.textContent = checkedCount;
                                    if (checkedCount > 0) {
                                        btnBulkDelete.classList.remove('hidden');
                                    } else {
                                        btnBulkDelete.classList.add('hidden');
                                    }
                                    const checkboxes = document.querySelectorAll('.role-checkbox');
                                    if (checkboxes.length > 0) {
                                        checkAll.checked = checkedCount === checkboxes.length;
                                    }
                                } catch (e) {
                                    console.error('roles: gagal update tombol bulk delete', e);
                                }
                            }

                            if (!checkAll.dataset.rolesInit) {
                                checkAll.dataset.rolesInit = '1';
                                checkAll.addEventListener('change', function () {
                                    document.querySelectorAll('.role-checkbox').forEach(cb => {
                                        cb.checked = checkAll.checked;
                                    });
                                    updateBulkDeleteButton();
                                });
                            }

                            document.querySelectorAll('.role-checkbox').forEach(cb => {
                                if (!cb.dataset.rolesInit) {
                                    cb.dataset.rolesInit = '1';
                                    cb.addEventListener('change', updateBulkDeleteButton);
                                }
                            });

                            updateBulkDeleteButton();
                        } catch (e) {
                            console.error('roles: inisialisasi halaman gagal', e);
                        }
                    }

                    function cleanupRolesPage() {
                        try {
                            const table = document.getElementById('bulk-delete-role-form');
                            if (table) delete table.dataset.rolesInit;
                            const checkAll = document.getElementById('check-all-role');
                            if (checkAll) delete checkAll.dataset.rolesInit;
                        } catch (e) {
                            console.error('roles: cleanup gagal', e);
                        }
                    }

                    if (document.readyState !== 'loading') {
                        initRolesPage();
                    } else {
                        document.addEventListener('DOMContentLoaded', initRolesPage, { once: true });
                    }
                    document.addEventListener('turbo:load', initRolesPage);
                    document.addEventListener('turbo:before-cache', cleanupRolesPage);
                })();
            </script>

        </div>

        {{-- MODAL EDIT JABATAN --}}
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="editUrl" method="POST" @submit="konfirmasiSebelumSimpan($event)">
                        @csrf
                        @method('PUT')
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 border-b pb-2" id="modal-title">
                                Edit Jabatan
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Jabatan *</label>
                                    <input type="text" name="name" x-model="editData.name" required
                                        :readonly="editIsBuiltIn"
                                        :class="editIsBuiltIn ? 'bg-gray-100 cursor-not-allowed' : ''"
                                        class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <p x-show="editIsBuiltIn" x-cloak class="text-xs text-gray-500 mt-1">Role bawaan sistem tidak dapat di-rename (dipakai menu sidebar & pemeriksaan hak akses).</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kategori Jabatan *</label>
                                    <select name="kategori" x-model="editData.kategori" required
                                        class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="utama">Jabatan Utama (Struktural/Dosen)</option>
                                        <option value="tendik">Peran Tendik (Staf/Administrasi)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Template Dashboard *</label>
                                    <select name="dashboard_route" x-model="editData.dashboard_route" required
                                        class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        @foreach ($baseDashboards as $route => $label)
                                            <option value="{{ $route }}">{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    {{-- Ringkasan selisih permission saat dashboard diubah --}}
                                    <div x-show="selisihPermission.berubah" x-cloak class="mt-2 text-xs rounded-lg border px-3 py-2"
                                        :class="totalPermissionAkhir === 0 ? 'border-amber-300 bg-amber-50 text-amber-900' : 'border-blue-200 bg-blue-50 text-blue-900'">
                                        <template x-if="selisihPermission.dicabut.length > 0">
                                            <p><span class="font-semibold">Dicabut:</span> <span x-text="selisihPermission.dicabut.join(', ')"></span></p>
                                        </template>
                                        <template x-if="selisihPermission.ditambah.length > 0">
                                            <p><span class="font-semibold">Ditambah:</span> <span x-text="selisihPermission.ditambah.join(', ')"></span></p>
                                        </template>
                                        <template x-if="selisihPermission.dicabut.length === 0 && selisihPermission.ditambah.length === 0">
                                            <p>Permission tidak berubah.</p>
                                        </template>
                                        <template x-if="totalPermissionAkhir === 0">
                                            <p class="font-semibold mt-1">⚠ Role ini tidak akan punya permission apa pun.</p>
                                        </template>
                                        <p class="mt-1 opacity-75">Permission lainnya tidak berubah.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Simpan Perubahan
                            </button>
                            <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        </div>
    @endsection
</x-app-layout>
