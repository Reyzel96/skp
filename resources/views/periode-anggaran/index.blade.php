<x-app-layout>
    @section('page-title', 'Kelola Periode Anggaran')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter">Periode Anggaran</h1>
                <p class="text-gray-500 text-sm">Kelola periode anggaran untuk program kegiatan fakultas.</p>
            </div>
            <button onclick="openAddModal()" 
                class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah Periode
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl relative shadow-sm" role="alert">
                <span class="block sm:inline font-bold">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white border border-gray-800 shadow-xl overflow-hidden rounded-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-white uppercase tracking-widest font-black text-[11px]">
                            <th class="border border-gray-800 p-4 text-center w-12">No</th>
                            <th class="border border-gray-800 p-4 text-center w-24">Tahun Anggaran</th>
                            <th class="border border-gray-800 p-4 text-left">Tahun Akademik</th>
                            <th class="border border-gray-800 p-4 text-center">Berlaku s/d Berakhir</th>
                            <th class="border border-gray-800 p-4 text-center w-24">Status</th>
                            <th class="border border-gray-800 p-4 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        @forelse($periodeList as $index => $p)
                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-200">
                                <td class="border border-gray-300 p-3 text-center font-bold text-gray-400">{{ $index + 1 }}</td>
                                <td class="border border-gray-300 p-3 text-center font-black text-blue-900 text-lg">{{ $p->tahun_anggaran }}</td>
                                <td class="border border-gray-300 p-3 font-bold text-gray-700">{{ $p->tahun_akademik }}</td>
                                <td class="border border-gray-300 p-3 text-center text-xs font-bold text-gray-600">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200">{{ $p->tanggal_mulai ? $p->tanggal_mulai->format('d/m/Y') : '-' }}</span>
                                        <i class="fas fa-long-arrow-alt-right text-gray-400"></i>
                                        <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200">{{ $p->tanggal_selesai ? $p->tanggal_selesai->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </td>
                                <td class="border border-gray-300 p-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border
                                        {{ $p->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }}">
                                        {{ $p->status }}
                                    </span>
                                </td>
                                <td class="border border-gray-300 p-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="editPeriode({{ json_encode($p) }})" class="text-blue-600 hover:text-blue-800 p-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('periode-anggaran.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus periode ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 p-1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-20 text-center text-gray-400 italic font-medium bg-gray-50">
                                    <i class="fas fa-calendar-times text-4xl block mb-4 opacity-20"></i>
                                    Belum ada data periode anggaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH --}}
    <div id="modal-tambah" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-tambah').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="form-tambah" action="{{ route('periode-anggaran.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-8 sm:pb-4">
                        <h3 class="text-xl leading-6 font-black text-gray-900 uppercase tracking-tight mb-6" id="modal-title">Tambah Periode Anggaran</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tahun Anggaran *</label>
                                <input type="number" name="tahun_anggaran" id="add-tahun-anggaran" 
                                    oninput="suggestAcademicYear(this.value, 'add')"
                                    class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Semester Genap *</label>
                                    <input type="text" id="add-genap" placeholder="Otomatis..." class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold bg-gray-50 text-xs" readonly>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Semester Gasal *</label>
                                    <input type="text" id="add-gasal" placeholder="Otomatis..." class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold bg-gray-50 text-xs" readonly>
                                </div>
                                <input type="hidden" name="tahun_akademik" id="add-tahun-akademik" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Mulai *</label>
                                    <input type="date" name="tanggal_mulai" id="add-tanggal-mulai" 
                                        onchange="setMinDate('add')"
                                        class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Selesai *</label>
                                    <input type="date" name="tanggal_selesai" id="add-tanggal-selesai" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold text-xs" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-8 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            SIMPAN
                        </button>
                        <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            BATAL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('modal-edit').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:min-h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="form-edit" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-8 sm:pb-4">
                        <h3 class="text-xl leading-6 font-black text-gray-900 uppercase tracking-tight mb-6" id="modal-title">Edit Periode Anggaran</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tahun Anggaran *</label>
                                <input type="number" id="edit-tahun-anggaran" name="tahun_anggaran" 
                                    oninput="suggestAcademicYear(this.value, 'edit')"
                                    class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Semester Genap *</label>
                                    <input type="text" id="edit-genap" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold bg-gray-50 text-xs" readonly>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Semester Gasal *</label>
                                    <input type="text" id="edit-gasal" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold bg-gray-50 text-xs" readonly>
                                </div>
                                <input type="hidden" name="tahun_akademik" id="edit-tahun-akademik" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Mulai *</label>
                                    <input type="date" id="edit-tanggal-mulai" name="tanggal_mulai" 
                                        onchange="setMinDate('edit')"
                                        class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Selesai *</label>
                                    <input type="date" id="edit-tanggal-selesai" name="tanggal_selesai" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold text-xs" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</label>
                                <select id="edit-status" name="status" class="w-full border-gray-300 rounded-xl focus:ring-blue-500 font-bold">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-8 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2 bg-blue-600 text-base font-bold text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            UPDATE
                        </button>
                        <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            BATAL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function suggestAcademicYear(year, prefix) {
            const genapEl = document.getElementById(`${prefix}-genap`);
            const gasalEl = document.getElementById(`${prefix}-gasal`);
            const hiddenEl = document.getElementById(`${prefix}-tahun-akademik`);

            if (year && year.length === 4) {
                const y = parseInt(year);
                const genapStart = y - 1;
                const gasalEnd = y + 1;
                
                const genapText = `Semester Genap ${genapStart}/${y}`;
                const gasalText = `Semester Gasal ${y}/${gasalEnd}`;
                
                genapEl.value = genapText;
                gasalEl.value = gasalText;
                hiddenEl.value = `${genapText} ${gasalText}`;
            } else {
                genapEl.value = '';
                gasalEl.value = '';
                hiddenEl.value = '';
            }
        }

        function setMinDate(prefix) {
            const startVal = document.getElementById(`${prefix}-tanggal-mulai`).value;
            const endEl = document.getElementById(`${prefix}-tanggal-selesai`);
            if (startVal) {
                endEl.min = startVal;
                // Jika tanggal selesai saat ini lebih kecil dari min baru, kosongkan
                if (endEl.value && endEl.value < startVal) {
                    endEl.value = '';
                }
            } else {
                endEl.removeAttribute('min');
            }
        }

        function openAddModal() {
            const form = document.getElementById('form-tambah');
            form.reset();
            document.getElementById('add-genap').value = '';
            document.getElementById('add-gasal').value = '';
            document.getElementById('add-tahun-akademik').value = '';
            document.getElementById('add-tanggal-selesai').removeAttribute('min');
            document.getElementById('modal-tambah').classList.remove('hidden');
        }

        function editPeriode(p) {
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');
            form.action = `/periode-anggaran/${p.id}`;
            document.getElementById('edit-tahun-anggaran').value = p.tahun_anggaran;
            
            // Split tahun_akademik for display
            if (p.tahun_akademik) {
                const parts = p.tahun_akademik.split(' Semester Gasal ');
                if (parts.length === 2) {
                    document.getElementById('edit-genap').value = parts[0];
                    document.getElementById('edit-gasal').value = 'Semester Gasal ' + parts[1];
                } else {
                    document.getElementById('edit-genap').value = p.tahun_akademik;
                    document.getElementById('edit-gasal').value = '';
                }
                document.getElementById('edit-tahun-akademik').value = p.tahun_akademik;
            }

            // Format date YYYY-MM-DD for input date
            if (p.tanggal_mulai) {
                const dMulai = new Date(p.tanggal_mulai);
                const dateStr = dMulai.toISOString().split('T')[0];
                document.getElementById('edit-tanggal-mulai').value = dateStr;
                document.getElementById('edit-tanggal-selesai').min = dateStr;
            }
            if (p.tanggal_selesai) {
                const dSelesai = new Date(p.tanggal_selesai);
                document.getElementById('edit-tanggal-selesai').value = dSelesai.toISOString().split('T')[0];
            }

            document.getElementById('edit-status').value = p.status;
            modal.classList.remove('hidden');
        }

        // Mencegah perubahan nilai pada input number saat di-scroll
        document.addEventListener('wheel', function(event) {
            if (document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, {
            passive: false
        });
    </script>
    <style>
        /* Sembunyikan Panah Atas/Bawah pada Input Number */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    @endsection
</x-app-layout>
