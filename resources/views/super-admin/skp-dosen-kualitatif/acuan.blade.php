<x-app-layout>
    @section('page-title', 'Acuan SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6" x-data="acuanEditor()">
            {{-- Toast Notification --}}
            <div x-show="showNotification" x-transition.opacity style="display: none;"
                 class="fixed top-5 right-5 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50 flex items-center gap-3">
                <i class="fas fa-check-circle"></i>
                <span x-text="notificationMessage"></span>
            </div>

            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Acuan SKP Dosen Kualitatif</h1>
                    <div class="mt-2 bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-3 text-lg"></i>
                        <p class="text-sm text-blue-700 font-medium">Pedoman dan Indikator Kinerja (Ubah teks di dalam kotak, otomatis tersimpan saat Anda mengeklik ke luar kotak)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="notificationMessage = 'Seluruh data berhasil disimpan!'; showNotification = true; setTimeout(() => { showNotification = false; }, 3000);"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-save mr-2"></i> Simpan
                    </button>
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- TAB UTAMA -->
            <div  class="bg-white rounded-xl mb-8 shadow-sm border border-gray-200 overflow-hidden mb-8" >
                <div class="bg-blue-600 px-6 py-4">
                    <h2 class="text-lg font-bold text-white tracking-wide">A. KINERJA UTAMA</h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="w-full text-xs text-left border border-gray-200">
                            <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-[11px]">
                                <tr>
                                    <th class="px-4 py-3 border w-[17%]">Sasaran Kinerja</th>
                                    <th class="px-4 py-3 border w-[17%]">Ukuran Keberhasilan / Indikator Kinerja Individu</th>
                                    <th class="px-4 py-3 border w-[17%]">Definisi Operasional</th>
                                    <th class="px-4 py-3 border w-[15%]">Bukti Utama</th>
                                    <th class="px-4 py-3 border w-[19%]">Target Sesuai Ekspektasi</th>
                                    <th class="px-4 py-3 border w-[15%]">Acuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700">
                                @foreach($utama as $parent)
                                    @foreach($parent->children as $idx => $child)
                                    <tr>
                                        @if($idx == 0)
                                        <td class="p-0 border align-top relative group" rowspan="{{ $parent->children->count() }}" x-data="{ localValue: {{ json_encode($parent->sasaran_kinerja) }} }">
                                            <div class="invisible whitespace-pre-wrap pt-3 pl-3 pr-8 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue" @change="saveFieldDirect({{ $parent->id }}, 'sasaran_kinerja', localValue, 'parent')" class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify pt-3 pl-3 pr-8 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <button type="button" @click="addChild({{ $parent->id }})" class="pointer-events-auto text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded px-2 py-1 text-[10px] font-bold shadow-sm flex items-center gap-1 border border-blue-200" title="Tambah Sub Indikator">
                                                    <i class="fas fa-plus"></i> Tambah Sub Indikator
                                                </button>
                                                <button type="button" @click="deleteParent({{ $parent->id }})" class="pointer-events-auto text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Sasaran Kinerja">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif

                                        @foreach(['indikator_kinerja', 'definisi_operasional', 'bukti_utama', 'target_ekspektasi', 'acuan'] as $f)
                                        <td class="p-0 border align-top relative group" x-data="{ localValue: {{ json_encode($child->$f) }}{{ $f == 'indikator_kinerja' ? ', butuhLampiran: '.($child->butuh_lampiran ? 'true' : 'false').', labelLampiran: '.json_encode($child->label_lampiran ?? '') : '' }} }">
                                            <div class="invisible whitespace-pre-wrap p-3 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue"
                                                  @change="saveFieldDirect({{ $child->id }}, '{{ $f }}', localValue, 'child')"
                                                  class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify p-3 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            @if($f == 'indikator_kinerja')
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <label class="pointer-events-auto flex items-center gap-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded px-1.5 py-0.5 text-[9px] font-bold text-amber-700 cursor-pointer shadow-sm" title="Centang jika baris ini wajib melampirkan bukti saat realisasi pegawai">
                                                    <input type="checkbox" x-model="butuhLampiran" @change="saveFieldDirect({{ $child->id }}, 'butuh_lampiran', butuhLampiran, 'child')" class="w-3 h-3 accent-amber-600 cursor-pointer">
                                                    <i class="fas fa-paperclip"></i> Lampiran
                                                </label>
                                                <input type="text" x-show="butuhLampiran" x-model="labelLampiran" @change="saveFieldDirect({{ $child->id }}, 'label_lampiran', labelLampiran, 'child')" placeholder="Nama lampiran..." class="pointer-events-auto text-[9px] border border-amber-300 rounded px-1 py-0.5 w-20 mt-0.5 outline-none focus:ring-1 focus:ring-amber-400">
                                                @if($parent->children->count() > 1)
                                                <button type="button" @click="deleteChild({{ $child->id }})" class="text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Baris Ini">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="p-2 bg-gray-50 text-center border-t">
                                        <button type="button" @click="addParent('utama')" class="text-blue-600 hover:text-blue-800 font-bold text-[11px] py-1 px-3 rounded hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-plus mr-1"></i> Tambah Sasaran Kinerja Utama
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB TAMBAHAN -->
            <div  class="bg-white rounded-xl mb-8 shadow-sm border border-gray-200 overflow-hidden mb-8" >
                <div class="bg-blue-600 px-6 py-4">
                    <h2 class="text-lg font-bold text-white tracking-wide">B. KINERJA TAMBAHAN (TIDAK WAJIB)</h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="w-full text-xs text-left border border-gray-200">
                            <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-[11px]">
                                <tr>
                                    <th class="px-4 py-3 border w-[17%]">Sasaran Kinerja</th>
                                    <th class="px-4 py-3 border w-[17%]">Ukuran Keberhasilan / Indikator Kinerja Individu</th>
                                    <th class="px-4 py-3 border w-[17%]">Definisi Operasional</th>
                                    <th class="px-4 py-3 border w-[15%]">Bukti Utama</th>
                                    <th class="px-4 py-3 border w-[19%]">Target Sesuai Ekspektasi</th>
                                    <th class="px-4 py-3 border w-[15%]">Acuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700">
                                @foreach($tambahan as $parent)
                                    @foreach($parent->children as $idx => $child)
                                    <tr>
                                        @if($idx == 0)
                                        <td class="p-0 border align-top relative group" rowspan="{{ $parent->children->count() }}" x-data="{ localValue: {{ json_encode($parent->sasaran_kinerja) }} }">
                                            <div class="invisible whitespace-pre-wrap pt-3 pl-3 pr-8 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue" @change="saveFieldDirect({{ $parent->id }}, 'sasaran_kinerja', localValue, 'parent')" class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify pt-3 pl-3 pr-8 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <button type="button" @click="addChild({{ $parent->id }})" class="pointer-events-auto text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded px-2 py-1 text-[10px] font-bold shadow-sm flex items-center gap-1 border border-blue-200" title="Tambah Sub Indikator">
                                                    <i class="fas fa-plus"></i> Tambah Sub Indikator
                                                </button>
                                                <button type="button" @click="deleteParent({{ $parent->id }})" class="pointer-events-auto text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Sasaran Kinerja">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif

                                        @foreach(['indikator_kinerja', 'definisi_operasional', 'bukti_utama', 'target_ekspektasi', 'acuan'] as $f)
                                        <td class="p-0 border align-top relative group" x-data="{ localValue: {{ json_encode($child->$f) }}{{ $f == 'indikator_kinerja' ? ', butuhLampiran: '.($child->butuh_lampiran ? 'true' : 'false').', labelLampiran: '.json_encode($child->label_lampiran ?? '') : '' }} }">
                                            <div class="invisible whitespace-pre-wrap p-3 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue"
                                                  @change="saveFieldDirect({{ $child->id }}, '{{ $f }}', localValue, 'child')"
                                                  class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify p-3 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            @if($f == 'indikator_kinerja')
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <label class="pointer-events-auto flex items-center gap-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded px-1.5 py-0.5 text-[9px] font-bold text-amber-700 cursor-pointer shadow-sm" title="Centang jika baris ini wajib melampirkan bukti saat realisasi pegawai">
                                                    <input type="checkbox" x-model="butuhLampiran" @change="saveFieldDirect({{ $child->id }}, 'butuh_lampiran', butuhLampiran, 'child')" class="w-3 h-3 accent-amber-600 cursor-pointer">
                                                    <i class="fas fa-paperclip"></i> Lampiran
                                                </label>
                                                <input type="text" x-show="butuhLampiran" x-model="labelLampiran" @change="saveFieldDirect({{ $child->id }}, 'label_lampiran', labelLampiran, 'child')" placeholder="Nama lampiran..." class="pointer-events-auto text-[9px] border border-amber-300 rounded px-1 py-0.5 w-20 mt-0.5 outline-none focus:ring-1 focus:ring-amber-400">
                                                @if($parent->children->count() > 1)
                                                <button type="button" @click="deleteChild({{ $child->id }})" class="text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Baris Ini">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="p-2 bg-gray-50 text-center border-t">
                                        <button type="button" @click="addParent('tambahan')" class="text-blue-600 hover:text-blue-800 font-bold text-[11px] py-1 px-3 rounded hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-plus mr-1"></i> Tambah Sasaran Kinerja Tambahan
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB JABATAN -->
            <div  class="bg-white rounded-xl mb-8 shadow-sm border border-gray-200 overflow-hidden mb-8" >
                <div class="bg-blue-600 px-6 py-4">
                    <h2 class="text-lg font-bold text-white tracking-wide">C. KHUSUS JABATAN</h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="w-full text-xs text-left border border-gray-200">
                            <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-[11px]">
                                <tr>
                                    <th class="px-4 py-3 border w-[17%]">Sasaran Kinerja</th>
                                    <th class="px-4 py-3 border w-[17%]">Ukuran Keberhasilan / Indikator Kinerja Individu</th>
                                    <th class="px-4 py-3 border w-[17%]">Definisi Operasional</th>
                                    <th class="px-4 py-3 border w-[15%]">Bukti Utama</th>
                                    <th class="px-4 py-3 border w-[19%]">Target Sesuai Ekspektasi</th>
                                    <th class="px-4 py-3 border w-[15%]">Acuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700">
                                @foreach($jabatan as $parent)
                                    @foreach($parent->children as $idx => $child)
                                    <tr>
                                        @if($idx == 0)
                                        <td class="p-0 border align-top relative group" rowspan="{{ $parent->children->count() }}" x-data="{ localValue: {{ json_encode($parent->sasaran_kinerja) }} }">
                                            <div class="invisible whitespace-pre-wrap pt-3 pl-3 pr-8 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue" @change="saveFieldDirect({{ $parent->id }}, 'sasaran_kinerja', localValue, 'parent')" class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify pt-3 pl-3 pr-8 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <button type="button" @click="addChild({{ $parent->id }})" class="pointer-events-auto text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded px-2 py-1 text-[10px] font-bold shadow-sm flex items-center gap-1 border border-blue-200" title="Tambah Sub Indikator">
                                                    <i class="fas fa-plus"></i> Tambah Sub Indikator
                                                </button>
                                                <button type="button" @click="deleteParent({{ $parent->id }})" class="pointer-events-auto text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Sasaran Kinerja">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif

                                        @foreach(['indikator_kinerja', 'definisi_operasional', 'bukti_utama', 'target_ekspektasi', 'acuan'] as $f)
                                            @if($f == 'indikator_kinerja')
                                                <td class="p-3 border align-top relative group" x-data="{ localValue: {{ json_encode($child->$f) }}, butuhLampiran: {{ $child->butuh_lampiran ? 'true' : 'false' }}, labelLampiran: {{ json_encode($child->label_lampiran ?? '') }} }">
                                                    <div class="text-[11px] text-gray-500 mb-1 leading-relaxed">
                                                        Melaksanakan tugas pimpinan perguruan tinggi swasta berdasarkan Izin Menduduki Jabatan dari Rektor Universitas Muhammadiyah Surabaya |
                                                    </div>
                                                    <div class="relative w-full group-hover:bg-gray-50 transition-colors rounded-sm pb-1">
                                                        <select x-model="localValue"
                                                            @change="saveFieldDirect({{ $child->id }}, '{{ $f }}', localValue, 'child')"
                                                            class="w-full border-transparent bg-transparent focus:bg-white text-[11px] text-justify py-2 outline-none focus:ring-2 focus:ring-blue-100 transition-all text-gray-700 block m-0 font-bold cursor-pointer rounded">
                                                            <option value="">-- Pilih Jabatan --</option>
                                                            <option value="(Dekan / Wakil Dekan)">(Dekan / Wakil Dekan)</option>
                                                            <option value="(Kaprodi / Sekprodi)">(Kaprodi / Sekprodi)</option>
                                                            <option value="(Kepala Laboratorium)">(Kepala Laboratorium)</option>
                                                            <option value="(Kepala Unit Penjaminan Mutu Prodi / Gugus Kendali Mutu Fakultas)">(Kepala Unit Penjaminan Mutu Prodi / Gugus Kendali Mutu Fakultas)</option>
                                                            <option value="(Unit Penelitian dan Pengabdian kepada Masyarakat)">(Unit Penelitian dan Pengabdian kepada Masyarakat)</option>
                                                            <option value="(Unit Kemahasiswaan)">(Unit Kemahasiswaan)</option>
                                                            <option value="(Unit Internasional dan Kerja Sama)">(Unit Internasional dan Kerja Sama)</option>
                                                            <option value="(Unit Humas dan Promosi)">(Unit Humas dan Promosi)</option>
                                                        </select>
                                                    </div>
                                                    <div class="flex justify-between items-center mt-1">
                                                        <label class="flex items-center gap-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded px-1.5 py-0.5 text-[9px] font-bold text-amber-700 cursor-pointer shadow-sm" title="Centang jika baris ini wajib melampirkan bukti saat realisasi pegawai">
                                                            <input type="checkbox" x-model="butuhLampiran" @change="saveFieldDirect({{ $child->id }}, 'butuh_lampiran', butuhLampiran, 'child')" class="w-3 h-3 accent-amber-600 cursor-pointer">
                                                            <i class="fas fa-paperclip"></i> Lampiran
                                                        </label>
                                                        <input type="text" x-show="butuhLampiran" x-model="labelLampiran" @change="saveFieldDirect({{ $child->id }}, 'label_lampiran', labelLampiran, 'child')" placeholder="Nama lampiran..." class="pointer-events-auto text-[9px] border border-amber-300 rounded px-1 py-0.5 w-20 mt-0.5 outline-none focus:ring-1 focus:ring-amber-400">
                                                        @if($parent->children->count() > 1)
                                                        <button type="button" @click="deleteChild({{ $child->id }})" class="text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Baris Ini">
                                                            <i class="fas fa-trash text-[10px]"></i>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @else
                                                <td class="p-0 border align-top relative group" x-data="{ localValue: {{ json_encode($child->$f) }} }">
                                                    <div class="invisible whitespace-pre-wrap p-3 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                                    <textarea x-model="localValue"
                                                        @change="saveFieldDirect({{ $child->id }}, '{{ $f }}', localValue, 'child')"
                                                        class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify p-3 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="p-2 bg-gray-50 text-center border-t">
                                        <button type="button" @click="addParent('jabatan')" class="text-blue-600 hover:text-blue-800 font-bold text-[11px] py-1 px-3 rounded hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-plus mr-1"></i> Tambah Sasaran Kinerja Jabatan
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB PERILAKU -->
            <div  class="bg-white rounded-xl mb-8 shadow-sm border border-gray-200 overflow-hidden" >
                <div class="bg-emerald-600 px-6 py-4">
                    <h2 class="text-lg font-bold text-white tracking-wide">D. PERILAKU KERJA</h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="w-full text-xs text-left border border-gray-200">
                            <thead class="bg-gray-50 font-bold uppercase text-gray-600 text-[11px]">
                                <tr>
                                    <th class="px-4 py-3 border w-[15%]">Indikator</th>
                                    <th class="px-4 py-3 border w-[18%]">Sub Indikator</th>
                                    <th class="px-4 py-3 border w-[18%]">Definisi Operasional</th>
                                    <th class="px-4 py-3 border w-[15%]">Bukti Utama</th>
                                    <th class="px-4 py-3 border w-[19%]">Target Sesuai Ekspektasi</th>
                                    <th class="px-4 py-3 border w-[15%]">Acuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700">
                                @foreach($perilaku as $parent)
                                    @foreach($parent->children as $idx => $child)
                                    <tr>
                                        @if($idx == 0)
                                        <td class="p-0 border align-top relative group" rowspan="{{ $parent->children->count() }}" x-data="{ localValue: {{ json_encode($parent->sasaran_kinerja) }} }">
                                            <div class="invisible whitespace-pre-wrap pt-3 pl-3 pr-8 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue" @change="saveFieldDirect({{ $parent->id }}, 'sasaran_kinerja', localValue, 'parent')" class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify pt-3 pl-3 pr-8 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <button type="button" @click="addChild({{ $parent->id }})" class="pointer-events-auto text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded px-2 py-1 text-[10px] font-bold shadow-sm flex items-center gap-1 border border-blue-200" title="Tambah Sub Indikator">
                                                    <i class="fas fa-plus"></i> Tambah Sub Indikator
                                                </button>
                                                <button type="button" @click="deleteParent({{ $parent->id }})" class="pointer-events-auto text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Sasaran Kinerja">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif

                                        @foreach(['indikator_kinerja', 'definisi_operasional', 'bukti_utama', 'target_ekspektasi', 'acuan'] as $f)
                                        <td class="p-0 border align-top relative group" x-data="{ localValue: {{ json_encode($child->$f) }}{{ $f == 'indikator_kinerja' ? ', butuhLampiran: '.($child->butuh_lampiran ? 'true' : 'false').', labelLampiran: '.json_encode($child->label_lampiran ?? '') : '' }} }">
                                            <div class="invisible whitespace-pre-wrap p-3 pb-10 text-[11px] leading-relaxed break-words min-h-[60px]" x-text="localValue + ' '"></div>
                                            <textarea x-model="localValue"
                                                  @change="saveFieldDirect({{ $child->id }}, '{{ $f }}', localValue, 'child')"
                                                  class="absolute inset-0 w-full h-full border-transparent hover:bg-gray-50 focus:bg-white text-[11px] text-justify p-3 pb-10 outline-none focus:ring-2 focus:ring-blue-100 transition-all resize-none overflow-hidden text-gray-700 leading-relaxed block m-0"></textarea>
                                            @if($f == 'indikator_kinerja')
                                            <div class="absolute bottom-1.5 left-1 right-1 flex justify-between items-end pointer-events-none z-10">
                                                <label class="pointer-events-auto flex items-center gap-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded px-1.5 py-0.5 text-[9px] font-bold text-amber-700 cursor-pointer shadow-sm" title="Centang jika baris ini wajib melampirkan bukti saat realisasi pegawai">
                                                    <input type="checkbox" x-model="butuhLampiran" @change="saveFieldDirect({{ $child->id }}, 'butuh_lampiran', butuhLampiran, 'child')" class="w-3 h-3 accent-amber-600 cursor-pointer">
                                                    <i class="fas fa-paperclip"></i> Lampiran
                                                </label>
                                                <input type="text" x-show="butuhLampiran" x-model="labelLampiran" @change="saveFieldDirect({{ $child->id }}, 'label_lampiran', labelLampiran, 'child')" placeholder="Nama lampiran..." class="pointer-events-auto text-[9px] border border-amber-300 rounded px-1 py-0.5 w-20 mt-0.5 outline-none focus:ring-1 focus:ring-amber-400">
                                                @if($parent->children->count() > 1)
                                                <button type="button" @click="deleteChild({{ $child->id }})" class="text-red-400 hover:text-red-600 bg-white hover:bg-red-50 rounded-full p-1.5 shadow-sm border border-red-100" title="Hapus Baris Ini">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="p-2 bg-gray-50 text-center border-t">
                                        <button type="button" @click="addParent('perilaku')" class="text-blue-600 hover:text-blue-800 font-bold text-[11px] py-1 px-3 rounded hover:bg-blue-100 transition-colors">
                                            <i class="fas fa-plus mr-1"></i> Tambah Perilaku Kerja
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('acuanEditor', () => ({

                    showNotification: false,
                    notificationMessage: '',

                    addParent(kategori) {
                        fetch('{{ route("superadmin.skp-dosen-kualitatif.acuan.add-parent") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ kategori: kategori })
                        }).then(res => res.json()).then(data => { if(data.success) window.location.reload(); });
                    },
                    deleteParent(id) {
                        if(!confirm('Hapus baris ini beserta seluruh isinya?')) return;
                        fetch(`/superadmin/skp-dosen-kualitatif-acuan/delete-parent/${id}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        }).then(res => res.json()).then(data => { if(data.success) window.location.reload(); });
                    },
                    addChild(parentId) {
                        fetch('{{ route("superadmin.skp-dosen-kualitatif.acuan.add-child") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ parent_id: parentId })
                        }).then(res => res.json()).then(data => { if(data.success) window.location.reload(); });
                    },
                    deleteChild(id) {
                        if(!confirm('Hapus baris ini?')) return;
                        fetch(`/superadmin/skp-dosen-kualitatif-acuan/delete-child/${id}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        }).then(res => res.json()).then(data => { if(data.success) window.location.reload(); });
                    },
                    saveFieldDirect(id, field, value, type) {
                        fetch('{{ route("superadmin.skp-dosen-kualitatif.acuan.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id: id,
                                field: field,
                                value: value,
                                type: type
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.notificationMessage = data.message;
                                this.showNotification = true;
                                setTimeout(() => { this.showNotification = false; }, 3000);
                            }
                        });
                    }
                }));
            });
        </script>
        <style>
            [] { display: none !important; }
        </style>
    @endsection
</x-app-layout>
