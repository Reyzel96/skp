<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-8" x-data="{ 
            showModal: false, 
            editMode: false,
            currentProdi: { id: '', nama: '', kode: '' },
            
            openAddModal() {
                this.editMode = false;
                this.currentProdi = { id: '', nama: '', kode: '' };
                this.showModal = true;
            },
            openEditModal(prodi) {
                this.editMode = true;
                this.currentProdi = { ...prodi };
                this.showModal = true;
            }
        }">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Manajemen Program Studi</h1>
                    <p class="text-gray-500 font-medium">Kelola daftar prodi yang akan muncul di alokasi anggaran fakultas.</p>
                </div>
                <button @click="openAddModal()"
                    class="inline-flex items-center bg-gray-900 text-white px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-black transition-all shadow-xl hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Prodi Baru
                </button>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">No</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Nama Program Studi</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Kode</th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($prodis as $prodi)
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-8 py-5 whitespace-nowrap text-sm font-bold text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="text-sm font-black text-gray-800 uppercase tracking-tight group-hover:text-blue-600 transition-colors">{{ $prodi->nama }}</span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-black uppercase">{{ $prodi->kode ?? '-' }}</span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            <button @click="openEditModal(@js($prodi))"
                                                class="p-2.5 text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
                                                title="Edit Prodi">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            <form action="{{ route('superadmin.prodi.destroy', $prodi->id) }}"
                                                method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all"
                                                    onclick="return confirm('Hapus prodi {{ $prodi->nama }}? Data alokasi anggaran lama yang menggunakan nama ini mungkin tidak akan terbaca dengan benar.')">
                                                    <i class="fas fa-trash-alt text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                                                <i class="fas fa-university text-4xl"></i>
                                            </div>
                                            <p class="text-gray-900 font-black text-xl uppercase tracking-tighter">Belum ada data Prodi</p>
                                            <p class="text-gray-400 text-sm font-medium mt-1">Silakan tambah prodi untuk memulai sistem alokasi anggaran.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MODAL TAMBAH/EDIT --}}
            <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showModal" @click="showModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-10">

                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tighter" x-text="editMode ? 'Edit Program Studi' : 'Tambah Prodi Baru'"></h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-red-500 transition-colors">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>

                        <form :action="editMode ? '{{ url('superadmin/prodi') }}/' + currentProdi.id : '{{ route('superadmin.prodi.store') }}'" method="POST" class="space-y-6">
                            @csrf
                            <template x-if="editMode">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nama Program Studi</label>
                                <input type="text" name="nama" x-model="currentProdi.nama" required
                                    class="w-full bg-gray-50 border-gray-200 rounded-2xl p-4 font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all uppercase">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Kode Prodi (Opsional)</label>
                                <input type="text" name="kode" x-model="currentProdi.kode"
                                    class="w-full bg-gray-50 border-gray-200 rounded-2xl p-4 font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all uppercase">
                            </div>

                            <div class="pt-6">
                                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl transition-all transform hover:-translate-y-1">
                                    <span x-text="editMode ? 'Simpan Perubahan' : 'Simpan Data Prodi'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
