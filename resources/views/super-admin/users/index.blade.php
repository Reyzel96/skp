<x-app-layout>
    @section('page-title', 'Kelola Users')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('superadmin.users.create') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition shadow-sm flex items-center">
                    <i class="fas fa-plus mr-2"></i> Tambah User
                </a>
                
                <button type="button" onclick="document.getElementById('modal-import').classList.remove('hidden')"
                    class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 transition shadow-sm flex items-center">
                    <i class="fas fa-file-import mr-2"></i> Import User
                </button>

                <button type="button" id="btn-bulk-delete" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 transition shadow-sm flex items-center hidden" onclick="if(confirm('Yakin ingin menghapus semua user yang dipilih?')) document.getElementById('bulk-delete-form').submit();">
                    <i class="fas fa-trash-alt mr-2"></i> Hapus Terpilih (<span id="count-selected">0</span>)
                </button>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- MODAL IMPORT -->
        <div id="modal-import" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-import').classList.add('hidden')"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('superadmin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-file-excel text-emerald-600"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Import Data User</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 mb-4">
                                            Silakan unggah file Excel (.xlsx, .xls) atau CSV sesuai format sistem. 
                                            <a href="{{ route('superadmin.users.template') }}" class="text-blue-600 font-bold hover:underline">Unduh Template Disini</a>
                                        </p>
                                        
                                        <div id="drop-zone" class="border-2 border-dashed border-gray-300 hover:border-emerald-500 hover:bg-emerald-50 rounded-lg p-6 text-center transition-all duration-200 relative">
                                            <input type="file" name="file" id="file-import" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".xlsx,.xls,.csv" onchange="updateFileName(this)">
                                            <div id="upload-placeholder" class="pointer-events-none">
                                                <i id="upload-icon" class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3 transition-colors"></i>
                                                <p id="upload-text" class="text-sm text-gray-600 font-medium">Drag & Drop file Anda di sini</p>
                                                <p class="text-xs text-gray-400 mt-1">atau klik untuk menelusuri komputer</p>
                                            </div>
                                            <div id="file-name-display" class="hidden pointer-events-none flex-col items-center justify-center">
                                                <i class="fas fa-file-excel text-4xl text-emerald-500 mb-2"></i>
                                                <p id="file-name-text" class="font-bold text-emerald-700 text-sm truncate w-full px-4"></p>
                                                <p class="text-xs text-emerald-600 mt-1">Siap untuk diimport</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-bold text-white hover:bg-emerald-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                MULAI IMPORT
                            </button>
                            <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                BATAL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function updateFileName(input) {
                const placeholder = document.getElementById('upload-placeholder');
                const nameDisplay = document.getElementById('file-name-display');
                const nameText = document.getElementById('file-name-text');
                const dropZone = document.getElementById('drop-zone');
                
                if (input.files && input.files[0]) {
                    placeholder.classList.add('hidden');
                    nameDisplay.classList.remove('hidden');
                    nameDisplay.classList.add('flex');
                    nameText.innerText = input.files[0].name;
                    dropZone.classList.add('border-emerald-500', 'bg-emerald-50');
                    dropZone.classList.remove('border-gray-300');
                } else {
                    placeholder.classList.remove('hidden');
                    nameDisplay.classList.add('hidden');
                    nameDisplay.classList.remove('flex');
                    dropZone.classList.remove('border-emerald-500', 'bg-emerald-50');
                    dropZone.classList.add('border-gray-300');
                }
            }

            // Drag and Drop Events UI Enhancements
            document.addEventListener('DOMContentLoaded', function() {
                const dropZone = document.getElementById('drop-zone');
                const uploadIcon = document.getElementById('upload-icon');
                const uploadText = document.getElementById('upload-text');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    dropZone.classList.add('border-emerald-500', 'bg-emerald-100', 'scale-105');
                    uploadIcon.classList.remove('text-gray-400');
                    uploadIcon.classList.add('text-emerald-500', 'animate-bounce');
                    uploadText.innerText = "Lepaskan file sekarang!";
                    uploadText.classList.add('text-emerald-600');
                }

                function unhighlight(e) {
                    dropZone.classList.remove('border-emerald-500', 'bg-emerald-100', 'scale-105');
                    uploadIcon.classList.add('text-gray-400');
                    uploadIcon.classList.remove('text-emerald-500', 'animate-bounce');
                    uploadText.innerText = "Drag & Drop file Anda di sini";
                    uploadText.classList.remove('text-emerald-600');
                }
                
                // The actual drop is handled natively by the absolute file input, 
                // we just needed the visual hover effects!
            });
        </script>

        <form id="bulk-delete-form" action="{{ route('superadmin.users.bulk-destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left w-10">
                                <input type="checkbox" id="check-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail Pekerjaan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role Sistem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $activeSince = now()->getTimestamp() - 300;
                                    $isOnline = \Illuminate\Support\Facades\DB::table('sessions')
                                        ->where('user_id', $user->id)
                                        ->where('last_activity', '>=', $activeSince)
                                        ->exists();
                                    $canDelete = $user->id !== auth()->id() && !$isOnline;
                                @endphp
                                @if($canDelete)
                                    <input type="checkbox" name="ids[]" value="{{ $user->id }}" class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $user->profile->nip ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{-- PERBAIKAN: Menggunakan nama_lengkap dari profile --}}
                                <div class="font-bold">{{ $user->profile->nama_lengkap ?? $user->name }}</div>

                                @if ($user->profile && $user->profile->jabatan_fungsional)
                                    <div class="text-xs text-gray-500 mt-1">{{ $user->profile->jabatan_fungsional }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-xs">
                                <div class="font-medium text-blue-700">{{ $user->profile->jabatan_pekerjaan ?? 'Belum diset' }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $user->profile->unit_kerja ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->getRoleNames() as $role)
                                        <span class="px-2 py-1 text-[10px] leading-tight font-semibold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-3 items-center">
                                    <a href="{{ route('superadmin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-3 py-1 rounded">Edit</a>

                                    @if ($canDelete)
                                        <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST" class="inline m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-bold bg-red-50 px-3 py-1 rounded" onclick="return confirm('Yakin hapus user ini?')">Hapus</button>
                                        </form>
                                    @else
                                        @if($isOnline && $user->id !== auth()->id())
                                            <span class="text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-1 rounded border border-emerald-200">Online</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">Belum ada data user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkAll = document.getElementById('check-all');
                const checkboxes = document.querySelectorAll('.user-checkbox');
                const btnBulkDelete = document.getElementById('btn-bulk-delete');
                const countSelected = document.getElementById('count-selected');

                function updateBulkDeleteButton() {
                    const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
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
    @endsection
</x-app-layout>
