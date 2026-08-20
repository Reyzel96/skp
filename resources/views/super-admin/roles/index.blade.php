<x-app-layout>
    @section('page-title', 'Kelola Jabatan')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            [x-cloak] { display: none !important; }
        </style>
        <div x-data="{ 
            showEditModal: false, 
            editData: { id: '', name: '', kategori: '', dashboard_route: '' },
            editUrl: '',
            openEdit(role) {
                this.editData = { 
                    id: role.id, 
                    name: role.name, 
                    kategori: role.kategori, 
                    dashboard_route: role.dashboard_route 
                };
                this.editUrl = `/superadmin/roles/${role.id}`;
                this.showEditModal = true;
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
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($role->users_count == 0)
                                                <input type="checkbox" name="ids[]" value="{{ $role->id }}" class="role-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $role->name }}
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
                                            <button @click="openEdit({{ $role }})" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            
                                            @if($role->users_count == 0)
                                                <form action="{{ route('superadmin.roles.destroy', $role->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jabatan ini? Tindakan ini tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 ml-2">
                                                        <i class="fas fa-trash mr-1"></i>Hapus
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-[10px] text-orange-600 font-semibold bg-orange-50 px-2 py-1 rounded border border-orange-200 ml-2" title="Digunakan oleh {{ $role->users_count }} pegawai">Sedang Digunakan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            </tbody>
                        </table>
                    </div>
                    </form>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const checkAll = document.getElementById('check-all-role');
                    const checkboxes = document.querySelectorAll('.role-checkbox');
                    const btnBulkDelete = document.getElementById('btn-bulk-delete-role');
                    const countSelected = document.getElementById('count-selected-role');

                    function updateBulkDeleteButton() {
                        const checkedCount = document.querySelectorAll('.role-checkbox:checked').length;
                        countSelected.textContent = checkedCount;
                        if (checkedCount > 0) {
                            btnBulkDelete.classList.remove('hidden');
                        } else {
                            btnBulkDelete.classList.add('hidden');
                        }
                        
                        if (checkboxes.length > 0) {
                            checkAll.checked = checkedCount === checkboxes.length;
                        }
                    }

                    if (checkAll) {
                        checkAll.addEventListener('change', function() {
                            checkboxes.forEach(cb => {
                                cb.checked = this.checked;
                            });
                            updateBulkDeleteButton();
                        });
                    }

                    checkboxes.forEach(cb => {
                        cb.addEventListener('change', updateBulkDeleteButton);
                    });
                });
            </script>

        </div>

        {{-- MODAL EDIT JABATAN --}}
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="editUrl" method="POST">
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
                                        class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
