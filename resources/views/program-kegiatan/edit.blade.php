@extends('layouts.app')

@section('page-title', 'Edit Master Program Fakultas')

@section('sidebar')
    @include('partials.sidebar')
@endsection

@section('content')

@php
    $initialData = [[
        'id'                => $program->id,
        'unit_kerja'        => old('unit_kerja', $program->unit_kerja),
        'sasaran'           => old('sasaran', $program->sasaran),
        'nama_program'      => old('nama_program', $program->nama_program),
        'jenis_kegiatan'    => old('jenis_kegiatan', $program->jenis_kegiatan),
        'nama_kegiatan'     => old('nama_kegiatan', $program->nama_kegiatan),
        'sub_kegiatan'      => old('sub_kegiatan', $program->sub_kegiatan),
        'semester'          => old('semester', $program->semester),
        'indikator'         => old('indikator', $program->indikator),
        'target_capaian'    => old('target_capaian', $program->target_capaian),
        'waktu_pelaksanaan' => old('waktu_pelaksanaan', $program->waktu_pelaksanaan),
        'tahun_anggaran'    => old('tahun_anggaran', $program->tahun_anggaran),
        'tahun_akademik'    => old('tahun_akademik', $program->tahun_akademik),
        'periode_id'        => old('periode_id', $program->periode_id),
        'anggaran_pagu'     => old('anggaran_pagu', (int)$program->anggaran_pagu),
        'tipe_alokasi'      => old('tipe_alokasi', $program->tipe_alokasi),
        'per_prodi'         => old('per_prodi', (int)$program->per_prodi),
        'alokasi_custom'    => old('alokasi_custom', $program->alokasi_custom ?? []),
        'per_mhs'           => old('per_mhs', $program->per_mhs),
        'anggaran_per_prodi'=> old('anggaran_per_prodi', (int)$program->anggaran_per_prodi),
    ]];
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
        min-height: 34px;
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
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Edit <span class="text-blue-600">Program Fakultas</span></h2>
            <p class="text-gray-500 font-bold mt-1 uppercase text-xs tracking-widest">Pembaruan Katalog Program & Kegiatan Anggaran</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('program-kegiatan.index') }}" 
                class="bg-white text-gray-700 px-6 py-2.5 rounded-xl font-bold border-2 border-gray-200 hover:bg-gray-50 transition shadow-sm text-xs uppercase">
                Batal / Kembali
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    <div class="space-y-4 mb-8">
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-5 rounded-xl shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3 mb-2 text-red-800 font-bold">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3 class="text-base uppercase">Gagal Memperbarui Data</h3>
                </div>
                <ul class="list-disc ml-8 text-xs text-red-600 font-black space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <form action="{{ route('program-kegiatan.update', $program->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1 mb-2 block">Periode Anggaran Pelaksanaan *</label>
                        <select name="periode_id" x-model="programs[0].periode_id"
                            @change="const p = @js($periodeAnggaran).find(x => x.id == $event.target.value); 
                                     if(p) { programs[0].tahun_anggaran = p.tahun_anggaran; programs[0].tahun_akademik = p.tahun_akademik; }"
                            class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl p-4 focus:bg-white focus:border-blue-500 focus:ring-0 font-bold text-sm text-gray-900 transition-all cursor-pointer shadow-sm" required>
                            <option value="">-- Pilih Periode --</option>
                            @foreach($periodeAnggaran as $pa)
                                <option value="{{ $pa->id }}">
                                    Tahun Anggaran {{ $pa->tahun_anggaran }} ({{ $pa->tahun_akademik }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex flex-col justify-center items-center">
                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Tahun Anggaran</span>
                        <span class="text-xl font-black text-blue-900 font-mono" x-text="programs[0].tahun_anggaran || '-'"></span>
                        <input type="hidden" name="tahun_anggaran" x-model="programs[0].tahun_anggaran">
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100 flex flex-col justify-center items-center">
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Tahun Akademik</span>
                        <span class="text-[10px] font-black text-indigo-900 text-center font-mono" x-text="programs[0].tahun_akademik || '-'"></span>
                        <input type="hidden" name="tahun_akademik" x-model="programs[0].tahun_akademik">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse border border-gray-100 rounded-xl overflow-hidden table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black text-gray-500 uppercase tracking-tight border-b border-gray-200">
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
                            <th class="px-4 py-4 min-w-[160px] border-r border-gray-200">Per Mahasiswa</th>
                            <th class="px-4 py-4 min-w-[200px] border-r border-gray-200 bg-emerald-50/30 text-emerald-800">Anggaran</th>
                            <th class="px-4 py-4 min-w-[250px] border-r border-gray-200">Per Program Studi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(program, index) in programs" :key="index">
                            <tr class="hover:bg-blue-50/10 transition-colors">
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="unit_kerja" x-model="program.unit_kerja"
                                        class="table-input" required
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="sasaran" x-model="program.sasaran"
                                        class="table-input"
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="nama_program" x-model="program.nama_program"
                                        class="table-input" required
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="jenis_kegiatan" x-model="program.jenis_kegiatan"
                                        class="table-input"
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top bg-blue-50/5">
                                    <textarea name="nama_kegiatan" x-model="program.nama_kegiatan"
                                        class="table-input text-blue-900 border-blue-100" required
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="sub_kegiatan" x-model="program.sub_kegiatan"
                                        class="table-input"
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <select name="semester" x-model="program.semester"
                                        class="table-input w-full h-full min-h-[80px] border-none rounded-none focus:ring-0 text-[10px] appearance-none cursor-pointer bg-white">
                                        <option value="">-- Semester --</option>
                                        <option :value="'Semester Ganjil ' + getFormattedYear()" x-text="'Ganjil ' + getFormattedYear()"></option>
                                        <option :value="'Semester Genap ' + getFormattedYear()" x-text="'Genap ' + getFormattedYear()"></option>
                                    </select>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="indikator" x-model="program.indikator"
                                        class="table-input text-amber-700 bg-white"
                                        placeholder="Indikator..."
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <div class="relative w-full h-full min-h-[80px]">
                                        <input type="number" step="0.01" min="0" 
                                            onkeydown="preventMinus(event)" 
                                            oninput="if(this.value < 0) this.value = 0"
                                            name="target_capaian" x-model="program.target_capaian"
                                            class="table-input w-full h-full border-none rounded-none focus:ring-0 font-mono text-left bg-white" placeholder="0.00">
                                        <span class="absolute right-3 top-4 text-[8px] font-black text-gray-300 pointer-events-none">%</span>
                                    </div>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <textarea name="waktu_pelaksanaan" x-model="program.waktu_pelaksanaan"
                                        class="table-input bg-white" placeholder="Waktu..."
                                        @input="$el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = '5px'; $el.style.height = $el.scrollHeight + 'px' })"></textarea>
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <input type="number" min="0" 
                                        onkeydown="preventMinus(event)" 
                                        oninput="if(this.value < 0) this.value = 0"
                                        name="per_mhs" x-model.number="program.per_mhs"
                                        class="table-input text-right font-mono min-h-[80px]">
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top bg-emerald-50/5">
                                    <div class="relative w-full h-full min-h-[80px]">
                                        <span class="absolute left-3 top-4 text-[8px] font-black text-emerald-300 pointer-events-none">Rp</span>
                                        <input type="number" min="0" 
                                            onkeydown="preventMinus(event)" 
                                            oninput="if(this.value < 0) this.value = 0; program.anggaran_pagu = program.anggaran_per_prodi; calculateAverage(program)"
                                            name="anggaran_per_prodi" x-model.number="program.anggaran_per_prodi"
                                            class="table-input w-full h-full border-none rounded-none focus:ring-0 pl-8 text-left text-emerald-700 font-mono bg-white focus:bg-white"
                                            placeholder="0" required>
                                    </div>
                                    <input type="hidden" name="anggaran_pagu" x-model="program.anggaran_pagu">
                                </td>
                                <td class="p-0 border-r border-gray-100 align-top">
                                    <div class="flex flex-col w-full h-full min-h-[80px]">
                                        <div class="flex items-center justify-between p-1 bg-gray-100 border-b border-gray-200">
                                            <button type="button" @click="program.tipe_alokasi = 'Rata'; calculateAverage(program)" 
                                                :class="program.tipe_alokasi === 'Rata' ? 'bg-white text-blue-600 shadow-sm border-gray-200' : 'text-gray-400 border-transparent hover:text-gray-600'"
                                                class="flex-1 text-[7px] font-black uppercase py-1 border rounded transition-all">Rata</button>
                                            <button type="button" @click="openCustomModal(index)" 
                                                :class="program.tipe_alokasi === 'Custom' ? 'bg-white text-orange-600 shadow-sm border-gray-200' : 'text-gray-400 border-transparent hover:text-gray-600'"
                                                class="flex-1 text-[7px] font-black uppercase py-1 border rounded transition-all ml-1">Custom</button>
                                        </div>
                                        
                                        <div class="flex-grow flex items-start">
                                            <div x-show="program.tipe_alokasi === 'Rata'" class="w-full h-full">
                                                <input type="number" min="0" 
                                                    onkeydown="preventMinus(event)" 
                                                    oninput="if(this.value < 0) this.value = 0"
                                                    x-model.number="program.per_prodi" name="per_prodi" 
                                                    class="table-input w-full h-full border-none rounded-none focus:ring-0 text-left py-3 px-3 text-blue-800 font-mono bg-white"
                                                    placeholder="Rp 0">
                                            </div>

                                            <div x-show="program.tipe_alokasi === 'Custom'" class="w-full h-full">
                                                <button type="button" @click="openCustomModal(index)" 
                                                    class="w-full h-full px-3 py-3 bg-white border-none text-orange-700 rounded-none text-[8px] font-black uppercase hover:bg-orange-50 transition-colors flex flex-col items-start gap-1">
                                                    <span>Custom Alokasi</span>
                                                    <span class="font-mono text-[10px]" x-text="formatRupiah(getTotalCustom(program))"></span>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" name="tipe_alokasi" :value="program.tipe_alokasi">
                                        <template x-for="(val, prodi) in program.alokasi_custom" :key="prodi">
                                            <input type="hidden" :name="'alokasi_custom[' + prodi + ']'" :value="val">
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-4">
             <a href="{{ route('program-kegiatan.index') }}" 
                class="bg-gray-100 text-gray-700 px-8 py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-gray-200 transition">
                Batal
            </a>
            <button type="submit" class="bg-blue-600 text-white px-12 py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-500/20">
                Update Data Program
            </button>
        </div>
    </form>

    {{-- MODAL CUSTOM ALOKASI --}}
    <div x-show="showCustomModal" x-cloak 
        class="fixed inset-0 z-[100] overflow-y-auto bg-gray-900/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-4xl w-full p-8 overflow-hidden transform transition-all animate-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
                <div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Atur Alokasi <span class="text-orange-600">Per Prodi</span></h3>
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

            // Pastikan alokasi_custom adalah object dan memiliki semua prodi
            if (initialData && initialData.length > 0) {
                initialData.forEach(item => {
                    if (!item.alokasi_custom || Array.isArray(item.alokasi_custom) || typeof item.alokasi_custom !== 'object') {
                        let objCustom = {};
                        prodisData.forEach(p => objCustom[p] = 0);
                        item.alokasi_custom = objCustom;
                    } else {
                        // Pastikan semua prodi ada di dalam object (jika ada prodi baru)
                        prodisData.forEach(p => {
                            if (item.alokasi_custom[p] === undefined) {
                                item.alokasi_custom[p] = 0;
                            }
                        });
                    }
                });
            }

            return {
                prodis: prodisData,
                programs: initialData,
                showCustomModal: false,
                activeProgram: null,

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

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
                },

                formatCompact(number) {
                    if (number >= 1000000) return (number / 1000000).toFixed(1) + ' jt';
                    if (number >= 1000) return (number / 1000).toFixed(0) + ' rb';
                    return number;
                },

                getFormattedYear() {
                    const p = periodeData.find(x => x.id == this.programs[0].periode_id);
                    if(!p) return '';
                    const year = parseInt(p.tahun_anggaran);
                    return `${year}/${year + 1}`;
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
