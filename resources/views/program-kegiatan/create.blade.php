@extends('layouts.app')

@section('page-title', 'Tambah Program Fakultas')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')

@php
    $importData = session('import_data', []);
    $oldData = old('programs', []);
    $initialData = count($oldData) > 0 ? $oldData : $importData;
@endphp

<style>
    .table-input {
        width: 100%;
        border: 1px solid #f1f5f9;
        padding: 12px 12px;
        font-size: 11px;
        background: #fff;
        border-radius: 4px;
        transition: all 0.2s ease;
        font-weight: 700;
        line-height: 1.4;
        display: block;
        text-align: left;
    }

    .table-input:focus {
        outline: none;
        border: 1px solid #3b82f6;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.05);
        z-index: 10;
        position: relative;
    }

    textarea.table-input {
        resize: none;
        overflow: hidden;
        min-height: 80px;
    }

    /* Hide spinners */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }

    [x-cloak] { display: none !important; }

    .custom-scrollbar::-webkit-scrollbar {
        width: 16px;
        height: 16px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
        border: 4px solid #f1f5f9;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>

<div x-data="programManager()" class="w-full mx-auto pb-20 px-4 md:px-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
        <div>
            <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Master <span class="text-blue-600">Program Fakultas</span></h2>
            <p class="text-gray-500 text-sm font-bold mt-1 uppercase tracking-widest">Penyusunan Katalog Program & Kegiatan Anggaran</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button"
                @click="globalPeriodeId ? document.getElementById('importModal').classList.remove('hidden') : null"
                :disabled="!globalPeriodeId"
                :class="!globalPeriodeId ? 'bg-gray-300 cursor-not-allowed shadow-none opacity-50' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20 hover:-translate-y-0.5'"
                class="text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg flex items-center gap-2 transition-all">
                <i class="fas fa-file-excel"></i> Import Excel
            </button>
            <div class="bg-blue-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg shadow-blue-500/20">
                <span x-text="programs.length"></span> Baris Kegiatan
            </div>
            <a href="{{ route('program-kegiatan.index') }}" class="bg-white text-gray-700 px-6 py-2.5 rounded-xl font-bold border-2 border-gray-200 hover:bg-gray-50 transition shadow-sm text-xs uppercase">Batal</a>
        </div>
    </div>

    {{-- Alert Messages --}}
    <div class="space-y-4 mb-8">
        <template x-if="!globalPeriodeId">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-2xl flex items-center gap-5 animate-pulse shadow-sm">
                <div class="bg-amber-100 p-3 rounded-xl text-amber-600 shadow-inner">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black text-amber-900 uppercase tracking-widest">Tahun Anggaran Belum Dipilih</h4>
                    <p class="text-[11px] font-bold text-amber-700 uppercase mt-1">Silakan pilih Tahun Anggaran pada form di bawah untuk mengaktifkan fitur Import dan Input rincian kegiatan.</p>
                </div>
            </div>
        </template>
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-5 rounded-xl shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3 mb-2 text-red-800 font-bold">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3 class="text-base uppercase">Gagal Menyimpan Data</h3>
                </div>
                <ul class="list-disc ml-8 text-xs text-red-600 font-black space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl shadow-sm font-black text-xs uppercase flex items-center gap-3 animate-fadeIn">
                <i class="fas fa-times-circle text-base"></i> {{ session('error') }}
            </div>
        @endif
    </div>

    <form action="{{ route('program-kegiatan.store') }}" method="POST">
        @csrf

        {{-- GLOBAL PERIODE SELECTION --}}
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mb-8">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="flex-shrink-0 bg-blue-50 w-12 h-12 rounded-xl flex items-center justify-center text-blue-600 shadow-inner">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <div class="flex-grow w-full">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Pilih Tahun Anggaran Pelaksanaan *</label>
                    <select x-model="globalPeriodeId" @change="syncGlobalPeriode()"
                        class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl p-3 focus:bg-white focus:border-blue-500 focus:ring-0 font-bold text-sm text-gray-900 transition-all cursor-pointer shadow-sm"
                        required>
                        <option value="">-- Pilih Periode Anggaran --</option>
                        @foreach($periodeAnggaran as $pa)
                            <option value="{{ $pa->id }}">
                                Tahun Anggaran {{ $pa->tahun_anggaran }} (Akademik {{ $pa->tahun_akademik }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-shrink-0 grid grid-cols-2 gap-4 w-full md:w-auto">
                    <div class="bg-blue-50/50 px-4 py-2.5 rounded-xl border border-blue-100 flex flex-col items-center min-w-[140px]">
                        <span class="text-[9px] font-black text-blue-400 uppercase tracking-tighter">Tahun Anggaran</span>
                        <span class="text-sm font-black text-blue-800" x-text="getGlobalYear() || '-'"></span>
                    </div>
                    <div class="bg-indigo-50/50 px-4 py-2.5 rounded-xl border border-indigo-100 flex flex-col items-center min-w-[140px]">
                        <span class="text-[9px] font-black text-indigo-400 uppercase tracking-tighter">Tahun Akademik</span>
                        <span class="text-[10px] font-black text-indigo-800 text-center" x-text="getGlobalAcademic() || '-'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE FORM --}}
        <div class="space-y-4 mb-8">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"
                :class="!globalPeriodeId ? 'opacity-60 cursor-not-allowed' : ''">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-list-ul text-blue-600"></i>
                        <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Rincian Katalog Program & Kegiatan</h3>
                    </div>
                    <button type="button" @click="addProgram()" :disabled="!globalPeriodeId"
                        :class="!globalPeriodeId ? 'bg-gray-300 cursor-not-allowed shadow-none' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30'"
                        class="text-white px-4 py-1.5 rounded-lg font-black text-[10px] uppercase tracking-wider transition-all shadow-lg flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto custom-scrollbar" :class="!globalPeriodeId ? 'pointer-events-none' : ''">
                    <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black text-gray-500 uppercase tracking-tight border-b border-gray-200">
                            <th class="px-2 py-4 w-12 text-center border-r border-gray-200">No</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Unit Kerja</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Sasaran</th>
                            <th class="px-4 py-4 min-w-[300px] border-r border-gray-200">Nama Program</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Jenis Kegiatan</th>
                            <th class="px-4 py-4 min-w-[350px] border-r border-gray-200 bg-blue-50/30">Nama Kegiatan</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Sub Kegiatan</th>
                            <th class="px-4 py-4 min-w-[200px] border-r border-gray-200">Semester</th>
                            <th class="px-4 py-4 min-w-[350px] border-r border-gray-200">Indikator</th>
                            <th class="px-4 py-4 min-w-[160px] border-r border-gray-200">Target Capaian</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Waktu Pelaksanaan</th>
                            <th class="px-4 py-4 min-w-[180px] border-r border-gray-200">Tahun Anggaran</th>
                            <th class="px-4 py-4 min-w-[180px] border-r border-gray-200">Tahun Akademik</th>
                            <th class="px-4 py-4 min-w-[160px] border-r border-gray-200">Per Mahasiswa</th>
                            <th class="px-4 py-4 min-w-[200px] border-r border-gray-200 bg-emerald-50/30 text-emerald-800">Anggaran</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Per Program Studi</th>
                            <th class="px-2 py-4 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="(program, index) in programs" :key="index">
                            <tr class="hover:bg-blue-50/10 transition-colors group">
                                <td class="p-0 text-center align-top font-bold text-gray-400 border-r border-gray-100 text-[10px] bg-gray-50/30 pt-4" x-text="index + 1"></td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.unit_kerja" :name="`programs[${index}][unit_kerja]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Unit Kerja..." required :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.sasaran" :name="`programs[${index}][sasaran]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Sasaran..." :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.nama_program" :name="`programs[${index}][nama_program]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Nama Program..." required :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.jenis_kegiatan" :name="`programs[${index}][jenis_kegiatan]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Jenis Kegiatan..." :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.nama_kegiatan" :name="`programs[${index}][nama_kegiatan]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 text-blue-900 bg-white"
                                            placeholder="Nama Kegiatan..." required :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.sub_kegiatan" :name="`programs[${index}][sub_kegiatan]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Sub Kegiatan..." :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <select x-model="program.semester" :name="`programs[${index}][semester]`"
                                            class="table-input w-full h-full min-h-[80px] border-none rounded-none focus:ring-0 text-[10px] appearance-none cursor-pointer bg-white" :disabled="!globalPeriodeId">
                                            <option value="">-- Semester --</option>
                                            <option :value="'Semester Ganjil ' + getFormattedYear()" x-text="'Ganjil ' + getFormattedYear()"></option>
                                            <option :value="'Semester Genap ' + getFormattedYear()" x-text="'Genap ' + getFormattedYear()"></option>
                                        </select>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.indikator" :name="`programs[${index}][indikator]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 text-amber-700 bg-white"
                                            placeholder="Indikator..." :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <div class="relative w-full h-full min-h-[80px]">
                                            <input type="number" step="0.01" min="0" 
                                                onkeydown="preventMinus(event)" 
                                                oninput="if(this.value < 0) this.value = 0"
                                                x-model="program.target_capaian" :name="`programs[${index}][target_capaian]`"
                                                class="table-input w-full h-full border-none rounded-none focus:ring-0 font-mono text-left bg-white" placeholder="0.00" :disabled="!globalPeriodeId">
                                            <span class="absolute right-3 top-4 text-[8px] font-black text-gray-300 pointer-events-none">%</span>
                                        </div>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <textarea x-model="program.waktu_pelaksanaan" :name="`programs[${index}][waktu_pelaksanaan]`"
                                            class="table-input w-full h-full resize-none border-none rounded-none focus:ring-0 bg-white" placeholder="Waktu..." :disabled="!globalPeriodeId"
                                            @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                            x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <input type="text" x-model="program.tahun_anggaran" :name="`programs[${index}][tahun_anggaran]`"
                                            onkeydown="preventMinus(event)"
                                            oninput="this.value = this.value.replace(/-/g, '')"
                                            class="table-input w-full h-full min-h-[80px] border-none rounded-none focus:ring-0 text-[9px] bg-white text-left" placeholder="Contoh: 2025/2026">
                                    </td>
                                    <td class="p-0 border-r border-gray-100 align-top">
                                        <input type="text" x-model="program.tahun_akademik" :name="`programs[${index}][tahun_akademik]`"
                                            onkeydown="preventMinus(event)"
                                            oninput="this.value = this.value.replace(/-/g, '')"
                                            class="table-input w-full h-full min-h-[80px] border-none rounded-none focus:ring-0 text-[9px] bg-white text-left" placeholder="Contoh: 2025/2026">
                                    </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <input type="number" min="0" 
                                        onkeydown="preventMinus(event)" 
                                        oninput="if(this.value < 0) this.value = 0"
                                        x-model.number="program.per_mhs" :name="`programs[${index}][per_mhs]`"
                                        class="table-input w-full h-full min-h-[80px] border-none rounded-none focus:ring-0 text-left font-mono bg-white" placeholder="0" :disabled="!globalPeriodeId">
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <div class="relative w-full h-full min-h-[80px]">
                                        <span class="absolute left-3 top-4 text-[8px] font-black text-emerald-300 pointer-events-none">Rp</span>
                                        <br>
                                        <input type="number" min="0" 
                                            onkeydown="preventMinus(event)" 
                                            oninput="if(this.value < 0) this.value = 0; program.anggaran_pagu = program.anggaran_per_prodi; calculateAverage(program)"
                                            x-model.number="program.anggaran_per_prodi" :name="`programs[${index}][anggaran_per_prodi]`"
                                            class="table-input w-full h-full border-none rounded-none focus:ring-0 pl-8 text-left text-emerald-700 font-mono bg-white focus:bg-white"
                                            placeholder="0" required :disabled="!globalPeriodeId">
                                    </div>
                                    <input type="hidden" :name="`programs[${index}][anggaran_pagu]`" :value="program.anggaran_pagu">
                                    <input type="hidden" :name="`programs[${index}][periode_id]`" :value="program.periode_id">
                                    <input type="hidden" :name="`programs[${index}][tipe_alokasi]`" :value="program.tipe_alokasi">
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <div class="flex flex-col w-full h-full min-h-[80px]">
                                        <div class="flex items-center justify-between p-1 bg-gray-100 border-b border-gray-200">
                                            <button type="button" @click="program.tipe_alokasi = 'Rata'; calculateAverage(program)" :disabled="!globalPeriodeId"
                                                :class="program.tipe_alokasi === 'Rata' ? 'bg-white text-blue-600 shadow-sm border-gray-200' : 'text-gray-400 border-transparent hover:text-gray-600'"
                                                class="flex-1 text-[7px] font-black uppercase py-1 border rounded transition-all">Rata</button>
                                            <button type="button" @click="openCustomModal(index)" :disabled="!globalPeriodeId"
                                                :class="program.tipe_alokasi === 'Custom' ? 'bg-white text-orange-600 shadow-sm border-gray-200' : 'text-gray-400 border-transparent hover:text-gray-600'"
                                                class="flex-1 text-[7px] font-black uppercase py-1 border rounded transition-all ml-1">Custom</button>
                                        </div>

                                        <div class="flex-grow flex items-start">
                                            <div x-show="program.tipe_alokasi === 'Rata'" class="w-full h-full">
                                                <input type="number" min="0" 
                                                    onkeydown="preventMinus(event)" 
                                                    oninput="if(this.value < 0) this.value = 0"
                                                    x-model.number="program.per_prodi" :name="`programs[${index}][per_prodi]`"
                                                    class="table-input w-full h-full border-none rounded-none focus:ring-0 text-left py-3 px-3 text-blue-800 font-mono bg-white"
                                                    placeholder="Rp 0" :disabled="!globalPeriodeId">
                                            </div>

                                            <div x-show="program.tipe_alokasi === 'Custom'" class="w-full h-full">
                                                <button type="button" @click="openCustomModal(index)" :disabled="!globalPeriodeId"
                                                    class="w-full h-full px-3 py-3 bg-white border-none text-orange-700 rounded-none text-[8px] font-black uppercase hover:bg-orange-50 transition-colors flex flex-col items-start gap-1">
                                                    <span>Custom Alokasi</span>
                                                    <span class="font-mono text-[10px]" x-text="formatRupiah(getTotalCustom(program))"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <template x-for="(val, prodi) in program.alokasi_custom" :key="prodi">
                                            <input type="hidden" :name="`programs[${index}][alokasi_custom][${prodi}]`" :value="val">
                                        </template>
                                    </div>
                                </td>
                                <td class="p-0 text-center align-top bg-gray-50/30">
                                    <button type="button" @click="removeProgram(index)" :disabled="!globalPeriodeId"
                                        class="w-full h-full min-h-[80px] flex items-center justify-center p-3 text-red-500 hover:bg-red-500 hover:text-white transition-all disabled:text-gray-300 disabled:hover:bg-transparent">
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <template x-if="programs.length === 0">
                <div class="p-20 text-center bg-gray-50 flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center text-gray-400 mb-4">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Belum ada rincian kegiatan</p>
                    <button type="button" @click="addProgram()" :disabled="!globalPeriodeId" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-xl font-black text-[10px] uppercase tracking-wider hover:bg-blue-700 transition shadow-lg shadow-blue-500/20 disabled:bg-gray-300 disabled:shadow-none disabled:cursor-not-allowed">Klik untuk Menambah Baris</button>
                </div>
            </template>
        </div>
    </div>

        {{-- Bottom Actions --}}
        <div class="mt-12 mb-20 flex justify-center">
            <button type="submit" :disabled="!validateAll() || !globalPeriodeId"
                :class="!validateAll() || !globalPeriodeId ? 'bg-gray-400 cursor-not-allowed opacity-50' : 'bg-gray-900 hover:bg-black hover:-translate-y-1 shadow-xl shadow-black/20'"
                class="w-full max-w-sm text-white py-4 rounded-2xl transition-all font-black text-sm uppercase tracking-widest flex items-center justify-center gap-4 group">
                <i class="fas fa-save group-hover:scale-110 transition-transform"></i>
                Simpan (<span x-text="programs.length"></span>) Data Program
            </button>
        </div>
    </form>

    {{-- MODAL CUSTOM ALOKASI --}}
    <div x-show="showCustomModal" x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto bg-gray-900/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-4xl w-full p-8 overflow-hidden transform transition-all animate-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
                <div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Alokasi <span class="text-orange-600">Per Program Studi</span></h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase mt-1 tracking-widest" x-text="activeProgram ? activeProgram.nama_kegiatan : ''"></p>
                </div>
                <button @click="closeCustomModal()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                <template x-if="activeProgram">
                    <template x-for="prodi in prodis" :key="prodi">
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 group hover:border-orange-200 transition-all">
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-tighter mb-2 group-hover:text-orange-600 transition-colors" x-text="prodi"></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-gray-300">Rp</span>
                                <input type="number" min="0" 
                                    onkeydown="preventMinus(event)" 
                                    oninput="if(this.value < 0) this.value = 0"
                                    x-model.number="activeProgram.alokasi_custom[prodi]" 
                                    class="w-full bg-white border border-gray-200 rounded-xl py-2.5 pl-8 pr-4 text-right text-xs font-black text-gray-800 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/5 transition-all font-mono">

                            </div>
                        </div>
                    </template>
                </template>
            </div>

            <div class="border-t border-gray-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-8">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Anggaran Pagu</span>
                        <span class="text-xl font-black text-blue-900 font-mono" x-text="formatRupiah(activeProgram ? activeProgram.anggaran_per_prodi : 0)"></span>
                    </div>
                    <div class="w-px h-10 bg-gray-200"></div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Ter-Alokasi</span>
                        <span class="text-xl font-black font-mono" :class="activeProgram && getTotalCustom(activeProgram) > activeProgram.anggaran_per_prodi ? 'text-red-600' : 'text-emerald-600'"
                            x-text="formatRupiah(activeProgram ? getTotalCustom(activeProgram) : 0)"></span>
                    </div>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <template x-if="activeProgram && getTotalCustom(activeProgram) > activeProgram.anggaran_per_prodi">
                        <div class="flex items-center gap-2 text-red-600 text-[10px] font-black uppercase bg-red-50 px-4 py-2 rounded-xl animate-pulse">
                            <i class="fas fa-exclamation-triangle"></i> Melebihi Pagu!
                        </div>
                    </template>
                    <button @click="closeCustomModal()" 
                        :disabled="activeProgram && getTotalCustom(activeProgram) > activeProgram.anggaran_per_prodi"
                        :class="activeProgram && getTotalCustom(activeProgram) > activeProgram.anggaran_per_prodi ? 'bg-gray-400 cursor-not-allowed shadow-none' : 'bg-gray-900 hover:bg-black shadow-xl'"
                        class="flex-grow md:flex-grow-0 text-white px-12 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition">Terapkan & Simpan</button>
                </div>
            </div>
        </div>
    </div>
    {{-- MODAL IMPORT EXCEL --}}
    <div id="importModal" class="hidden fixed inset-0 z-[110] overflow-y-auto bg-gray-900/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-8 overflow-hidden transform transition-all animate-in zoom-in duration-300">
            <form action="{{ route('program-kegiatan.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="selected_periode_id" :value="globalPeriodeId">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600">
                        <i class="fas fa-file-excel text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Import <span class="text-emerald-600">Excel</span></h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Impor Data Katalog Program Fakultas</p>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-2xl text-[10px] text-amber-800 font-bold uppercase leading-relaxed tracking-wider">
                    <p class="mb-2 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> Header Kolom (Wajib Baris ke-1):</p>
                    <code class="text-[10px] bg-white/50 px-2 py-2 rounded-lg border border-amber-200 block lowercase overflow-x-auto whitespace-nowrap">
                        unit_kerja | sasaran | nama_program | jenis_kegiatan | nama_kegiatan | sub_kegiatan | per_mhs | anggaran | anggaran_pagu | per_prodi | semester | indikator | target_capaian | waktu_pelaksanaan
                    </code>
                    <p class="mt-2 text-[9px]">* Nama program & nama kegiatan wajib diisi. Kolom lainnya opsional (mengikuti header di atas).</p>
                </div>

                <div class="mb-8">
                    <label class="block w-full text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1 text-center">Pilih File Master Program</label>
                    <div class="relative group">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center transition-all group-hover:border-emerald-500 group-hover:bg-emerald-50/30">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-3 transition-colors group-hover:text-emerald-500"></i>
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest group-hover:text-emerald-700">Seret file atau klik di sini</p>
                            <p class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-tighter">Maksimum 5MB (XLSX, XLS)</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-700 transition shadow-xl shadow-emerald-500/20 flex items-center justify-center gap-3">
                        <i class="fas fa-upload"></i> Proses Import
                    </button>
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="w-full bg-gray-100 text-gray-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function preventMinus(e) {
        if (e.key === '-' || e.key === 'e') {
            e.preventDefault();
        }
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('programManager', () => {
            const prodisData = @json($prodis);
            const initialData = @json($initialData);
            const periodeData = @js($periodeAnggaran);

            const getEmptyProgram = () => {
                let defaultCustom = {};
                prodisData.forEach(p => defaultCustom[p] = 0);

                return {
                    periode_id: '', unit_kerja: 'Fakultas Teknik', sasaran: '', nama_program: '', jenis_kegiatan: '',
                    nama_kegiatan: '', sub_kegiatan: '',
                    indikator: '', target_capaian: '', semester: '', waktu_pelaksanaan: '',
                    per_mhs: 0, anggaran_per_prodi: 0, anggaran_pagu: 0,
                    tahun_anggaran: '', tahun_akademik: '',
                    tipe_alokasi: 'Rata', per_prodi: 0, alokasi_custom: defaultCustom
                };
            };

            let parsedPrograms = [];
            if (initialData && initialData.length > 0) {
                parsedPrograms = initialData.map(item => {
                    // Pastikan alokasi_custom adalah object dan memiliki semua prodi
                    if (!item.alokasi_custom || Array.isArray(item.alokasi_custom) || typeof item.alokasi_custom !== 'object') {
                        let objCustom = {};
                        prodisData.forEach(p => objCustom[p] = 0);
                        item.alokasi_custom = objCustom;
                    } else {
                        // Pastikan semua prodi ada di dalam object
                        prodisData.forEach(p => {
                            if (item.alokasi_custom[p] === undefined) {
                                item.alokasi_custom[p] = 0;
                            }
                        });
                    }

                    // Default values for missing fields
                    if(item.tipe_alokasi === undefined) item.tipe_alokasi = 'Rata';
                    if(item.per_mhs === undefined) item.per_mhs = 0;

                    // Prioritaskan anggaran_per_prodi, jika 0 atau null, coba ambil dari anggaran_pagu
                    if(!item.anggaran_per_prodi || item.anggaran_per_prodi == 0) {
                        item.anggaran_per_prodi = item.anggaran_pagu || 0;
                    }
                    if(!item.anggaran_pagu || item.anggaran_pagu == 0) {
                        item.anggaran_pagu = item.anggaran_per_prodi || 0;
                    }

                    if(item.indikator === undefined) item.indikator = '';
                    if(item.target_capaian === undefined) item.target_capaian = '';
                    if(item.semester === undefined) item.semester = '';
                    if(item.waktu_pelaksanaan === undefined) item.waktu_pelaksanaan = '';
                    if(item.tahun_anggaran === undefined) item.tahun_anggaran = '';
                    if(item.tahun_akademik === undefined) item.tahun_akademik = '';

                    return item;
                });
            } else {
                parsedPrograms = [getEmptyProgram()];
            }
            return {
                prodis: prodisData,
                programs: parsedPrograms,
                globalPeriodeId: parsedPrograms.length > 0 && parsedPrograms[0].periode_id ? parsedPrograms[0].periode_id : '',
                showCustomModal: false,
                activeProgram: null,

                syncGlobalPeriode() {
                    const p = periodeData.find(x => x.id == this.globalPeriodeId);
                    if(p) {
                        this.programs.forEach(prog => {
                            prog.periode_id = p.id;
                        });
                    }
                },

                getFormattedYear() {
                    const p = periodeData.find(x => x.id == this.globalPeriodeId);
                    if(!p) return '';
                    const year = parseInt(p.tahun_anggaran);
                    return `${year}/${year + 1}`;
                },

                getGlobalYear() {
                    return this.getFormattedYear();
                },

                getGlobalAcademic() {
                    return this.getFormattedYear();
                },

                addProgram() {
                    const newProg = getEmptyProgram();
                    const p = periodeData.find(x => x.id == this.globalPeriodeId);
                    if(p) {
                        newProg.periode_id = p.id;
                    }
                    this.programs.push(newProg);
                },

                removeProgram(index) {
                    if(confirm('Hapus baris kegiatan ini?')) {
                        this.programs.splice(index, 1);
                    }
                },

                openCustomModal(index) {
                    this.activeProgram = this.programs[index];
                    this.activeProgram.tipe_alokasi = 'Custom';
                    this.showCustomModal = true;
                },

                closeCustomModal() {
                    this.showCustomModal = false;
                    this.activeProgram = null;
                },

                calculateAverage(program) {
                    if (program.tipe_alokasi === 'Rata') {
                        const total = parseFloat(program.anggaran_per_prodi) || 0;
                        const count = this.prodis.length || 1;
                        program.per_prodi = Math.round(total / count);
                    }
                },

                getTotalCustom(program) {
                    let total = 0;
                    if(!program.alokasi_custom) return 0;
                    for (const key in program.alokasi_custom) {
                        total += Math.round(parseFloat(program.alokasi_custom[key]) || 0);
                    }
                    return total;
                },

                getSisa(program) {
                    return Math.round(parseFloat(program.anggaran_per_prodi) || 0) - this.getTotalCustom(program);
                },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
                },

                formatCompact(number) {
                    if (number >= 1000000) return (number / 1000000).toFixed(1) + ' jt';
                    if (number >= 1000) return (number / 1000).toFixed(0) + ' rb';
                    return number;
                },

                validateAll() {
                    if (this.programs.length === 0) return false;
                    for (let i = 0; i < this.programs.length; i++) {
                        let p = this.programs[i];
                        if (p.tipe_alokasi === 'Custom' && this.getTotalCustom(p) > p.anggaran_per_prodi) {
                            return false;
                        }
                    }
                    return true;
                }
            }
        });
    });

    document.addEventListener('wheel', function(event) {
        if (document.activeElement.type === 'number') {
            document.activeElement.blur();
        }
    }, {
        passive: false
    });
</script>
@endpush
