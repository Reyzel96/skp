<x-app-layout>
    @section('page-title', 'Edit SKP Dosen')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <style>
            /* Merged Styles dari Code 2 */
            .table-input {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                display: block;
                box-sizing: border-box;
                border: none !important;
                padding: 6px 4px;
                font-size: 10px;
                background: transparent;
                resize: none;
                overflow: hidden;
                min-height: 40px;
                white-space: pre-wrap;
                word-break: break-word;
                line-height: 1.2;
            }

            .table-input:focus {
                outline: 2px solid #3b82f6 !important;
                background-color: #f0f9ff;
                z-index: 10;
                position: relative;
            }

            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            .readonly-ak {
                background-color: transparent !important;
                border: none !important;
                font-weight: 900 !important;
                color: #047857 !important;
                text-align: center;
            }

            .readonly-ak:focus {
                box-shadow: none !important;
            }

            th {
                background-color: #f8fafc;
                border: 1px solid #cbd5e1 !important;
                font-size: 9px;
                text-transform: uppercase;
                letter-spacing: 0.025em;
                text-align: center;
                vertical-align: middle;
                padding: 4px 2px !important;
                color: #475569;
                line-height: 1.1;
                word-wrap: break-word;
                font-weight: 900;
            }

            td {
                border: 1px solid #cbd5e1 !important;
                padding: 0 !important;
                vertical-align: middle !important;
            }

            [x-cloak] {
                display: none !important;
            }

            /* CSS TAMBAHAN UNTUK TABEL PERILAKU */
            .table-skp-custom {
                border-collapse: collapse !important;
                border-style: hidden !important;
            }

            .table-skp-custom th,
            .table-skp-custom td {
                border: 1px solid #000 !important;
                padding: 8px 12px !important;
                color: #000;
            }

            .bg-grey-custom {
                background-color: #d1d5db !important;
            }
        </style>

        <div class="max-w-6xl mx-auto py-6 px-6 font-sans">
            {{-- PERUBAHAN: Form action mengarah ke update, ditambahkan @method('PUT') --}}
            <form action="{{ route('superadmin.skp-dosen.update', $skp->id) }}" method="POST" id="skpForm" x-data='{
                setupModal: false, {{-- PERUBAHAN: Modal default tertutup untuk halaman edit --}}

                {{-- PERUBAHAN: Pre-fill ID dengan data $skp --}}
                pegawaiId: "{{ old("user_id", $skp->user_id) }}",
                periodeId: "{{ old("periode_skp_id", $skp->periode_skp_id) }}",
                penilaiId: "{{ old("pejabat_penilai_id", $skp->pejabat_penilai_id) }}",
                penilaiAkhirId: "{{ old("penilai_akhir_id", $skp->penilai_akhir_id) }}",
                penilaiRank: 99,

                {{-- PERUBAHAN: Pre-fill Detail Pegawai (Sesuaikan relasi dengan model Anda) --}}
                pegawaiName: "{{ $skp->user->profile->nama_lengkap ?? $skp->user->name ?? "......................" }}",
                pegawaiNIP: "{{ $skp->user->profile->nip ?? "......................" }}",
                pegawaiPangkat: "{{ $skp->user->profile->pangkat_golongan ?? "......................" }}",
                pegawaiJabatan: "{{ $skp->user->getRoleNames()->first() ?? "......................" }}",

                {{-- PERUBAHAN: Pre-fill Detail Penilai --}}
                penilaiName: "{{ $skp->pejabatPenilai->profile->nama_lengkap ?? $skp->pejabatPenilai->name ?? "......................" }}",
                penilaiNIP: "{{ $skp->pejabatPenilai->profile->nip ?? "......................" }}",
                penilaiPangkat: "{{ $skp->pejabatPenilai->profile->pangkat_golongan ?? "......................" }}",
                penilaiJabatan: "{{ $skp->pejabatPenilai->getRoleNames()->first() ?? "......................" }}",

                {{-- PERUBAHAN: Pre-fill Detail Penilai Akhir --}}
                penilaiAkhirName: "{{ $skp->atasanPejabatPenilai->profile->nama_lengkap ?? $skp->atasanPejabatPenilai->name ?? "......................" }}",
                penilaiAkhirNIP: "{{ $skp->atasanPejabatPenilai->profile->nip ?? "......................" }}",
                penilaiAkhirPangkat: "{{ $skp->atasanPejabatPenilai->profile->pangkat_golongan ?? "......................" }}",
                penilaiAkhirJabatan: "{{ $skp->atasanPejabatPenilai->getRoleNames()->first() ?? "......................" }}",

                // Variabel Setup Jabatan Pimpinan
                jabatanGenap: "{{ $skp->jabatan_pimpinan_genap ?? "" }}",
                jabatanGasal: "{{ $skp->jabatan_pimpinan_gasal ?? "" }}",

                get isValidSetup() { return this.pegawaiId !== "" && this.periodeId !== "" && this.penilaiId !== "" && this.penilaiAkhirId !== ""; },

                {{-- PERUBAHAN: Pre-fill Perilaku Kerja dari controller --}}
                perilakuCategories: @json($formattedPerilaku ?? []),

                {{-- PERUBAHAN: Pre-fill Tugas Tambahan dari controller --}}
                tugasTambahan: @json($tugasTambahan ?? []),

                addTugasTambahan(jenis = "", detail = "", ak = 1) {
                    this.tugasTambahan.push({ jenis: jenis, detail: detail, ak: ak });
                },
                removeTugasTambahan(index) {
                    this.tugasTambahan.splice(index, 1);
                },
                syncTupoksiToTugasTambahan(idx, data) {
                    if (this.tugasTambahan[idx]) {
                        this.tugasTambahan[idx].jenis = data.tupoksi || "";
                        this.tugasTambahan[idx].detail = data.jenis_aktivitas || "";
                        this.tugasTambahan[idx].ak = 1;
                    } else {
                        while(this.tugasTambahan.length <= idx) {
                            this.tugasTambahan.push({ jenis: "", detail: "", ak: 1 });
                        }
                        this.tugasTambahan[idx].jenis = data.tupoksi || "";
                        this.tugasTambahan[idx].detail = data.jenis_aktivitas || "";
                    }
                },
                addIndicator(catIndex) {
                    let cat = this.perilakuCategories[catIndex];
                    cat.indicators.push({ text: "", bobot_persen: null });
                },
                removeIndicator(catIndex, indIndex) {
                    this.perilakuCategories[catIndex].indicators.splice(indIndex, 1);
                },
                getCategoryTotal(catIndex) {
                    let cat = this.perilakuCategories[catIndex];
                    if(!cat || !cat.indicators) return 0;
                    return cat.indicators.reduce((sum, ind) => sum + (parseFloat(ind.bobot_persen) || 0), 0);
                },
                validateForm(e) {
                    for(let i=0; i<this.perilakuCategories.length; i++) {
                        let total = this.getCategoryTotal(i);
                        if (total > 100) {
                            e.preventDefault();
                            alert("Total persentase pada kategori " + this.perilakuCategories[i].name + " melebihi 100% (" + total + "%). Harap sesuaikan.");
                            return false;
                        }
                    }
                    return true;
                }
            }' @submit="validateForm">
                @csrf
                @method('PUT') {{-- PENTING UNTUK PROSES EDIT DI LARAVEL --}}

                {{-- MODAL PERSIAPAN AWAL (DIPERBAIKI) --}}
                <div x-show="setupModal" style="z-index: 99999;"
                    class="fixed inset-0 overflow-y-auto bg-gray-900/80 backdrop-blur-sm" x-cloak>

                    {{-- Wrapper flex untuk menengahkan tanpa memotong layar --}}
                    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">

                        {{-- Modal Content --}}
                        <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 max-w-5xl w-full transform transition-all relative my-8">

                            <div class="text-center mb-6">
                                <h2 class="text-2xl font-black text-gray-800">Edit Data SKP Dosen</h2>
                                <p class="text-gray-500 text-sm mt-1">Ubah data periode, pegawai, atasan penilai, serta setup jabatan jika diperlukan.</p>
                            </div>

                            <div class="space-y-6">
                                {{-- 1. PILIH PERIODE --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Periode SKP *</label>
                                    <select name="periode_skp_id" id="periode_skp_id" x-model="periodeId" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold bg-white text-sm"
                                        onchange="generateAllRows()">
                                        <option value="">-- Pilih Periode Aktif --</option>
                                        @foreach ($periode as $p)
                                            <option value="{{ $p->id }}">
                                                @if($p->tipe == 'tahunan')
                                                    Tahun {{ $p->tahun }} : {{ $p->semester_genap }} / {{ $p->semester_gasal }}
                                                @elseif($p->tipe == 'semester')
                                                    Tahun {{ $p->tahun }} : Semester {{ ucfirst($p->semester) }}
                                                @elseif($p->tipe == 'bulanan')
                                                    Tahun {{ $p->tahun }} : Bulan {{ \Carbon\Carbon::create()->month($p->bulan)->translatedFormat('F') }}{{ $p->bulan_akhir ? ' — ' . \Carbon\Carbon::create()->month($p->bulan_akhir)->translatedFormat('F') : '' }}
                                                @else
                                                    Tahun {{ $p->tahun }}
                                                @endif
                                                ({{ strtoupper($p->status) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @php
                                    $jabatanRank = [
                                        'Rektor' => 1,
                                        'Wakil Rektor' => 2,
                                        'Dekan' => 3,
                                        'Wadek 1' => 4,
                                        'Wadek 2' => 4,
                                        'Kaprodi' => 5,
                                        'Sekprodi' => 6,
                                        'Dosen' => 7,
                                    ];
                                    $getRank = function ($user) use ($jabatanRank) {
                                        return $jabatanRank[$user->getRoleNames()->first() ?? 'Dosen'] ?? 99;
                                    };

                                    // Filter Atasan Penilai hanya Dekan dan Kaprodi
                                    $filteredCalonPenilai = $calonPenilai->filter(function($user) {
                                        return $user->roles->contains(function($role) {
                                            $lowRole = strtolower($role->name);
                                            return str_contains($lowRole, 'dekan') || str_contains($lowRole, 'kaprodi');
                                        });
                                    });

                                    // Filter Dosen Yang Dinilai
                                    $filteredUsersDosen = $usersDosen->filter(function($user) {
                                        $roleName = strtolower($user->getRoleNames()->first() ?? '');
                                        return $roleName !== 'admin keuangan fakultas';
                                    });

                                    $sortedCalonPenilai = $filteredCalonPenilai
                                        ->sortBy(fn($p) => $p->profile->nama_lengkap ?? $p->name)
                                        ->sortBy($getRank);
                                    $sortedUsersDosen = $filteredUsersDosen
                                        ->sortBy(fn($u) => $u->profile->nama_lengkap ?? $u->name)
                                        ->sortBy($getRank);

                                    $masterJabatanSetup = [
                                        1 => 'Rektor',
                                        2 => 'Pembantu/Wakil Rektor',
                                        3 => 'Dekan',
                                        4 => 'Direktur Pasca Sarjana',
                                        5 => 'Ketua Sekolah Tinggi',
                                        6 => 'Pembantu/Wakil Ketua Sekolah Tinggi',
                                        7 => 'Direktur Politeknik',
                                        8 => 'Pembantu/Wakil Direktur Politeknik',
                                        9 => 'Direktur Akademi',
                                        10 => 'Pembantu/Wakil Dekan',
                                        11 => 'Asisten Direktur Pascasarjana',
                                        12 => 'Pembantu/Wakil Direktur Akademi',
                                        13 => 'Ketua Jurusan/Bagian',
                                        14 => 'Pembantu/Wakil Direktur Akademi',
                                        15 => 'Sekretaris Jurusan',
                                        16 => 'Kepala Laboratorium',
                                    ];
                                @endphp

                                <div class="border border-gray-300 rounded-lg overflow-hidden mt-6 shadow-sm">
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-800 text-sm">

                                        {{-- SISI KIRI PEGAWAI YANG DINILAI --}}
                                        <div class="bg-white relative">
                                            <div
                                                class="grid grid-cols-[40px_1fr] border-b border-gray-300 font-bold bg-blue-50 uppercase">
                                                <div class="p-2 text-center border-r border-gray-300">I</div>
                                                <div class="p-2 text-blue-800">DOSEN YANG DINILAI (PILIH)</div>
                                            </div>
                                            <div class="p-2 border-b border-gray-400 bg-blue-50">
                                                <select name="user_id" x-model="pegawaiId"
                                                    @change="
                                                    let opt = $el.options[$el.selectedIndex];
                                                    pegawaiName = opt.dataset.nama || '......................';
                                                    pegawaiNIP = opt.dataset.nip || '......................';
                                                    pegawaiPangkat = opt.dataset.pangkat || '......................';
                                                    pegawaiJabatan = opt.dataset.jabatan || '......................';

                                                    // Auto-Fill Jabatan Menggunakan Kamus Alias
                                                    let matchedKode = window.autoDetectJabatan ? window.autoDetectJabatan(pegawaiJabatan) : '';
                                                    jabatanGenap = matchedKode;
                                                    jabatanGasal = matchedKode;
                                                    "
                                                    required
                                                    class="w-full px-2 py-1.5 border border-blue-300 rounded focus:ring-2 focus:ring-blue-500 font-bold bg-white text-xs text-blue-800">
                                                    <option value="">-- Pilih Dosen --</option>
                                                    @foreach ($sortedUsersDosen as $dosen)
                                                        @php
                                                            $dosenJabatan = $dosen->getRoleNames()->first() ?? 'Dosen';
                                                        @endphp
                                                        <option
                                                            value="{{ $dosen->id }}"
                                                            data-nama="{{ $dosen->profile->nama_lengkap ?? $dosen->name }}"
                                                            data-nip="{{ $dosen->profile->nip ?? '-' }}"
                                                            data-pangkat="{{ $dosen->profile->pangkat_golongan ?? '-' }}"
                                                            data-jabatan="{{ $dosenJabatan }}">
                                                            [{{ $dosenJabatan }}]
                                                            {{ $dosen->profile->nama_lengkap ?? $dosen->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">1</div>
                                                <div class="p-2">Nama</div>
                                                <div class="p-2 font-semibold" x-text="pegawaiName"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">2</div>
                                                <div class="p-2">NIP/NIDN</div>
                                                <div class="p-2 font-semibold" x-text="pegawaiNIP"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">3</div>
                                                <div class="p-2">Pangkat/Gol</div>
                                                <div class="p-2 font-semibold" x-text="pegawaiPangkat"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">4</div>
                                                <div class="p-2">Jabatan/Pekerjaan</div>
                                                <div class="p-2 font-semibold" x-text="pegawaiJabatan"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">5</div>
                                                <div class="p-2">Unit Kerja</div>
                                                <div class="p-2 font-semibold">Universitas Muhammadiyah Surabaya</div>
                                            </div>
                                        </div>

                                        {{-- SISI KANAN PENILAI --}}
                                        <div class="bg-white">
                                            <div
                                                class="grid grid-cols-[40px_1fr] border-b border-gray-300 font-bold bg-gray-100 uppercase">
                                                <div class="p-2 text-center border-r border-gray-300">II</div>
                                                <div class="p-2 flex items-center justify-between">PEJABAT PENILAI (PILIH)</div>
                                            </div>
                                            <div class="p-2 border-b border-gray-400 bg-emerald-50">
                                                <select name="pejabat_penilai_id" x-model="penilaiId"
                                                    @change="
                                                    let opt = $el.options[$el.selectedIndex];
                                                    penilaiName = opt.dataset.nama || '......................';
                                                    penilaiNIP = opt.dataset.nip || '......................';
                                                    penilaiPangkat = opt.dataset.pangkat || '......................';
                                                    penilaiJabatan = opt.dataset.jabatan || '......................';
                                                    penilaiRank = parseInt(opt.dataset.rank) || 99;
                                                    "
                                                    required
                                                    class="w-full px-2 py-1.5 border border-emerald-300 rounded focus:ring-2 focus:ring-emerald-500 font-bold text-emerald-800 bg-white text-xs">
                                                    <option value="" data-rank="99">-- Pilih Penilai --</option>
                                                    @foreach ($sortedCalonPenilai as $cp)
                                                        @php
                                                            $allRoles = $cp->getRoleNames();
                                                            // Cari role yang mengandung kata Dekan atau Kaprodi untuk ditampilkan
                                                            $cpJabatan = $allRoles->first(function($r) {
                                                                $low = strtolower($r);
                                                                return str_contains($low, 'dekan') || str_contains($low, 'kaprodi');
                                                            }) ?? $allRoles->first() ?? 'Dosen';

                                                            $cpRank = $getRank($cp);
                                                        @endphp
                                                        <option value="{{ $cp->id }}" data-rank="{{ $cpRank }}"
                                                            x-show="pegawaiId != '{{ $cp->id }}' || '{{ strtolower($cpJabatan) }}' === 'dekan'"
                                                            data-nama="{{ $cp->profile->nama_lengkap ?? $cp->name }}"
                                                            data-nip="{{ $cp->profile->nip ?? '-' }}"
                                                            data-pangkat="{{ $cp->profile->pangkat_golongan ?? '-' }}"
                                                            data-jabatan="{{ $cpJabatan }}">
                                                            [{{ $cpJabatan }}]
                                                            {{ $cp->profile->nama_lengkap ?? $cp->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">1</div>
                                                <div class="p-2">Nama</div>
                                                <div class="p-2 font-semibold" x-text="penilaiName"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">2</div>
                                                <div class="p-2">NIP/NIDN</div>
                                                <div class="p-2 font-semibold" x-text="penilaiNIP"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">3</div>
                                                <div class="p-2">Pangkat/Gol</div>
                                                <div class="p-2 font-semibold" x-text="penilaiPangkat"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">4</div>
                                                <div class="p-2">Jabatan</div>
                                                <div class="p-2 font-semibold" x-text="penilaiJabatan"></div>
                                            </div>
                                            <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                                                <div class="p-2 text-center border-r border-gray-400">5</div>
                                                <div class="p-2">Unit Kerja</div>
                                                <div class="p-2 font-semibold">Universitas Muhammadiyah Surabaya</div>
                                            </div>
                                        </div>

                                        {{-- III. ATASAN PEJABAT PENILAI (DEKAN/REKTOR) --}}
                                        <div class="bg-white col-span-1 md:col-span-2 border-t border-gray-300">
                                            <div class="grid grid-cols-[40px_1fr] border-b border-gray-300 font-bold bg-indigo-50/50 uppercase">
                                                <div class="p-2 text-center border-r border-gray-300">III</div>
                                                <div class="p-2 flex items-center justify-between text-indigo-800">
                                                    <span>ATASAN PEJABAT PENILAI (PILIH) *</span>
                                                </div>
                                            </div>
                                            <div class="p-2 border-b border-gray-400 bg-indigo-50">
                                                <select name="penilai_akhir_id" x-model="penilaiAkhirId"
                                                    @change="
                                                    let opt = $el.options[$el.selectedIndex];
                                                    penilaiAkhirName = opt.dataset.nama || '......................';
                                                    penilaiAkhirNIP = opt.dataset.nip || '......................';
                                                    penilaiAkhirPangkat = opt.dataset.pangkat || '......................';
                                                    penilaiAkhirJabatan = opt.dataset.jabatan || '......................';
                                                    "
                                                    required
                                                    class="w-full px-2 py-1.5 border border-indigo-300 rounded focus:ring-2 focus:ring-indigo-500 font-bold text-indigo-800 bg-white text-xs">
                                                    <option value="">-- Pilih Atasan Penilai --</option>
                                                    @foreach ($jsonPenilaiAkhir ?? [] as $cpa)
                                                        <option value="{{ $cpa['id'] }}"
                                                            data-nama="{{ $cpa['nama'] }}"
                                                            data-nip="{{ $cpa['nip'] }}"
                                                            data-pangkat="{{ $cpa['pangkat'] }}"
                                                            data-jabatan="{{ $cpa['jabatan'] }}">
                                                            [{{ $cpa['jabatan'] }}]
                                                            {{ $cpa['nama'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex flex-col w-full">
                                                <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                    <div class="p-2 text-center border-r border-gray-400">1</div>
                                                    <div class="p-2">Nama</div>
                                                    <div class="p-2 font-semibold" x-text="penilaiAkhirName"></div>
                                                </div>
                                                <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                    <div class="p-2 text-center border-r border-gray-400">2</div>
                                                    <div class="p-2">NIP/NIDN</div>
                                                    <div class="p-2 font-semibold" x-text="penilaiAkhirNIP"></div>
                                                </div>
                                                <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                    <div class="p-2 text-center border-r border-gray-400">3</div>
                                                    <div class="p-2">Pangkat/Gol</div>
                                                    <div class="p-2 font-semibold" x-text="penilaiAkhirPangkat"></div>
                                                </div>
                                                <div class="grid grid-cols-[40px_140px_1fr] border-b border-gray-400 text-xs">
                                                    <div class="p-2 text-center border-r border-gray-400">4</div>
                                                    <div class="p-2">Jabatan/Pekerjaan</div>
                                                    <div class="p-2 font-semibold" x-text="penilaiAkhirJabatan"></div>
                                                </div>
                                                <div class="grid grid-cols-[40px_140px_1fr] text-xs">
                                                    <div class="p-2 text-center border-r border-gray-400">5</div>
                                                    <div class="p-2">Unit Kerja</div>
                                                    <div class="p-2 font-semibold">Universitas Muhammadiyah Surabaya</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- SETUP JABATAN AWAL (SEMESTER GENAP DAN GASAL) --}}
                                <div class="border border-slate-300 rounded-lg p-4 bg-slate-50 shadow-sm mt-4">
                                    <h3 class="text-xs font-bold text-slate-700 uppercase mb-3"><i
                                            class="fas fa-sitemap mr-1"></i> Menduduki Jabatan Pimpinan (Otomatis / Opsional
                                        Setup)</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-600 mb-1">Jabatan Semester
                                                Genap</label>
                                            <select x-model="jabatanGenap"
                                                class="w-full text-xs p-2 border-slate-300 rounded focus:ring-slate-500 font-bold text-blue-700">
                                                <option value="">-- Tidak Ada / Dosen Biasa --</option>
                                                @foreach ($masterJabatanSetup as $kode => $namaJbt)
                                                    <option value="{{ $kode }}">[{{ $kode }}]
                                                        {{ $namaJbt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-600 mb-1">Jabatan Semester
                                                Gasal</label>
                                            <select x-model="jabatanGasal"
                                                class="w-full text-xs p-2 border-slate-300 rounded focus:ring-slate-500 font-bold text-blue-700">
                                                <option value="">-- Tidak Ada / Dosen Biasa --</option>
                                                @foreach ($masterJabatanSetup as $kode => $namaJbt)
                                                    <option value="{{ $kode }}">[{{ $kode }}]
                                                        {{ $namaJbt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-8 flex flex-col-reverse sm:flex-row gap-4">
                                <a href="{{ route('superadmin.skp-dosen.index') }}"
                                    class="w-full sm:w-1/3 py-4 rounded-xl font-bold text-center tracking-wider uppercase transition-all border border-gray-300 text-gray-600 hover:bg-gray-100">Batal
                                    / Kembali</a>

                                <button type="button" :disabled="!isValidSetup"
                                    @click="
                                    setupModal = false;
                                    generateAllRows();
                                    setTimeout(() => { injectJabatanSetup(jabatanGenap, jabatanGasal); }, 500);
                                "
                                    class="w-full sm:w-2/3 py-4 rounded-xl font-black tracking-wider uppercase transition-all shadow-lg"
                                    :class="isValidSetup ?
                                        'bg-blue-600 hover:bg-blue-700 text-white transform hover:-translate-y-1' :
                                        'bg-gray-300 text-gray-500 cursor-not-allowed'">
                                    Terapkan Perubahan Data Awal &rarr;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- HALAMAN UTAMA --}}
                <div x-show="!setupModal" x-cloak>
                    <div class="flex justify-between items-center mb-6">
                        <a href="{{ route('superadmin.skp-dosen.index') }}"
                            class="text-sm font-bold text-gray-500 hover:text-emerald-600">&larr; Batal & Kembali</a>
                    </div>

                    {{-- HEADER IDENTITAS UTAMA (BIODATA TABLE) --}}
                    <div class="bg-white border border-gray-300 rounded-xl shadow-sm text-sm mb-6 overflow-hidden animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-300 border-b border-gray-300">
                            {{-- SISI KIRI: DOSEN --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-blue-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-blue-800 shrink-0 font-black tracking-widest">I</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-blue-800 min-w-0">
                                        <span class="truncate text-[10px] font-black tracking-tight">PEGAWAI YANG DINILAI</span>
                                        <button type="button" @click="setupModal = true" class="text-white bg-blue-600 px-2.5 py-1.5 rounded-lg text-[9px] shadow-lg shadow-blue-200 tracking-wider shrink-0 ml-2 hover:bg-blue-700 transition-all font-black uppercase flex items-center gap-1.5 active:scale-95">
                                            <i class="fas fa-user-edit"></i> GANTI PENILAI
                                        </button>
                                    </div>
                                </div>

                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP/NIDN</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-gray-900 truncate min-w-0 flex items-center h-full text-xs" x-text="pegawaiJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>

                            {{-- SISI KANAN: PEJABAT PENILAI --}}
                            <div class="flex-1 flex flex-col min-w-0 bg-white">
                                <div class="flex bg-emerald-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                    <div class="w-12 flex items-center justify-center border-r border-gray-300 text-emerald-800 shrink-0 font-black tracking-widest">II</div>
                                    <div class="flex-1 px-3 flex items-center justify-between text-emerald-800 min-w-0 text-[10px]">
                                        <span class="truncate font-black tracking-tight">PEJABAT PENILAI</span>
                                    </div>
                                </div>

                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP/NIDN</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-emerald-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white flex flex-col min-w-0">
                            <div class="flex bg-indigo-50/50 border-b border-gray-300 font-bold uppercase h-14">
                                <div class="w-12 flex items-center justify-center border-r border-gray-300 text-indigo-800 shrink-0 font-black tracking-widest">III</div>
                                <div class="flex-1 px-3 flex items-center text-indigo-800 min-w-0 text-[10px]">
                                    <span class="truncate font-black tracking-tight uppercase tracking-widest">ATASAN PEJABAT PENILAI</span>
                                </div>
                            </div>

                            <div class="flex flex-col w-full text-[11px]">
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">1</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Nama</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirName"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">2</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">NIP/NIDN</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirNIP"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">3</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Pangkat/Gol</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirPangkat"></div>
                                </div>
                                <div class="flex border-b border-gray-100 hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">4</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Jabatan</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs" x-text="penilaiAkhirJabatan"></div>
                                </div>
                                <div class="flex hover:bg-gray-50 transition-colors items-center h-12">
                                    <div class="w-12 flex justify-center items-center border-r border-gray-200 text-gray-400 font-medium shrink-0 h-full text-[10px]">5</div>
                                    <div class="w-32 px-3 text-[10px] text-gray-500 font-bold shrink-0 flex items-center h-full uppercase tracking-tighter">Unit Kerja</div>
                                    <div class="flex-1 px-3 font-bold text-indigo-900 truncate min-w-0 flex items-center h-full text-xs">Universitas Muhammadiyah Surabaya</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                        <h2 class="text-xl font-bold text-gray-800 uppercase tracking-tighter italic">📝 A. Penyusunan Target Sasaran Kerja Pegawai (SKP)</h2>
                    </div>

                    {{-- TABLE 1: TUPOKSI & BEBAN KERJA (SINKRON KE TUGAS TAMBAHAN) --}}
                    <div class="mb-2 ml-1 flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                        <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider">Tabel 1: Rincian Tupoksi & Beban Kerja</h3>
                    </div>
                    <div class="w-full shadow-md border border-gray-300 rounded-lg bg-white overflow-hidden mb-4">
                        <table class="w-full table-fixed border-collapse bg-white">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 h-9">
                                    <th rowspan="2" style="width: 3%;" class="text-[9px] font-black uppercase">No</th>
                                    <th rowspan="2" style="width: 8%;" class="text-[9px] font-black uppercase">Bidang</th>
                                    <th rowspan="2" style="width: 14%;" class="text-[9px] font-black uppercase">Tupoksi</th>
                                    <th rowspan="2" style="width: 15%;" class="text-[9px] font-black uppercase">Jenis Aktivitas</th>
                                    <th rowspan="2" style="width: 7%;" class="text-[9px] font-black uppercase">Penilai</th>
                                    <th colspan="3" style="width: 6%;" class="text-[9px] font-black uppercase border-b border-slate-300">Kategori</th>
                                    <th rowspan="2" style="width: 6%;" class="text-[9px] font-black uppercase">Periode</th>
                                    <th rowspan="2" style="width: 5%;" class="text-[9px] font-black uppercase">Freq</th>
                                    <th rowspan="2" style="width: 5%;" class="text-[9px] font-black uppercase">Dur</th>
                                    <th rowspan="2" style="width: 4%;" class="text-[9px] font-black uppercase">SDM</th>
                                    <th rowspan="2" style="width: 6%;" class="text-[9px] font-black uppercase">Vol Kerja</th>
                                    <th rowspan="2" style="width: 4%;" class="bg-white border-none"></th>
                                </tr>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th style="width: 2%;" class="text-[7px] font-bold px-0 uppercase border-r border-slate-300">Utm</th>
                                    <th style="width: 2%;" class="text-[7px] font-bold px-0 uppercase border-r border-slate-300">Prd</th>
                                    <th style="width: 2%;" class="text-[7px] font-bold px-0 uppercase">Bkn</th>
                                </tr>
                            </thead>
                            <tbody id="tupoksi-container">
                                @foreach($activities as $actIdx => $act)
                                    <tr id="row-tupoksi-{{ $actIdx }}" class="hover:bg-slate-50 transition-colors form-row-item">
                                        <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">{{ $actIdx + 1 }}</td>
                                        <td>
                                            <select name="tupoksi_items[{{ $actIdx }}][role_name]" required class="table-input font-semibold text-blue-800" onchange="fetchTupoksiByBidang(this)">
                                                <option value="">-- Pilih --</option>
                                                @php
                                                    $pegawaiJabatan = strtolower($skp->user->getRoleNames()->implode(', ') ?? 'dosen');
                                                    $jabatans = array_map('trim', explode(',', $pegawaiJabatan));
                                                @endphp
                                                @foreach($bidangList as $bid)
                                                    @php
                                                        $bLow = strtolower($bid);
                                                        $isMatch = false;
                                                        if (empty($pegawaiJabatan) || $pegawaiJabatan === '......................') {
                                                            $isMatch = true;
                                                        } else {
                                                            foreach ($jabatans as $j) {
                                                                if ($bLow === $j || str_contains($j, $bLow) || str_contains($bLow, $j)) {
                                                                    $isMatch = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    @if($isMatch)
                                                        <option value="{{ $bid }}" {{ $act->role_name == $bid ? 'selected' : '' }}>{{ $bid }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="tupoksi_items[{{ $actIdx }}][tupoksi_select]" class="table-input font-bold text-gray-800" onchange="fillTupoksiData(this)">
                                                <option value="{{ json_encode($act) }}" selected>{{ $act->tupoksi }}</option>
                                            </select>
                                            <input type="hidden" name="tupoksi_items[{{ $actIdx }}][tupoksi]" value="{{ $act->tupoksi }}">
                                        </td>
                                        <td><textarea name="tupoksi_items[{{ $actIdx }}][jenis_aktivitas]" required class="table-input leading-snug" oninput="syncJenisAktivitasToAlpine(this)">{{ $act->jenis_aktivitas }}</textarea></td>
                                        <td>
                                            <select name="tupoksi_items[{{ $actIdx }}][penilai_role]" required class="table-input font-semibold text-emerald-700">
                                                @foreach($penilaiRoles as $pr)
                                                    <option value="{{ $pr->name }}" {{ $act->penilai_role == $pr->name ? 'selected' : '' }}>{{ $pr->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="tupoksi_items[{{ $actIdx }}][kategori][]" value="Utama" class="w-3 h-3 text-blue-600 rounded" {{ is_array($act->kategori) && in_array('Utama', $act->kategori) ? 'checked' : '' }}></td>
                                        <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="tupoksi_items[{{ $actIdx }}][kategori][]" value="Periodik" class="w-3 h-3 text-blue-600 rounded" {{ is_array($act->kategori) && in_array('Periodik', $act->kategori) ? 'checked' : '' }}></td>
                                        <td class="checkbox-cell"><input type="checkbox" name="tupoksi_items[{{ $actIdx }}][kategori][]" value="Bukan Tupoksi" class="w-3 h-3 text-blue-600 rounded" {{ is_array($act->kategori) && in_array('Bukan Tupoksi', $act->kategori) ? 'checked' : '' }}></td>
                                        <td>
                                            <select name="tupoksi_items[{{ $actIdx }}][periode]" required class="table-input text-center px-1">
                                                @foreach(["bulanan", "harian", "kegiatan", "mingguan", "semester", "tahunan", "triwulan"] as $p)
                                                    <option value="{{ $p }}" {{ strtolower($act->periode) == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="tupoksi_items[{{ $actIdx }}][frekuensi_per_tahun]" value="{{ $act->frekuensi_per_tahun }}" min="0" step="1" required class="table-input text-center px-1" oninput="calcVol(this)"></td>
                                        <td><input type="number" name="tupoksi_items[{{ $actIdx }}][durasi_menit]" value="{{ $act->durasi_menit }}" min="0" step="1" required class="table-input text-center px-1" oninput="calcVol(this)"></td>
                                        <td><input type="number" name="tupoksi_items[{{ $actIdx }}][jumlah_sdm]" value="{{ $act->jumlah_sdm }}" min="1" step="1" required class="table-input text-center px-1" oninput="calcVol(this)"></td>
                                        <td class="bg-slate-50 text-right px-2 font-bold text-indigo-700 text-[9px] vol-display pt-3">
                                            {{ number_format($act->frekuensi_per_tahun * $act->durasi_menit * $act->jumlah_sdm, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center bg-white border-none align-middle">
                                            <button type="button" class="text-red-400 hover:text-red-600 transition" onclick="removeTupoksiRow({{ $actIdx }})">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- TOMBOL TAMBAH BARIS TUPOKSI --}}
                    <div class="mt-2 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50/50 p-3 rounded-lg border border-dashed border-gray-300 w-full mb-10">
                        <button type="button" id="btn-add-tupoksi"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg text-xs font-black hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center justify-center gap-2">
                            + Tambah Baris Tupoksi
                        </button>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight italic">Menambahkan baris akan otomatis sinkron ke Tabel Tugas Tambahan di bawah</div>
                    </div>

                    <div class="bg-white overflow-hidden border border-gray-300 shadow-sm mb-6">
                        <div class="bg-gray-100 border-b border-gray-300 p-3 flex justify-between items-center">
                            <span class="font-black text-sm uppercase tracking-tighter">A. KEGIATAN TUGAS JABATAN</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-[10px] border-none bg-white">
                                <thead class="text-gray-900 font-black text-center align-middle bg-gray-50">
                                    <tr>
                                        <th class="border border-gray-300 p-2 w-10 text-[9px] uppercase">NO</th>
                                        <th class="border border-gray-300 p-2 uppercase text-[9px]">III. KEGIATAN TUGAS
                                            JABATAN</th>
                                        <th class="border border-gray-300 p-2 w-24 text-[9px] uppercase">Nilai AK</th>
                                    </tr>
                                </thead>

                                @php
                                    $kategoriJenisMap = [
                                        'Pelaksanaan Pendidikan' => [
                                            'melaksanakan_perkuliahan' => 'Melaksanakan Perkuliahan',
                                            'membimbing_seminar' => 'Membimbing Seminar',
                                            'membimbing_kkn_pkl' => 'Membimbing KKN/PKN/PKL',
                                            'membimbing_skripsi_thesis_disertasi' =>
                                                'Membimbing dan Ikut Membimbing (Skripsi, Tesis, Disertasi)',
                                            'penguji_ujian_akhir' =>
                                                'Menjadi penguji Ujian Akhir baik sebagai ketua maupun anggota',
                                            'membina_kemahasiswaan' =>
                                                'Membina kegiatan kemahasiswaan di bidang akademik dan kemahasiswaan',
                                            'mengembangkan_bahan_kuliah' => 'Mengembangkan Bahan Kuliah',
                                        ],
                                        'Pelaksanaan Penelitian' => ['karya_ilmiah' => 'Menghasilkan Karya Ilmiah'],
                                        'Pelaksanaan Pengabdian Kepada Masyarakat' => [
                                            'pengabdian_kepada_masyarakat' => 'Pengabdian Masyarakat',
                                        ],
                                        'Menduduki Jabatan Di Perguruan Tinggi' => [
                                            'menduduki_jabatan_pimpinan' => 'Menduduki Jabatan Pimpinan',
                                        ],
                                        'Unsur Penunjang' => ['unsur_penunjang' => 'Unsur Penunjang'],
                                    ];

                                    $masterBahanKuliah = [
                                        'Buku Ajar' => 20,
                                        'Diktat' => 5,
                                        'Modul' => 5,
                                        'Petunjuk Praktikum' => 5,
                                        'Model' => 5,
                                        'Alat Bantu' => 5,
                                        'Audio Visual' => 5,
                                        'Naskah Tutorial' => 5,
                                    ];

                                    $masterKaryaIlmiah = [
                                        'Monograf' => 20,
                                        'Buku referensi' => 40,
                                        'Artikel di jurnal ilmiah internasional' => 40,
                                        'Artikel di jurnal ilmiah nasional terakreditasi' => 25,
                                        'Artikel di jurnal ilmiah nasional' => 10,
                                        'Makalah disajikan di seminar internasional' => 15,
                                        'Makalah disajikan di seminar nasional' => 10,
                                        'Poster internasional' => 10,
                                        'Poster nasional' => 5,
                                        'Artikel di majalah/koran' => 1,
                                        'Hasil Penelitian / hasil pemikiran yg tidak dipublikasikan' => 2,
                                        'Menerjemahkan/menyadur buku ilmiah Nasional' => 15,
                                        'Mengedit/menyunting karya ilmiah Nasional' => 10,
                                        'Membuat rencana dan karya teknologi tingkat Internasional' => 60,
                                        'Membuat rencana dan karya teknologi tingkat Nasional' => 40,
                                        'Karya seni monumental/karya sastra Tk. Internasional' => 20,
                                        'Karya seni monumental/karya sastra Tk. Nasional' => 15,
                                        'Karya seni monumental/karya sastra Tk. Lokal' => 10,
                                    ];

                                    $masterAbdimas = [
                                        'A' => [
                                            'title' => 'Kegiatan Abdimas lebih dari 6 bulan',
                                            'items' => [
                                                'Memberikan pelatihan masyarakat Tingkat Internasional' => 4,
                                                'Memberikan pelatihan masyarakat Tingkat Nasional' => 3,
                                                'Memberikan pelatihan masyarakat Tingkat Lokal' => 2,
                                            ],
                                        ],
                                        'B' => [
                                            'title' => 'Kegiatan Abdimas kurang dari 6 bulan',
                                            'items' => [
                                                'Memberikan pelatihan masyarakat Tingkat Internasional' => 3,
                                                'Memberikan pelatihan masyarakat Tingkat Nasional' => 2,
                                                'Memberikan pelatihan masyarakat Tingkat Lokal' => 1,
                                                'Insidental' => 1,
                                            ],
                                        ],
                                        'C' => [
                                            'title' => 'Kegiatan Abdimas dalam 1 tahun',
                                            'items' => [
                                                'Menduduki jabatan pimpinan lembaga pemerintahan' => 5.5,
                                                'Mengembangkan hasil penelitian' => 3,
                                                'Memberi pelayanan berdasarkan keahlian' => 1.5,
                                                'Memberi pelayanan penugasan lembaga PT' => 1,
                                                'Memberi pelayanan berdasarkan fungsi/jabatan' => 0.5,
                                                'Membuat karya pengabdian tidak dipublikasikan' => 3,
                                            ],
                                        ],
                                    ];

                                    $masterJabatan = [
                                        1 => ['nama' => 'Rektor', 'ak' => 6],
                                        2 => ['nama' => 'Pembantu/Wakil Rektor', 'ak' => 5],
                                        3 => ['nama' => 'Dekan', 'ak' => 5],
                                        4 => ['nama' => 'Direktur Pasca Sarjana', 'ak' => 5],
                                        5 => ['nama' => 'Ketua Sekolah Tinggi', 'ak' => 4],
                                        6 => ['nama' => 'Pembantu/ Wakil Ketua Sekolah Tinggi', 'ak' => 4],
                                        7 => ['nama' => 'Direktur Politeknik', 'ak' => 4],
                                        8 => ['nama' => 'Pembantu/Wakil Direktur Politeknik', 'ak' => 4],
                                        9 => ['nama' => 'Direktur Akademi', 'ak' => 4],
                                        10 => ['nama' => 'Pembantu/Wakil Dekan', 'ak' => 4],
                                        11 => ['nama' => 'Asisten Direktur Pascasarjana', 'ak' => 4],
                                        12 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3],
                                        13 => ['nama' => 'Ketua Jurusan/Bagian', 'ak' => 3],
                                        14 => ['nama' => 'Pembantu/Wakil Direktur Akademi', 'ak' => 3],
                                        15 => ['nama' => 'Sekretaris Jurusan', 'ak' => 3],
                                        16 => ['nama' => 'Kepala Laboratorium', 'ak' => 3],
                                    ];
                                @endphp

                                <script>
                                    window.jsMasterBahanKuliah = @json($masterBahanKuliah);
                                    window.jsMasterKaryaIlmiah = @json($masterKaryaIlmiah);
                                    window.jsMasterAbdimas = @json($masterAbdimas);
                                    window.jsMasterJabatan = @json($masterJabatan);

                                    // ✅ KAMUS ALIAS: Membuat sistem lebih cerdas mengenali "Wadek", "Kaprodi", dsb
                                    window.autoDetectJabatan = function(jabatan) {
                                        if (!jabatan) return '';
                                        let jLow = jabatan.toLowerCase();
                                        let matchedKode = '';

                                        const aliases = {
                                            1: ['rektor'],
                                            2: ['wakil rektor', 'warek', 'pembantu rektor'],
                                            3: ['dekan'],
                                            4: ['direktur pasca', 'direktur pascasarjana'],
                                            5: ['ketua sekolah tinggi'],
                                            6: ['wakil ketua sekolah tinggi'],
                                            10: ['wakil dekan', 'wadek', 'pembantu dekan', 'wd 1', 'wd 2', 'wd i', 'wd ii'],
                                            11: ['asisten direktur pasca', 'asisten direktur pascasarjana'],
                                            13: ['kaprodi', 'ketua prodi', 'ketua program studi', 'ketua program', 'ketua jurusan', 'kajur', 'ka. prodi', 'ka prodi'],
                                            15: ['sekprodi', 'sekretaris prodi', 'sekretaris program studi', 'sekretaris jurusan', 'sekjur', 'sek. prodi', 'sek prodi'],
                                            16: ['kepala lab', 'kalab', 'laboratorium', 'ka. lab', 'ka lab', 'kepala laboratorium']
                                        };

                                        for (let code in aliases) {
                                            if (aliases[code].some(alias => jLow.includes(alias))) {
                                                matchedKode = code;
                                                break;
                                            }
                                        }

                                        if (!matchedKode && window.jsMasterJabatan) {
                                            for (let k in window.jsMasterJabatan) {
                                                let jName = window.jsMasterJabatan[k].nama.toLowerCase();
                                                if (jLow.includes(jName) || jName.includes(jLow)) {
                                                    matchedKode = k;
                                                    break;
                                                }
                                            }
                                        }
                                        return matchedKode;
                                    };

                                    window.injectJabatanSetup = function(genapKode, gasalKode) {
                                        const inpGenap = document.querySelector('.inp-jabatan-genap');
                                        const inpGasal = document.querySelector('.inp-jabatan-gasal');

                                        if (inpGenap && genapKode !== "") {
                                            inpGenap.value = genapKode;
                                            inpGenap.dispatchEvent(new Event('input', {
                                                bubbles: true
                                            }));
                                        }
                                        if (inpGasal && gasalKode !== "") {
                                            inpGasal.value = gasalKode;
                                            inpGasal.dispatchEvent(new Event('input', {
                                                bubbles: true
                                            }));
                                        }
                                    };
                                </script>

                                @foreach ($kategoriJenisMap as $kat => $jenisArr)
                                    @php $slug = Str::slug($kat); @endphp
                                    <tbody id="cat-{{ $slug }}">
                                        <tr class="font-bold bg-gray-100">
                                            <td class="border border-gray-300 p-2 text-center"></td>
                                            <td class="border border-gray-300 p-2 uppercase" colspan="2">
                                                <span>{{ $kat }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach

                                <tbody>
                                    <tr class="font-black bg-indigo-50">
                                        <td class="border border-gray-300 p-2 text-right uppercase text-[10px]"
                                            colspan="2">TOTAL ANGKA KREDIT</td>
                                        <td class="border border-gray-300 p-2 text-center text-indigo-700 text-sm"
                                            id="total-ak">0.00</td>
                                    </tr>
                                    <tr class="font-bold bg-gray-100">
                                        <td class="border border-gray-300 p-1 text-center font-bold text-gray-800">II</td>
                                        <td class="border border-gray-300 p-1 uppercase" colspan="2">Tugas Tambahan dan Kreativitas</td>
                                    </tr>
                                    <template x-for="(item, index) in tugasTambahan" :key="index">
                                        <tr class="hover:bg-indigo-50/10 transition-colors">
                                            <td class="border border-gray-300 p-1 text-center text-slate-400 font-bold" x-text="index + 1"></td>
                                            <td class="border border-gray-300 p-0">
                                                <div class="flex flex-row p-1 gap-2 items-center">
                                                    <input type="text" x-model="item.jenis" :name="'tugas_tambahan['+index+'][jenis]'"
                                                        class="w-1/4 text-[10px] font-black text-indigo-600 uppercase border border-gray-100 rounded px-1 bg-gray-50 cursor-not-allowed"
                                                        readonly placeholder="[Tupoksi]">
                                                    <textarea x-model="item.detail" :name="'tugas_tambahan['+index+'][detail]'"
                                                        class="flex-1 table-input leading-tight text-gray-800 font-medium border border-gray-100 rounded px-1 bg-gray-50 cursor-not-allowed"
                                                        readonly style="min-height: 30px;" oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';"
                                                        placeholder="[Jenis Aktivitas]"></textarea>
                                                </div>
                                            </td>
                                            <td class="border border-gray-300 p-0 text-center relative">
                                                <div class="flex flex-col items-center justify-center h-full">
                                                    <input type="number" step="0.01" x-model="item.ak" :name="'tugas_tambahan['+index+'][ak]'"
                                                        class="w-full text-center font-black text-emerald-700 bg-gray-50 border-none focus:ring-0 text-xs cursor-not-allowed"
                                                        readonly placeholder="0.00">
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- BAGIAN TARGET PERILAKU KERJA (DIPERBARUI STYLE NYA) --}}
                    <div class="bg-white mb-8 w-full overflow-x-auto pb-4 pt-1">
                        <div class="border border-black rounded-xl overflow-hidden min-w-full inline-block align-middle shadow-sm">
                            <table class="w-full text-sm table-skp-custom bg-white">
                                <thead>
                                    <tr>
                                        <th colspan="6" class="text-left font-bold uppercase tracking-widest bg-white">UNSUR YANG DINILAI</th>
                                        <th class="text-center font-bold bg-white w-32">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- A. SKP (60%) --}}
                                    <tr>
                                        <td :rowspan="perilakuCategories.length + 4" class="text-center font-bold align-middle w-12 bg-white text-base">4.</td>
                                        <td colspan="2" class="font-bold bg-white">a. Sasaran Kerja Pegawai (SKP)</td>
                                        <td class="text-center bg-white w-12">-</td>
                                        <td class="text-center bg-white w-24">x</td>
                                        <td class="text-center font-bold bg-white w-20">60%</td>
                                        <td class="text-center font-bold bg-white">-</td>
                                    </tr>

                                    {{-- B. PERILAKU KERJA (LOOPING CATEGORIES) --}}
                                    <template x-for="(cat, catIndex) in perilakuCategories" :key="catIndex">
                                        <tr>
                                            <template x-if="catIndex === 0">
                                                <td :rowspan="perilakuCategories.length + 3" class="font-bold align-middle w-48 bg-white px-4 text-xs">b. Perilaku Kerja</td>
                                            </template>
                                            <td class="align-top bg-white">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span x-text="(catIndex + 1) + '. ' + cat.name" class="mt-1 text-[11px] font-bold uppercase"></span>
                                                    <button type="button" @click="addIndicator(catIndex)" class="bg-gray-100 border border-gray-300 text-gray-700 text-[9px] px-2 py-1 rounded font-bold hover:bg-gray-200 transition" title="Tambah Indikator">
                                                        <i class="fas fa-plus"></i> + Indikator
                                                    </button>
                                                </div>
                                                <div class="flex flex-col gap-2 mt-2">
                                                    <template x-for="(ind, indIndex) in cat.indicators" :key="indIndex">
                                                        <div class="flex items-start gap-2 bg-gray-50 p-2 border border-gray-300 rounded shadow-sm">
                                                            <div class="flex-1 min-w-0">
                                                                <textarea :name="'perilaku[' + cat.name + '][indicators][' + indIndex + '][text]'" x-model="cat.indicators[indIndex].text"
                                                                    class="w-full text-xs border-gray-300 rounded focus:ring-blue-500 resize-y p-1.5 bg-white" rows="2"
                                                                    oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';" placeholder="Tuliskan indikator perilaku..." required></textarea>
                                                            </div>
                                                            <div class="w-20 shrink-0 flex flex-col items-center justify-center">
                                                                <span class="text-[8px] font-bold text-gray-500 uppercase">Persentase</span>
                                                                <div class="relative w-full mt-1">
                                                                    <input type="number" :name="'perilaku[' + cat.name + '][indicators][' + indIndex + '][bobot_persen]'" 
                                                                        x-model.number="cat.indicators[indIndex].bobot_persen" 
                                                                        @input="cat.indicators[indIndex].bobot_persen = Math.round(cat.indicators[indIndex].bobot_persen);"
                                                                        step="1" min="0" max="100" required
                                                                        class="w-full text-center px-1 py-1 pr-4 text-xs font-black text-blue-700 border-blue-300 rounded focus:ring-blue-500 bg-white" placeholder="0">
                                                                    <span class="absolute right-1 top-1/2 -translate-y-1/2 text-[10px] font-bold text-blue-500">%</span>
                                                                </div>
                                                                <span class="text-[8px] mt-1 font-bold" :class="getCategoryTotal(catIndex) > 100 ? 'text-red-500' : 'text-emerald-500'" x-show="indIndex === cat.indicators.length - 1" x-text="'Total: ' + getCategoryTotal(catIndex) + '%'"></span>
                                                            </div>
                                                            <div class="shrink-0 pt-3">
                                                                <button type="button" @click="removeIndicator(catIndex, indIndex)" class="text-red-400 hover:text-red-600 p-1" title="Hapus">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="cat.indicators.length === 0">
                                                        <div class="text-center p-2 text-gray-400 italic text-[10px] bg-gray-50 rounded border border-dashed border-gray-300">Belum ada indikator.</div>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle bg-white">-</td>
                                            <td colspan="2" class="text-center align-middle bg-white">-</td>
                                            <td class="bg-grey-custom"></td>
                                        </tr>
                                    </template>

                                    {{-- FOOTER 7. JUMLAH --}}
                                    <tr>
                                        <td class="bg-white text-xs px-4">7. Jumlah</td>
                                        <td class="text-center align-middle bg-white">-</td>
                                        <td colspan="2" class="text-center align-middle bg-white">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>

                                    {{-- FOOTER 8. NILAI RATA-RATA --}}
                                    <tr>
                                        <td class="bg-white text-xs px-4">8. Nilai rata &ndash; rata</td>
                                        <td class="text-center align-middle bg-white">-</td>
                                        <td colspan="2" class="text-center align-middle bg-white">-</td>
                                        <td class="bg-grey-custom"></td>
                                    </tr>

                                    {{-- FOOTER 9. NILAI PERILAKU KERJA --}}
                                    <tr>
                                        <td class="font-bold bg-white text-xs px-4">9. Nilai Perilaku Kerja</td>
                                        <td class="text-center align-middle bg-white">-</td>
                                        <td class="text-center align-middle bg-white">x</td>
                                        <td class="text-center font-bold align-middle bg-white">40%</td>
                                        <td class="text-center font-bold align-middle bg-white">-</td>
                                    </tr>

                                    {{-- KESIMPULAN NILAI PRESTASI KERJA --}}
                                    <tr>
                                        <td colspan="6" class="text-center font-bold text-base p-3 bg-white tracking-widest uppercase">NILAI PRESTASI KERJA</td>
                                        <td class="text-center font-bold text-base p-3 bg-white">(-)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit"
                            class="px-8 py-3 bg-emerald-600 text-white font-black rounded-lg shadow-lg hover:bg-emerald-700 transition uppercase tracking-widest text-sm">
                            {{-- PERUBAHAN: Teks tombol disesuaikan untuk Edit --}}
                            💾 PERBARUI DATA SKP DOSEN
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endsection

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mencegah scroll mouse mengubah nilai pada semua input bertipe number
                document.addEventListener('wheel', function(event) {
                    if (document.activeElement.type === 'number') {
                        document.activeElement.blur();
                    }
                });

                let rowIndex = 0;
                const periodeSelect = document.getElementById('periode_skp_id');

                const jsKategoriJenisMap = @json($kategoriJenisMap);

                function getSelectedPeriodYear() {
                    const selectedOption = periodeSelect.options[periodeSelect.selectedIndex];
                    if (!selectedOption || selectedOption.value === "") return "";
                    const match = selectedOption.text.match(/\d{4}\/\d{4}/);
                    return match ? match[0] : "";
                }

                function slugify(text) {
                    return text.toString().toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g,
                        '-').replace(/^-+/, '').replace(/-+$/, '');
                }

                window.autoResize = function(el) {
                    el.style.height = 'auto';
                    el.style.height = el.scrollHeight + 'px';
                }

                window.reindexRows = function() {
                    let num = 1;
                    document.querySelectorAll('.row-item:not(.deleted-row)').forEach(tr => {
                        tr.querySelector('.row-num').innerText = num++;
                    });
                    calculateAll();
                }

                // ==========================================
                // KALKULASI OTOMATIS
                // ==========================================
                window.calculateAll = function() {
                    let totalAKGlobal = 0;

                    document.querySelectorAll('.row-item').forEach(tr => {
                        if (tr.classList.contains('deleted-row')) return;

                        const jenis = tr.querySelector('.jenis-input').value;
                        const akInput = tr.querySelector('.ak-input');
                        const kuantInput = tr.querySelector('.kuant-input');
                        const waktuInput = tr.querySelector('.waktu-input');

                        if (jenis === 'melaksanakan_perkuliahan') {
                            let totalSksGenap = 0,
                                totalSksGasal = 0;
                            let countGenap = 0,
                                countGasal = 0;

                            tr.querySelectorAll('.matkul-list-genap .sub-matkul').forEach(sub => {
                                const sks = parseFloat(sub.querySelector('.inp-sks').value) || 0;
                                const kelas = parseFloat(sub.querySelector('.inp-kelas').value) ||
                                    0;
                                const dosen = parseFloat(sub.querySelector('.inp-dosen').value) ||
                                    1;
                                if (dosen > 0) {
                                    totalSksGenap += (sks * kelas) / dosen;
                                    countGenap++;
                                }
                            });

                            tr.querySelectorAll('.matkul-list-gasal .sub-matkul').forEach(sub => {
                                const sks = parseFloat(sub.querySelector('.inp-sks').value) || 0;
                                const kelas = parseFloat(sub.querySelector('.inp-kelas').value) ||
                                    0;
                                const dosen = parseFloat(sub.querySelector('.inp-dosen').value) ||
                                    1;
                                if (dosen > 0) {
                                    totalSksGasal += (sks * kelas) / dosen;
                                    countGasal++;
                                }
                            });

                            const calcSem = (sksTotal) => {
                                let ak10 = sksTotal >= 10 ? 5 : (sksTotal * 0.5);
                                let akNext = sksTotal > 10 ? ((sksTotal - 10) * 0.25) : 0;
                                return {
                                    ak: ak10 + akNext,
                                    waktu: sksTotal >= 1 ? 6 : 0
                                };
                            };

                            let resGenap = calcSem(totalSksGenap);
                            let resGasal = calcSem(totalSksGasal);

                            akInput.value = (resGenap.ak + resGasal.ak).toFixed(2);
                            kuantInput.value = countGenap + countGasal;
                            waktuInput.value = resGenap.waktu + resGasal.waktu;
                        } else if (jenis === 'membimbing_seminar') {
                            let mhsGenap = parseFloat(tr.querySelector('.inp-mhs-genap').value) || 0;
                            let mhsGasal = parseFloat(tr.querySelector('.inp-mhs-gasal').value) || 0;

                            let akGenap = mhsGenap >= 1 ? 1 : 0;
                            let akGasal = mhsGasal >= 1 ? 1 : 0;

                            let waktuGenap = mhsGenap >= 1 ? 6 : 0;
                            let waktuGasal = mhsGasal >= 1 ? 6 : 0;

                            akInput.value = (akGenap + akGasal).toFixed(2);
                            kuantInput.value = mhsGenap + mhsGasal;
                            waktuInput.value = Math.min(waktuGenap + waktuGasal, 12);
                        } else if (jenis === 'membimbing_kkn_pkl') {
                            let totalAK = 0;
                            let totalKuant = 0;
                            let hasGenap = false;
                            let hasGasal = false;

                            ['KKN', 'PKN', 'PKL'].forEach(keg => {
                                let genap = parseFloat(tr.querySelector(
                                    `.inp-kkn-genap[data-keg="${keg}"]`).value) || 0;
                                let gasal = parseFloat(tr.querySelector(
                                    `.inp-kkn-gasal[data-keg="${keg}"]`).value) || 0;

                                if (genap >= 1) {
                                    totalAK += 1;
                                    hasGenap = true;
                                }
                                if (gasal >= 1) {
                                    totalAK += 1;
                                    hasGasal = true;
                                }

                                totalKuant += (genap + gasal);
                            });

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = (hasGenap ? 6 : 0) + (hasGasal ? 6 : 0);
                        } else if (jenis === 'membimbing_skripsi_thesis_disertasi') {
                            let totalAK = 0;
                            let totalKuant = 0;
                            let semAct = {
                                'genap': false,
                                'gasal': false
                            };

                            ['Skripsi', 'Tesis', 'Disertasi'].forEach(keg => {
                                let genapUtama = parseFloat(tr.querySelector(
                                    `.inp-bimb-utama-genap[data-keg="${keg}"]`).value) || 0;
                                let genapPenyerta = parseFloat(tr.querySelector(
                                    `.inp-bimb-penyerta-genap[data-keg="${keg}"]`).value) || 0;
                                let gasalUtama = parseFloat(tr.querySelector(
                                    `.inp-bimb-utama-gasal[data-keg="${keg}"]`).value) || 0;
                                let gasalPenyerta = parseFloat(tr.querySelector(
                                    `.inp-bimb-penyerta-gasal[data-keg="${keg}"]`).value) || 0;

                                let totalKegMhs = genapUtama + genapPenyerta + gasalUtama +
                                    gasalPenyerta;
                                if (totalKegMhs > 0) {
                                    if (genapUtama > 0 || genapPenyerta > 0) semAct['genap'] = true;
                                    if (gasalUtama > 0 || gasalPenyerta > 0) semAct['gasal'] = true;
                                }

                                let pembagi = 1;
                                if (keg === 'Skripsi') pembagi = 6;
                                else if (keg === 'Tesis') pembagi = 3;
                                else if (keg === 'Disertasi') pembagi = 2;

                                let sksGenapUtama = genapUtama / pembagi;
                                let sksGenapPenyerta = genapPenyerta / pembagi;
                                let sksGasalUtama = gasalUtama / pembagi;
                                let sksGasalPenyerta = gasalPenyerta / pembagi;

                                let akGenapUtama = sksGenapUtama * 1;
                                let akGenapPenyerta = (sksGenapPenyerta * 0.5) * 0.5;
                                let akGasalUtama = sksGasalUtama * 1;
                                let akGasalPenyerta = (sksGasalPenyerta * 0.5) * 0.5;

                                let elSksGenapUtm = tr.querySelector(
                                    `.out-sks-genap-utama[data-keg="${keg}"]`);
                                let elAkGenapUtm = tr.querySelector(
                                    `.out-ak-genap-utama[data-keg="${keg}"]`);
                                let elSksGenapPny = tr.querySelector(
                                    `.out-sks-genap-penyerta[data-keg="${keg}"]`);
                                let elAkGenapPny = tr.querySelector(
                                    `.out-ak-genap-penyerta[data-keg="${keg}"]`);

                                let elSksGasalUtm = tr.querySelector(
                                    `.out-sks-gasal-utama[data-keg="${keg}"]`);
                                let elAkGasalUtm = tr.querySelector(
                                    `.out-ak-gasal-utama[data-keg="${keg}"]`);
                                let elSksGasalPny = tr.querySelector(
                                    `.out-sks-gasal-penyerta[data-keg="${keg}"]`);
                                let elAkGasalPny = tr.querySelector(
                                    `.out-ak-gasal-penyerta[data-keg="${keg}"]`);

                                if (elSksGenapUtm) elSksGenapUtm.innerText = sksGenapUtama.toFixed(
                                    2);
                                if (elAkGenapUtm) elAkGenapUtm.innerText = akGenapUtama.toFixed(2);
                                if (elSksGenapPny) elSksGenapPny.innerText = sksGenapPenyerta
                                    .toFixed(2);
                                if (elAkGenapPny) elAkGenapPny.innerText = akGenapPenyerta.toFixed(
                                    2);

                                if (elSksGasalUtm) elSksGasalUtm.innerText = sksGasalUtama.toFixed(
                                    2);
                                if (elAkGasalUtm) elAkGasalUtm.innerText = akGasalUtama.toFixed(2);
                                if (elSksGasalPny) elSksGasalPny.innerText = sksGasalPenyerta
                                    .toFixed(2);
                                if (elAkGasalPny) elAkGasalPny.innerText = akGasalPenyerta.toFixed(
                                    2);

                                totalAK += (akGenapUtama + akGenapPenyerta + akGasalUtama +
                                    akGasalPenyerta);
                                totalKuant += totalKegMhs;
                            });

                            let activeCount = (semAct['genap'] ? 1 : 0) + (semAct['gasal'] ? 1 : 0);

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = activeCount >= 2 ? 12 : (activeCount === 1 ? 6 : 0);
                        } else if (jenis === 'penguji_ujian_akhir') {
                            let totalAK = 0;
                            let totalKuant = 0;
                            let semAct = {
                                'Genap': false,
                                'Gasal': false
                            };

                            ['Genap', 'Gasal'].forEach(sem => {
                                let ketua = parseFloat(tr.querySelector(
                                    `.inp-penguji-ketua[data-sem="${sem}"]`).value) || 0;
                                let anggota = parseFloat(tr.querySelector(
                                    `.inp-penguji-anggota[data-sem="${sem}"]`).value) || 0;

                                let totalMhs = ketua + anggota;
                                if (totalMhs > 0) semAct[sem] = true;

                                let akSem = (ketua * 1) + (anggota * 0.5);
                                let elAkDisplay = tr.querySelector(
                                    `.out-ak-penguji[data-sem="${sem}"]`);
                                if (elAkDisplay) elAkDisplay.innerText = akSem.toFixed(2);

                                totalAK += akSem;
                                totalKuant += totalMhs;
                            });

                            let activeCount = (semAct['Genap'] ? 1 : 0) + (semAct['Gasal'] ? 1 : 0);
                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = activeCount >= 2 ? 12 : (activeCount === 1 ? 6 : 0);
                        } else if (jenis === 'membina_kemahasiswaan') {
                            let totalAK = 0;
                            let totalKuant = 0;

                            tr.querySelectorAll('.sub-kemahasiswaan').forEach(sub => {
                                let genap = parseFloat(sub.querySelector('.inp-mhs-genap').value) ||
                                    0;
                                let gasal = parseFloat(sub.querySelector('.inp-mhs-gasal').value) ||
                                    0;

                                let jmlKegiatan = (genap > 0 || gasal > 0) ? 1 : 0;
                                let akBaris = ((genap > 0 ? 1 : 0) + (gasal > 0 ? 1 : 0)) * 2;

                                let outJml = sub.querySelector('.out-keg-jumlah');
                                let outAk = sub.querySelector('.out-keg-ak');
                                if (outJml) outJml.innerText = jmlKegiatan;
                                if (outAk) outAk.innerText = akBaris.toFixed(2);

                                totalKuant += jmlKegiatan;
                                totalAK += akBaris;
                            });

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = totalKuant >= 2 ? 12 : (totalKuant === 1 ? 6 : 0);
                        } else if (jenis === 'mengembangkan_bahan_kuliah') {
                            let totalAK = 0;
                            let totalKuant = 0;
                            let semAct = {
                                'genap': false,
                                'gasal': false
                            };

                            ['Genap', 'Gasal'].forEach(sem => {
                                Object.keys(window.jsMasterBahanKuliah).forEach(keg => {
                                    let jumlah = parseFloat(tr.querySelector(
                                        `.inp-bahan-jumlah[data-sem="${sem}"][data-keg="${keg}"]`
                                        ).value) || 0;
                                    let tarif = window.jsMasterBahanKuliah[keg];

                                    let akBaris = jumlah * tarif;

                                    let elAkDisplay = tr.querySelector(
                                        `.out-bahan-ak[data-sem="${sem}"][data-keg="${keg}"]`
                                        );
                                    if (elAkDisplay) elAkDisplay.innerText = akBaris
                                        .toFixed(2);

                                    if (jumlah > 0) semAct[sem.toLowerCase()] = true;
                                    totalAK += akBaris;
                                    totalKuant += jumlah;
                                });
                            });

                            let activeCount = (semAct['genap'] ? 1 : 0) + (semAct['gasal'] ? 1 : 0);
                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = activeCount >= 2 ? 12 : (activeCount === 1 ? 6 : 0);
                        } else if (jenis === 'karya_ilmiah') {
                            let totalAK = 0;
                            let totalKuant = 0;

                            Object.keys(window.jsMasterKaryaIlmiah).forEach(keg => {
                                let jumlah = parseFloat(tr.querySelector(`.inp-karya-jumlah[data-keg="${keg}"]`)
                                    .value) || 0;
                                let tarif = window.jsMasterKaryaIlmiah[keg];

                                let akBaris = jumlah * tarif;

                                let elAkDisplay = tr.querySelector(
                                    `.out-karya-ak[data-keg="${keg}"]`);
                                if (elAkDisplay) elAkDisplay.innerText = akBaris.toFixed(2);

                                totalAK += akBaris;
                                totalKuant += jumlah;
                            });

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = totalKuant >= 1 ? 12 : 0;
                        } else if (jenis === 'pengabdian_kepada_masyarakat') {
                            let totalAK = 0;
                            let totalKuant = 0;
                            let sumK = {
                                'A': 0,
                                'B': 0,
                                'C': 0
                            };

                            Object.keys(window.jsMasterAbdimas).forEach(kategoriCode => {
                                let group = window.jsMasterAbdimas[kategoriCode];
                                Object.keys(group.items).forEach(keg => {
                                    let jumlah = parseFloat(tr.querySelector(
                                        `.inp-abdimas-jumlah[data-kat="${kategoriCode}"][data-keg="${keg}"]`
                                        ).value) || 0;
                                    let tarif = group.items[keg];

                                    let akBaris = jumlah * tarif;

                                    let elAkDisplay = tr.querySelector(
                                        `.out-abdimas-ak[data-kat="${kategoriCode}"][data-keg="${keg}"]`
                                        );
                                    if (elAkDisplay) elAkDisplay.innerText = akBaris
                                        .toFixed(2);

                                    totalAK += akBaris;
                                    totalKuant += jumlah;
                                    sumK[kategoriCode] += jumlah;
                                });
                            });

                            let timeComp = (sumK['C'] >= 1 ? 12 : 0) + (sumK['B'] >= 1 ? 5 : 0) + (sumK[
                                'A'] >= 1 ? 12 : 0);
                            let waktuFinal = (timeComp >= 6) ? 12 : (sumK['B'] >= 1 ? 6 : 0);

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = waktuFinal;
                        } else if (jenis === 'menduduki_jabatan_pimpinan') {
                            let kodeGenap = parseInt(tr.querySelector('.inp-jabatan-genap').value) || 0;
                            let kodeGasal = parseInt(tr.querySelector('.inp-jabatan-gasal').value) || 0;

                            let akGenap = (kodeGenap > 0 && window.jsMasterJabatan[kodeGenap]) ? window
                                .jsMasterJabatan[kodeGenap].ak : 0;
                            let akGasal = (kodeGasal > 0 && window.jsMasterJabatan[kodeGasal]) ? window
                                .jsMasterJabatan[kodeGasal].ak : 0;

                            tr.querySelectorAll('.out-ak-jabatan-genap').forEach(el => el.innerText = '0');
                            tr.querySelectorAll('.out-ak-jabatan-gasal').forEach(el => el.innerText = '0');
                            tr.querySelectorAll('tr[class*="row-master-jabatan-"]').forEach(el => el
                                .classList.remove('bg-yellow-100'));

                            if (kodeGenap > 0 && window.jsMasterJabatan[kodeGenap]) {
                                let elAk = tr.querySelector(`.out-ak-jabatan-genap.row-${kodeGenap}`);
                                if (elAk) elAk.innerText = akGenap;
                                let elRow = tr.querySelector(`.row-master-jabatan-genap-${kodeGenap}`);
                                if (elRow) elRow.classList.add('bg-yellow-100');
                            }

                            if (kodeGasal > 0 && window.jsMasterJabatan[kodeGasal]) {
                                let elAk = tr.querySelector(`.out-ak-jabatan-gasal.row-${kodeGasal}`);
                                if (elAk) elAk.innerText = akGasal;
                                let elRow = tr.querySelector(`.row-master-jabatan-gasal-${kodeGasal}`);
                                if (elRow) elRow.classList.add('bg-yellow-100');
                            }

                            let outGenapTotal = tr.querySelector('.out-ak-genap-total');
                            if (outGenapTotal) outGenapTotal.innerText = akGenap.toFixed(2);

                            let outGasalTotal = tr.querySelector('.out-ak-gasal-total');
                            if (outGasalTotal) outGasalTotal.innerText = akGasal.toFixed(2);

                            let countAct = (akGenap > 0 ? 1 : 0) + (akGasal > 0 ? 1 : 0);

                            akInput.value = (akGenap + akGasal).toFixed(2);
                            kuantInput.value = countAct;
                            waktuInput.value = countAct * 6;
                        } else if (jenis === 'unsur_penunjang') {
                            let totalAK = 0;
                            let totalKuant = 0;

                            tr.querySelectorAll('.sub-penunjang').forEach(sub => {
                                let nama = sub.querySelector('.inp-nama-keg').value || '...';
                                let akInputan = parseFloat(sub.querySelector('.inp-ak-keg')
                                    .value) || 0;

                                if (nama !== '...' && nama.trim() !== '') {
                                    totalAK += akInputan;
                                    if (akInputan > 0) {
                                        totalKuant += 1;
                                    }
                                }
                            });

                            akInput.value = totalAK.toFixed(2);
                            kuantInput.value = totalKuant;
                            waktuInput.value = totalKuant >= 1 ? 12 : 0;
                        }

                        if (!akInput.classList.contains('readonly-ak')) {
                            totalAKGlobal += parseFloat(akInput.value) || 0;
                        } else {
                            totalAKGlobal += parseFloat(akInput.value) || 0;
                        }
                    });

                    document.getElementById('total-ak').innerText = totalAKGlobal.toFixed(2).replace('.', ',');
                }

                // ==========================================
                // PENAMBAHAN BARIS MATAKULIAH (Otomatis MK x)
                // ==========================================
                {{-- PERUBAHAN: Menambahkan parameter rowData agar dapat disuntikkan data lama --}}
                window.addSubRowPerkuliahan = function(baseIdxOrBtn, semester = null, rowData = null) {
                    let baseIdx, sem, containerTable;

                    if (baseIdxOrBtn instanceof HTMLElement) {
                        const btn = baseIdxOrBtn;
                        sem = btn.getAttribute('data-sem');
                        containerTable = btn.closest('div').nextElementSibling;
                        baseIdx = btn.getAttribute('data-idx');
                    } else {
                        baseIdx = baseIdxOrBtn;
                        sem = semester;
                        containerTable = document.querySelector(`#row-${baseIdx} .matkul-list-${sem}`);
                    }

                    if (!containerTable) return;

                    const matkulIdx = new Date().getTime() + Math.floor(Math.random() * 1000);
                    const mkCount = containerTable.querySelectorAll('.sub-matkul').length + 1;

                    const namaMk = rowData?.mata_kuliah || ('MK ' + mkCount);
                    const valSks = rowData?.sks ?? '';
                    const valKls = rowData?.jumlah_kelas ?? '1';
                    const valDsn = rowData?.dosen_pengampu ?? '1';

                    const html = `
                    <div class="sub-matkul flex flex-wrap md:flex-nowrap gap-2 items-center bg-gray-50 p-2 rounded border border-gray-200 mt-2 shadow-sm relative">
                        <input type="hidden" name="kegiatan[${baseIdx}][detail][${matkulIdx}][semester]" value="${sem}">
                        <input type="text" name="kegiatan[${baseIdx}][detail][${matkulIdx}][mata_kuliah]" class="table-input flex-1 border-gray-300 bg-gray-100 font-bold text-gray-600 cursor-not-allowed" value="${namaMk}" readonly oninput="generateUraianGabungan('${baseIdx}', 'melaksanakan_perkuliahan')">

                        <div class="flex items-center gap-1 w-20">
                            <span class="text-[9px] font-bold text-gray-400 uppercase">SKS</span>
                            <input type="number" name="kegiatan[${baseIdx}][detail][${matkulIdx}][sks]" class="inp-sks table-input text-center px-1 border border-gray-200 bg-white rounded" min="0" value="${valSks}" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'melaksanakan_perkuliahan');" placeholder="0" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189">
                        </div>

                        <div class="flex items-center gap-1 w-20 hidden">
                            <span class="text-[9px] font-bold text-gray-400 uppercase">Kls</span>
                            <input type="number" name="kegiatan[${baseIdx}][detail][${matkulIdx}][jumlah_kelas]" class="inp-kelas table-input text-center px-1 border border-gray-200 bg-white rounded" min="1" value="${valKls}" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'melaksanakan_perkuliahan');" placeholder="1" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189">
                        </div>

                        <div class="flex items-center gap-1 w-20 hidden">
                            <span class="text-[9px] font-bold text-gray-400 uppercase">Dsn</span>
                            <input type="number" name="kegiatan[${baseIdx}][detail][${matkulIdx}][dosen_pengampu]" class="inp-dosen table-input text-center px-1 border border-gray-200 bg-white rounded" min="1" value="${valDsn}" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'melaksanakan_perkuliahan');" placeholder="1" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189">
                        </div>

                        <button type="button" class="text-red-400 hover:text-red-600 bg-white border border-red-200 px-2 py-1 rounded shadow-sm" onclick="this.closest('.sub-matkul').remove(); calculateAll(); generateUraianGabungan('${baseIdx}', 'melaksanakan_perkuliahan');"><i class="fas fa-times"></i></button>
                    </div>
                `;
                    containerTable.insertAdjacentHTML('beforeend', html);

                    setTimeout(() => {
                        calculateAll();
                        generateUraianGabungan(baseIdx, 'melaksanakan_perkuliahan');
                    }, 50);
                }

                {{-- PERUBAHAN: Menambahkan parameter rowData agar dapat disuntikkan data lama --}}
                window.addSubRowKemahasiswaan = function(baseIdxOrBtn, rowData = null) {
                    let baseIdx, containerTable;

                    if (baseIdxOrBtn instanceof HTMLElement) {
                        const btn = baseIdxOrBtn;
                        baseIdx = btn.getAttribute('data-idx');
                        containerTable = btn.closest('div').nextElementSibling.querySelector('tbody');
                    } else {
                        baseIdx = baseIdxOrBtn;
                        containerTable = document.querySelector(`#row-${baseIdx} .tbody-kemahasiswaan`);
                    }

                    if (!containerTable) return;

                    const kegIdx = new Date().getTime() + Math.floor(Math.random() * 1000);
                    const kegCount = containerTable.querySelectorAll('.sub-kemahasiswaan').length + 1;

                    const namaKeg = rowData?.nama_kegiatan || ('Kegiatan ' + kegCount);
                    const valGenap = rowData?.jumlah_genap ?? '';
                    const valGasal = rowData?.jumlah_gasal ?? '';

                    const html = `
                    <tr class="sub-kemahasiswaan border-b border-gray-100">
                        <td class="border border-gray-300 p-1">
                            <input type="text" name="kegiatan[${baseIdx}][detail][${kegIdx}][nama_kegiatan]" class="inp-nama-keg table-input w-full bg-gray-100 text-gray-600 font-bold p-1 cursor-not-allowed" value="${namaKeg}" readonly oninput="generateUraianGabungan('${baseIdx}', 'membina_kemahasiswaan')">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="kegiatan[${baseIdx}][detail][${kegIdx}][jumlah_genap]" class="inp-mhs-genap table-input text-center bg-white p-1" min="0" placeholder="0" value="${valGenap}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'membina_kemahasiswaan');">
                        </td>
                        <td class="border border-gray-300 p-1">
                            <input type="number" name="kegiatan[${baseIdx}][detail][${kegIdx}][jumlah_gasal]" class="inp-mhs-gasal table-input text-center bg-white p-1" min="0" placeholder="0" value="${valGasal}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'membina_kemahasiswaan');">
                        </td>
                        <td class="border border-gray-300 p-1 text-center font-bold text-gray-600 bg-gray-50 out-keg-jumlah">0</td>
                        <td class="border border-gray-300 p-1 text-center font-bold text-emerald-600 bg-gray-50 out-keg-ak">0.00</td>
                        <td class="border border-gray-300 p-1 text-center">
                            <button type="button" class="text-red-400 hover:text-red-600" onclick="this.closest('tr').remove(); calculateAll(); generateUraianGabungan('${baseIdx}', 'membina_kemahasiswaan');"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                `;
                    containerTable.insertAdjacentHTML('beforeend', html);
                    setTimeout(() => {
                        calculateAll();
                        generateUraianGabungan(baseIdx, 'membina_kemahasiswaan');
                    }, 50);
                }

                {{-- PERUBAHAN: Menambahkan parameter rowData agar dapat disuntikkan data lama --}}
                window.addSubRowPenunjang = function(baseIdxOrBtn, rowData = null) {
                    let baseIdx, containerTable;

                    if (baseIdxOrBtn instanceof HTMLElement) {
                        const btn = baseIdxOrBtn;
                        baseIdx = btn.getAttribute('data-idx');
                        containerTable = btn.closest('div').nextElementSibling.querySelector('tbody');
                    } else {
                        baseIdx = baseIdxOrBtn;
                        containerTable = document.querySelector(`#row-${baseIdx} .tbody-penunjang`);
                    }

                    if (!containerTable) return;

                    const kegIdx = new Date().getTime() + Math.floor(Math.random() * 1000);
                    const penCount = containerTable.querySelectorAll('.sub-penunjang').length + 1;

                    const namaPen = rowData?.nama_kegiatan || ('Penunjang ' + penCount);
                    const valAk = rowData?.ak ?? '';

                    const html = `
                    <tr class="sub-penunjang border-b border-gray-100">
                        <td class="border border-gray-300 p-1">
                            <input type="text" name="kegiatan[${baseIdx}][detail][${kegIdx}][nama_kegiatan]" class="inp-nama-keg table-input w-full bg-gray-100 text-gray-600 font-bold p-1 cursor-not-allowed" value="${namaPen}" readonly oninput="generateUraianGabungan('${baseIdx}', 'unsur_penunjang')">
                        </td>
                        <td class="border border-gray-300 p-1 w-32">
                            <input type="number" step="0.01" name="kegiatan[${baseIdx}][detail][${kegIdx}][ak]" class="inp-ak-keg table-input text-center bg-white p-1" min="0" placeholder="0" value="${valAk}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan('${baseIdx}', 'unsur_penunjang');">
                        </td>
                        <td class="border border-gray-300 p-1 text-center w-12">
                            <button type="button" class="text-red-400 hover:text-red-600" onclick="this.closest('tr').remove(); calculateAll(); generateUraianGabungan('${baseIdx}', 'unsur_penunjang');"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                `;
                    containerTable.insertAdjacentHTML('beforeend', html);
                    setTimeout(() => {
                        calculateAll();
                        generateUraianGabungan(baseIdx, 'unsur_penunjang');
                    }, 50);
                }

                window.generateUraianGabungan = function(idx, jenis) {
                    const tr = document.getElementById('row-' + idx);
                    if (!tr) return;
                    const hiddenUraian = tr.querySelector('.hidden-uraian');

                    if (jenis === 'melaksanakan_perkuliahan') {
                        let genapArr = [];
                        tr.querySelectorAll('.matkul-list-genap .sub-matkul').forEach(sub => {
                            const mk = sub.querySelector('input[name*="mata_kuliah"]').value || '...';
                            const sks = sub.querySelector('.inp-sks').value || '0';
                            genapArr.push(`${mk} (${sks} SKS)`);
                        });

                        let gasalArr = [];
                        tr.querySelectorAll('.matkul-list-gasal .sub-matkul').forEach(sub => {
                            const mk = sub.querySelector('input[name*="mata_kuliah"]').value || '...';
                            const sks = sub.querySelector('.inp-sks').value || '0';
                            gasalArr.push(`${mk} (${sks} SKS)`);
                        });

                        let text = "Melaksanakan Perkuliahan\n";
                        if (genapArr.length > 0) text += "Genap: " + genapArr.join(", ") + "\n";
                        if (gasalArr.length > 0) text += "Gasal: " + gasalArr.join(", ");
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'membimbing_seminar') {
                        let mhsGenap = tr.querySelector('.inp-mhs-genap').value || '0';
                        let mhsGasal = tr.querySelector('.inp-mhs-gasal').value || '0';
                        hiddenUraian.value =
                            `Membimbing Seminar\nGenap: ${mhsGenap} Mahasiswa\nGasal: ${mhsGasal} Mahasiswa`;
                    } else if (jenis === 'membimbing_kkn_pkl') {
                        let text = "Membimbing Mahasiswa KKN, Praktek Kerja Nyata, Praktek Kerja Lapangan\n";
                        ['KKN', 'PKN', 'PKL'].forEach(keg => {
                            let genap = tr.querySelector(`.inp-kkn-genap[data-keg="${keg}"]`).value || '0';
                            let gasal = tr.querySelector(`.inp-kkn-gasal[data-keg="${keg}"]`).value || '0';
                            if (genap > 0 || gasal > 0) text +=
                                `- ${keg} (Genap: ${genap}, Gasal: ${gasal})\n`;
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'membimbing_skripsi_thesis_disertasi') {
                        let text = "Membimbing dan ikut membimbing dalam menghasilkan skripsi, thesis, disertasi\n";
                        ['Skripsi', 'Tesis', 'Disertasi'].forEach(keg => {
                            let genapUtama = tr.querySelector(`.inp-bimb-utama-genap[data-keg="${keg}"]`)
                                .value || '0';
                            let genapPenyerta = tr.querySelector(
                                `.inp-bimb-penyerta-genap[data-keg="${keg}"]`).value || '0';
                            let gasalUtama = tr.querySelector(`.inp-bimb-utama-gasal[data-keg="${keg}"]`)
                                .value || '0';
                            let gasalPenyerta = tr.querySelector(
                                `.inp-bimb-penyerta-gasal[data-keg="${keg}"]`).value || '0';

                            if (genapUtama > 0 || genapPenyerta > 0 || gasalUtama > 0 || gasalPenyerta >
                                0) {
                                text +=
                                    `- ${keg} \n  Genap: Utama(${genapUtama}), Penyerta(${genapPenyerta}) \n  Gasal: Utama(${gasalUtama}), Penyerta(${gasalPenyerta})\n`;
                            }
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'penguji_ujian_akhir') {
                        let text = "Menjadi penguji Ujian Akhir baik sebagai ketua maupun anggota\n";
                        ['Genap', 'Gasal'].forEach(sem => {
                            let ketua = tr.querySelector(`.inp-penguji-ketua[data-sem="${sem}"]`).value ||
                                '0';
                            let anggota = tr.querySelector(`.inp-penguji-anggota[data-sem="${sem}"]`)
                                .value || '0';
                            if (ketua > 0 || anggota > 0) {
                                text += `- Semester ${sem}: Ketua(${ketua}), Anggota(${anggota})\n`;
                            }
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'membina_kemahasiswaan') {
                        let text = "Membina kegiatan kemahasiswaan di bidang akademik dan kemahasiswaan\n";
                        tr.querySelectorAll('.sub-kemahasiswaan').forEach(sub => {
                            let nama = sub.querySelector('.inp-nama-keg').value || '...';
                            let genap = sub.querySelector('.inp-mhs-genap').value || '0';
                            let gasal = sub.querySelector('.inp-mhs-gasal').value || '0';
                            if (nama !== '...') {
                                text += `- ${nama} (Genap: ${genap}, Gasal: ${gasal})\n`;
                            }
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'mengembangkan_bahan_kuliah') {
                        let text = "Mengembangkan Bahan Kuliah\n";
                        ['Genap', 'Gasal'].forEach(sem => {
                            let ada = false;
                            let semText = `- Semester ${sem}: `;
                            Object.keys(window.jsMasterBahanKuliah).forEach(keg => {
                                let jumlah = parseFloat(tr.querySelector(
                                    `.inp-bahan-jumlah[data-sem="${sem}"][data-keg="${keg}"]`
                                    ).value) || 0;
                                if (jumlah > 0) {
                                    semText += `${keg}(${jumlah}), `;
                                    ada = true;
                                }
                            });
                            if (ada) text += semText.slice(0, -2) + "\n";
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'karya_ilmiah') {
                        let text = "Menghasilkan Karya Ilmiah\n";
                        Object.keys(window.jsMasterKaryaIlmiah).forEach(keg => {
                            let jumlah = parseFloat(tr.querySelector(`.inp-karya-jumlah[data-keg="${keg}"]`)
                                .value) || 0;
                            if (jumlah > 0) {
                                text += `- ${keg}: ${jumlah} Karya\n`;
                            }
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'pengabdian_kepada_masyarakat') {
                        let text = "Melaksanakan Kegiatan Pengabdian Kepada Masyarakat\n";
                        Object.keys(window.jsMasterAbdimas).forEach(katCode => {
                            let group = window.jsMasterAbdimas[katCode];
                            Object.keys(group.items).forEach(keg => {
                                let jumlah = parseFloat(tr.querySelector(
                                    `.inp-abdimas-jumlah[data-kat="${katCode}"][data-keg="${keg}"]`
                                    ).value) || 0;
                                if (jumlah > 0) {
                                    text += `- ${keg}: ${jumlah} Kegiatan\n`;
                                }
                            });
                        });
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'menduduki_jabatan_pimpinan') {
                        let kodeGenap = parseInt(tr.querySelector('.inp-jabatan-genap').value) || 0;
                        let kodeGasal = parseInt(tr.querySelector('.inp-jabatan-gasal').value) || 0;
                        let text = "Menduduki Jabatan Pimpinan Di Perguruan Tinggi\n";
                        if (kodeGenap > 0 && window.jsMasterJabatan[kodeGenap]) text += "- Semester Genap: " +
                            window.jsMasterJabatan[kodeGenap].nama + "\n";
                        if (kodeGasal > 0 && window.jsMasterJabatan[kodeGasal]) text += "- Semester Gasal: " +
                            window.jsMasterJabatan[kodeGasal].nama + "\n";
                        hiddenUraian.value = text.trim();
                    } else if (jenis === 'unsur_penunjang') {
                        let text = "Unsur Penunjang\n";
                        tr.querySelectorAll('.sub-penunjang').forEach(sub => {
                            let nama = sub.querySelector('.inp-nama-keg').value || '...';
                            let akInputan = parseFloat(sub.querySelector('.inp-ak-keg').value) || 0;
                            if (nama !== '...' && nama.trim() !== '') {
                                text += `- ${nama} (AK: ${akInputan})\n`;
                            }
                        });
                        hiddenUraian.value = text.trim();
                    }
                }

                {{-- PERUBAHAN: Modifikasi fungsi createRow agar bisa menerima parameter 'data' (Pre-fill saat Edit) --}}
                window.createRow = function(categoryName, jenisValue, jenisText, data = null) {
                    const tbodyId = 'cat-' + slugify(categoryName);
                    const tbody = document.getElementById(tbodyId);
                    if (!tbody) return;

                    const currentIdx = rowIndex++;
                    const tr = document.createElement('tr');
                    tr.id = 'row-' + currentIdx;
                    tr.className = "row-item";

                    let kegiatanHTML = `
                    <input type="hidden" name="kegiatan[${currentIdx}][kategori]" value="${categoryName}">
                    <input type="hidden" name="kegiatan[${currentIdx}][jenis]" value="${jenisValue}" class="jenis-input">

                    <input type="hidden" name="kegiatan[${currentIdx}][kuantitas_output]" value="${data?.kuantitas_output ?? 1}" class="kuant-input">
                    <input type="hidden" name="kegiatan[${currentIdx}][kualitas_mutu]" value="${data?.kualitas_mutu ?? 100}">
                    <input type="hidden" name="kegiatan[${currentIdx}][waktu]" value="${data?.waktu ?? 12}" class="waktu-input">
                    <input type="hidden" name="kegiatan[${currentIdx}][biaya]" value="${data?.biaya ?? 0}">
                `;

                    if (jenisValue === 'melaksanakan_perkuliahan') {
                        kegiatanHTML += `
                        <div class="detail-perkuliahan-container p-2 mt-1">
                            <div class="font-bold text-blue-800 text-[11px] uppercase mb-3 border-b border-blue-200 pb-2">${jenisText}</div>

                            <div class="mb-4">
                                <div class="flex justify-between items-center bg-emerald-100 p-2 rounded-t border-b border-emerald-200">
                                    <span class="font-bold text-emerald-900 text-[10px] ">Semester Genap</span>
                                    <button type="button" class="text-[9px] bg-emerald-600 text-white px-2 py-1 rounded shadow-sm hover:bg-emerald-700" data-idx="${currentIdx}" data-sem="genap" onclick="addSubRowPerkuliahan(this)">+ Matkul Genap</button>
                                </div>
                                <div class="matkul-list-genap bg-white border border-t-0 border-emerald-100 p-2 rounded-b min-h-[40px]"></div>
                            </div>

                            <div>
                                <div class="flex justify-between items-center bg-blue-100 p-2 rounded-t border-b border-blue-200">
                                    <span class="font-bold text-blue-900 text-[10px] ">Semester Gasal</span>
                                    <button type="button" class="text-[9px] bg-blue-600 text-white px-2 py-1 rounded shadow-sm hover:bg-blue-700" data-idx="${currentIdx}" data-sem="gasal" onclick="addSubRowPerkuliahan(this)">+ Matkul Gasal</button>
                                </div>
                                <div class="matkul-list-gasal bg-white border border-t-0 border-blue-100 p-2 rounded-b min-h-[40px]"></div>
                            </div>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Melaksanakan Perkuliahan</textarea>
                    `;
                    } else if (jenisValue === 'membimbing_seminar') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-purple-800 text-[11px] uppercase mb-3 border-b border-purple-200 pb-2">${jenisText}</div>

                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-2 shadow-sm">
                                    <label class="text-[10px] font-bold text-gray-600 uppercase whitespace-nowrap mr-3">Semester Genap</label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[9px] text-gray-400 font-bold whitespace-nowrap">Jml Mahasiswa:</span>
                                        <input type="number" name="kegiatan[${currentIdx}][detail][genap][jumlah_mahasiswa]" class="inp-mhs-genap table-input text-center font-bold text-purple-700 bg-white w-20" min="0" placeholder="0" value="${data?.detail?.genap?.jumlah_mahasiswa ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded p-2 shadow-sm">
                                    <label class="text-[10px] font-bold text-gray-600 uppercase whitespace-nowrap mr-3">Semester Gasal</label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[9px] text-gray-400 font-bold whitespace-nowrap">Jml Mahasiswa:</span>
                                        <input type="number" name="kegiatan[${currentIdx}][detail][gasal][jumlah_mahasiswa]" class="inp-mhs-gasal table-input text-center font-bold text-purple-700 bg-white w-20" min="0" placeholder="0" value="${data?.detail?.gasal?.jumlah_mahasiswa ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Membimbing Seminar</textarea>
                    `;
                    } else if (jenisValue === 'membimbing_kkn_pkl') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-orange-800 text-[11px] uppercase mb-3 border-b border-orange-200 pb-2">${jenisText}</div>

                            <table class="w-full text-[10px] border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2 text-left">KETERANGAN</th>
                                        <th class="border border-gray-300 p-2 w-24 text-center">MHS GENAP</th>
                                        <th class="border border-gray-300 p-2 w-24 text-center">MHS GASAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${['KKN', 'PKN', 'PKL'].map(keg => `
                                            <tr>
                                                <td class="border border-gray-300 p-2 font-bold text-gray-700">${keg}</td>
                                                <td class="border border-gray-300 p-1">
                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][genap]" class="inp-kkn-genap table-input text-center bg-white p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.genap ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                </td>
                                                <td class="border border-gray-300 p-1">
                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][gasal]" class="inp-kkn-gasal table-input text-center bg-white p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.gasal ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                </td>
                                            </tr>
                                        `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Membimbing KKN/PKL</textarea>
                    `;
                    } else if (jenisValue === 'membimbing_skripsi_thesis_disertasi') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-yellow-800 text-[11px] uppercase mb-3 border-b border-yellow-200 pb-2">${jenisText}</div>

                            ${['Skripsi', 'Tesis', 'Disertasi'].map((keg, i) => `
                                        <div class="mb-4 bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
                                            <div class="bg-yellow-100 text-yellow-900 px-3 py-2 font-black text-xs border-b border-yellow-200">
                                                ${keg} <span class="font-normal text-[9px] text-gray-500 ml-2">(*Setiap ${keg === 'Skripsi' ? 6 : (keg === 'Tesis' ? 3 : 2)} mhs = 1 SKS)</span>
                                            </div>
                                            <div class="grid grid-cols-1">

                                                <div class="border-b border-gray-200 pb-2">
                                                    <div class="bg-gray-100 font-bold text-gray-700 text-[10px] p-2 text-center uppercase border-b border-gray-200">SEMESTER GENAP</div>
                                                    <table class="w-full text-[10px] border-collapse">
                                                        <thead>
                                                            <tr class="text-gray-500 text-center border-b border-gray-100">
                                                                <th class="p-1 text-left pl-2 font-normal">Kategori</th>
                                                                <th class="p-1 font-normal w-16">Jml Mhs</th>
                                                                <th class="p-1 font-normal w-12">SKS</th>
                                                                <th class="p-1 font-normal w-12">AK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="border-b border-gray-50">
                                                                <td class="p-1 pl-2 font-bold text-gray-700">Jumlah mahasiswa yang dibimbing (sebagai Pembimbing Utama)</td>
                                                                <td class="p-1">
                                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][genap][utama]" class="inp-bimb-utama-genap table-input text-center bg-gray-50 p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.genap?.utama ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                                </td>
                                                                <td class="p-1 text-center font-bold text-gray-500 out-sks-genap-utama" data-keg="${keg}">0.00</td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 out-ak-genap-utama" data-keg="${keg}">0.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="p-1 pl-2 font-bold text-gray-700">Jumlah mahasiswa yang dibimbing (sebagai Pembimbing Penyerta)</td>
                                                                <td class="p-1">
                                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][genap][penyerta]" class="inp-bimb-penyerta-genap table-input text-center bg-gray-50 p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.genap?.penyerta ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                                </td>
                                                                <td class="p-1 text-center font-bold text-gray-500 out-sks-genap-penyerta" data-keg="${keg}">0.00</td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 out-ak-genap-penyerta" data-keg="${keg}">0.00</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="mt-2">
                                                    <div class="bg-gray-100 font-bold text-gray-700 text-[10px] p-2 text-center uppercase border-b border-gray-200">SEMESTER GASAL</div>
                                                    <table class="w-full text-[10px] border-collapse">
                                                        <thead>
                                                            <tr class="text-gray-500 text-center border-b border-gray-100">
                                                                <th class="p-1 text-left pl-2 font-normal">Kategori</th>
                                                                <th class="p-1 font-normal w-16">Jml Mhs</th>
                                                                <th class="p-1 font-normal w-12">SKS</th>
                                                                <th class="p-1 font-normal w-12">AK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="border-b border-gray-50">
                                                                <td class="p-1 pl-2 font-bold text-gray-700">Jumlah mahasiswa yang dibimbing (sebagai Pembimbing Utama)</td>
                                                                <td class="p-1">
                                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][gasal][utama]" class="inp-bimb-utama-gasal table-input text-center bg-gray-50 p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.gasal?.utama ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                                </td>
                                                                <td class="p-1 text-center font-bold text-gray-500 out-sks-gasal-utama" data-keg="${keg}">0.00</td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 out-ak-gasal-utama" data-keg="${keg}">0.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="p-1 pl-2 font-bold text-gray-700">Jumlah mahasiswa yang dibimbing (sebagai Pembimbing Penyerta)</td>
                                                                <td class="p-1">
                                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${keg}][gasal][penyerta]" class="inp-bimb-penyerta-gasal table-input text-center bg-gray-50 p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg]?.gasal?.penyerta ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                                </td>
                                                                <td class="p-1 text-center font-bold text-gray-500 out-sks-gasal-penyerta" data-keg="${keg}">0.00</td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 out-ak-gasal-penyerta" data-keg="${keg}">0.00</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                            </div>
                                        </div>
                                    `).join('')}
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Membimbing Skripsi/Thesis</textarea>
                    `;
                    } else if (jenisValue === 'penguji_ujian_akhir') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-teal-800 text-[11px] uppercase mb-3 border-b border-teal-200 pb-2">${jenisText}</div>

                            <table class="w-full text-[10px] border-collapse bg-white shadow-sm border border-gray-200 rounded">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2 text-left" rowspan="2">NAMA MAHASISWA / KETERANGAN</th>
                                        <th class="border border-gray-300 p-1 text-center" colspan="2">PENGUJI</th>
                                        <th class="border border-gray-300 p-1 text-center w-16" rowspan="2">NILAI AK</th>
                                    </tr>
                                    <tr class="bg-gray-50 text-[9px] text-center text-gray-500">
                                        <th class="border border-gray-300 p-1 w-16">KETUA</th>
                                        <th class="border border-gray-300 p-1 w-16">ANGGOTA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${['Genap', 'Gasal'].map(sem => `
                                            <tr>
                                                <td class="border border-gray-300 p-2 font-bold text-gray-700">Semester ${sem}</td>
                                                <td class="border border-gray-300 p-1">
                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${sem}][ketua]" class="inp-penguji-ketua table-input text-center bg-gray-50 p-1" data-sem="${sem}" min="0" placeholder="0" value="${data?.detail?.[sem]?.ketua ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                </td>
                                                <td class="border border-gray-300 p-1">
                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${sem}][anggota]" class="inp-penguji-anggota table-input text-center bg-gray-50 p-1" data-sem="${sem}" min="0" placeholder="0" value="${data?.detail?.[sem]?.anggota ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                </td>
                                                <td class="border border-gray-300 p-1 text-center font-bold text-emerald-600 bg-gray-50 out-ak-penguji" data-sem="${sem}">0.00</td>
                                            </tr>
                                        `).join('')}
                                </tbody>
                            </table>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Menjadi penguji Ujian Akhir</textarea>
                    `;
                    } else if (jenisValue === 'membina_kemahasiswaan') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="flex justify-between items-center mb-3 border-b border-indigo-200 pb-2">
                                <span class="font-bold text-indigo-800 text-[11px] uppercase">${jenisText}</span>
                                <button type="button" class="text-[9px] bg-indigo-600 text-white px-2 py-1 rounded shadow-sm hover:bg-indigo-700" data-idx="${currentIdx}" onclick="addSubRowKemahasiswaan(this)">+ Tambah Kegiatan</button>
                            </div>

                            <table class="w-full text-[10px] border-collapse bg-white shadow-sm border border-gray-200 rounded">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2 text-left">NAMA KEGIATAN YANG DIBIMBING</th>
                                        <th class="border border-gray-300 p-1 text-center w-20">JML KGT (GENAP)</th>
                                        <th class="border border-gray-300 p-1 text-center w-20">JML KGT (GASAL)</th>
                                        <th class="border border-gray-300 p-1 text-center w-16">TOTAL KGT</th>
                                        <th class="border border-gray-300 p-1 text-center w-16">NILAI AK</th>
                                        <th class="border border-gray-300 p-1 text-center w-8 text-red-500"><i class="fas fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody class="tbody-kemahasiswaan">
                                </tbody>
                            </table>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Membina Kemahasiswaan</textarea>
                    `;
                    } else if (jenisValue === 'mengembangkan_bahan_kuliah') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-cyan-800 text-[11px] uppercase mb-3 border-b border-cyan-200 pb-2">${jenisText}</div>

                            ${['Genap', 'Gasal'].map(sem => `
                                        <div class="mb-4 bg-white border border-gray-200 rounded shadow-sm">
                                            <div class="bg-cyan-100 text-cyan-900 px-3 py-2 font-black text-xs border-b border-cyan-200 uppercase">
                                                SEMESTER ${sem}
                                            </div>
                                            <table class="w-full text-[10px] border-collapse">
                                                <thead>
                                                    <tr class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                                        <th class="p-2 text-left">Jenis Bahan Kuliah</th>
                                                        <th class="p-1 w-24 text-center">Tarif AK</th>
                                                        <th class="p-1 w-24 text-center">Jumlah Dibuat</th>
                                                        <th class="p-1 w-24 text-center">Total AK</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${Object.keys(window.jsMasterBahanKuliah).map(keg => `
                                                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                                            <td class="p-2 font-bold text-gray-700">${keg}</td>
                                                            <td class="p-1 text-center font-bold text-gray-500">${window.jsMasterBahanKuliah[keg]}</td>
                                                            <td class="p-1">
                                                                <input type="number" name="kegiatan[${currentIdx}][detail][${sem}][${keg}]" class="inp-bahan-jumlah table-input text-center bg-white p-1" data-sem="${sem}" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[sem]?.[keg] ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                            </td>
                                                            <td class="p-1 text-center font-bold text-emerald-600 bg-gray-50 out-bahan-ak" data-sem="${sem}" data-keg="${keg}">0.00</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    `).join('')}
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Mengembangkan Bahan Kuliah</textarea>
                    `;
                    } else if (jenisValue === 'karya_ilmiah') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-pink-800 text-[11px] uppercase mb-3 border-b border-pink-200 pb-2">${jenisText}</div>

                            <div class="bg-white border border-gray-200 rounded shadow-sm">
                                <div class="bg-gray-100 text-gray-500 px-3 py-2 font-bold text-[10px] border-b border-gray-200">
                                    Periode ditandarkan dalam 1 (satu) Tahun
                                </div>
                                <table class="w-full text-[10px] border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                            <th class="p-2 text-left">Jenis Karya Ilmiah</th>
                                            <th class="p-1 w-20 text-center">AK/Karya</th>
                                            <th class="p-1 w-20 text-center">Jumlah Karya</th>
                                            <th class="p-1 w-20 text-center">Jumlah AK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${Object.keys(window.jsMasterKaryaIlmiah).map(keg => `
                                                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                                    <td class="p-2 font-bold text-gray-700">${keg}</td>
                                                    <td class="p-1 text-center font-bold text-gray-500">${window.jsMasterKaryaIlmiah[keg]}</td>
                                                    <td class="p-1">
                                                        <input type="number" name="kegiatan[${currentIdx}][detail][${keg}]" class="inp-karya-jumlah table-input text-center bg-white p-1" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[keg] ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                    </td>
                                                    <td class="p-1 text-center font-bold text-emerald-600 bg-gray-50 out-karya-ak" data-keg="${keg}">0.00</td>
                                                </tr>
                                            `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Menghasilkan Karya Ilmiah</textarea>
                    `;
                    } else if (jenisValue === 'pengabdian_kepada_masyarakat') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-red-800 text-[11px] uppercase mb-3 border-b border-red-200 pb-2">${jenisText}</div>

                            ${Object.keys(window.jsMasterAbdimas).map(katCode => {
                                let group = window.jsMasterAbdimas[katCode];
                                return `
                                            <div class="mb-4 bg-white border border-gray-200 rounded shadow-sm">
                                                <div class="bg-red-50 text-red-900 px-3 py-2 font-black text-[10px] border-b border-red-200">
                                                    ${katCode.toLowerCase()}. ${group.title}
                                                </div>
                                                <table class="w-full text-[10px] border-collapse">
                                                    <thead>
                                                        <tr class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                                            <th class="p-2 text-left">KETERANGAN</th>
                                                            <th class="p-1 w-20 text-center">AK/Prog</th>
                                                            <th class="p-1 w-24 text-center">Jml Kegiatan</th>
                                                            <th class="p-1 w-20 text-center">Jumlah AK</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${Object.keys(group.items).map(keg => `
                                                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                                                <td class="p-2 font-bold text-gray-700 leading-tight">${keg}</td>
                                                                <td class="p-1 text-center font-bold text-gray-500">${group.items[keg]}</td>
                                                                <td class="p-1">
                                                                    <input type="number" name="kegiatan[${currentIdx}][detail][${katCode}][${keg}]" class="inp-abdimas-jumlah table-input text-center bg-white p-1" data-kat="${katCode}" data-keg="${keg}" min="0" placeholder="0" value="${data?.detail?.[katCode]?.[keg] ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                                                </td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 bg-gray-50 out-abdimas-ak" data-kat="${katCode}" data-keg="${keg}">0.00</td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        `;
                            }).join('')}
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Melaksanakan Kegiatan Pengabdian Kepada Masyarakat</textarea>
                    `;
                    } else if (jenisValue === 'menduduki_jabatan_pimpinan') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="font-bold text-slate-800 text-[11px] uppercase mb-3 border-b border-slate-200 pb-2">${jenisText}</div>

                            ${['Genap', 'Gasal'].map(sem => `
                                        <div class="mb-4 bg-white border border-gray-200 rounded shadow-sm">
                                            <div class="bg-slate-600 text-white px-3 py-2 text-[10px] border-b border-slate-500 flex justify-between items-center">
                                                <div>
                                                    <div class="font-bold uppercase">Semester ${sem}</div>
                                                    <div class="text-[8px] font-normal italic">Jika Menjabat pada semester ini isikan Kode Jabatan pada kolom ini --------></div>
                                                </div>
                                                <input type="number" name="kegiatan[${currentIdx}][detail][kode_${sem.toLowerCase()}]" class="inp-jabatan-${sem.toLowerCase()} table-input text-gray-900 w-16 text-center font-bold" min="1" max="16" placeholder="Kode" value="${data?.detail?.[`kode_${sem.toLowerCase()}`] ?? ''}" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" oninput="calculateAll(); generateUraianGabungan(${currentIdx}, '${jenisValue}');">
                                            </div>
                                            <div class="max-h-48 overflow-y-auto">
                                                <table class="w-full text-[10px] border-collapse">
                                                    <thead class="sticky top-0 bg-slate-500 text-yellow-400">
                                                        <tr>
                                                            <th class="p-1 w-12 border border-slate-400">KODE</th>
                                                            <th class="p-1 text-left border border-slate-400 text-white">Jabatan</th>
                                                            <th class="p-1 w-20 border border-slate-400 text-white">Satuan</th>
                                                            <th class="p-1 w-16 border border-slate-400 text-white">AK Base</th>
                                                            <th class="p-1 w-16 border border-slate-400">AK</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${Object.keys(window.jsMasterJabatan).map(code => `
                                                            <tr class="border-b border-gray-200 row-master-jabatan-${sem.toLowerCase()}-${code}">
                                                                <td class="p-1 text-center font-bold text-yellow-600 border-r border-gray-200">${code}</td>
                                                                <td class="p-1 text-gray-700 border-r border-gray-200">${window.jsMasterJabatan[code].nama}</td>
                                                                <td class="p-1 text-center text-gray-500 border-r border-gray-200">Setiap Smt</td>
                                                                <td class="p-1 text-center text-gray-500 border-r border-gray-200">${window.jsMasterJabatan[code].ak}</td>
                                                                <td class="p-1 text-center font-bold text-emerald-600 out-ak-jabatan-${sem.toLowerCase()} row-${code}">0</td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    `).join('')}
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Menduduki Jabatan Pimpinan Di Perguruan Tinggi</textarea>
                    `;
                    } else if (jenisValue === 'unsur_penunjang') {
                        kegiatanHTML += `
                        <div class="p-2 mt-1">
                            <div class="flex justify-between items-center mb-3 border-b border-gray-300 pb-2">
                                <span class="font-bold text-gray-800 text-[11px] uppercase">${jenisText}</span>
                                <button type="button" class="text-[9px] bg-gray-600 text-white px-2 py-1 rounded shadow-sm hover:bg-gray-700" data-idx="${currentIdx}" onclick="addSubRowPenunjang(this)">+ Tambah Kegiatan</button>
                            </div>

                            <table class="w-full text-[10px] border-collapse bg-white shadow-sm border border-gray-200 rounded">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="border border-gray-300 p-2 text-left">NAMA KEGIATAN PENUNJANG</th>
                                        <th class="border border-gray-300 p-1 text-center w-32">NILAI AK</th>
                                        <th class="border border-gray-300 p-1 text-center w-12 text-red-500"><i class="fas fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody class="tbody-penunjang">
                                </tbody>
                            </table>
                        </div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" class="hidden-uraian hidden">Unsur Penunjang</textarea>
                    `;
                    } else {
                        let def = data ? data.kegiatan : (categoryName ===
                            "Menduduki Jabatan Di Perguruan Tinggi" ? jenisText + " (Tahun)" : jenisText);
                        kegiatanHTML += `
                        <div class="font-bold text-gray-600 text-[10px] uppercase mb-1">${jenisText}</div>
                        <textarea name="kegiatan[${currentIdx}][kegiatan]" rows="2" class="w-full table-input uraian-input font-medium border-gray-300 bg-white" placeholder="Uraian kegiatan spesifik..." oninput="autoResize(this); calculateAll();">${def}</textarea>
                    `;
                    }

                    let isAutoCalc = ['melaksanakan_perkuliahan', 'membimbing_seminar', 'membimbing_kkn_pkl',
                        'membimbing_skripsi_thesis_disertasi', 'penguji_ujian_akhir', 'membina_kemahasiswaan',
                        'mengembangkan_bahan_kuliah', 'karya_ilmiah', 'pengabdian_kepada_masyarakat',
                        'menduduki_jabatan_pimpinan', 'unsur_penunjang'
                    ].includes(jenisValue);

                    let akInputHtml = '';
                    if (jenisValue === 'menduduki_jabatan_pimpinan') {
                        akInputHtml = `
                        <input type="hidden" name="kegiatan[${currentIdx}][ak]" value="${data?.ak ?? 0}" class="ak-input">
                        <div class="flex flex-col gap-2 font-bold text-center text-xs">
                            <div class="bg-gray-100 p-1 rounded border border-gray-200">
                                <span class="text-[9px] text-gray-500 uppercase block">Genap</span>
                                <span class="text-emerald-700 out-ak-genap-total">0.00</span>
                            </div>
                            <div class="bg-gray-100 p-1 rounded border border-gray-200">
                                <span class="text-[9px] text-gray-500 uppercase block">Gasal</span>
                                <span class="text-emerald-700 out-ak-gasal-total">0.00</span>
                            </div>
                        </div>
                    `;
                    } else {
                        akInputHtml =
                            `<input type="number" step="0.01" name="kegiatan[${currentIdx}][ak]" value="${data?.ak ?? '0.00'}" class="ak-input table-input readonly-ak w-full" oninput="calculateAll()" ${isAutoCalc ? 'readonly' : ''}>`;
                    }

                    tr.innerHTML = `
                    <td class="border border-gray-300 p-2 text-center font-bold row-num align-top"></td>
                    <td class="border border-gray-300 p-2 align-top">${kegiatanHTML}</td>
                    <td class="border border-gray-300 p-2 align-middle bg-gray-50">${akInputHtml}</td>
                `;

                    tbody.appendChild(tr);

                    const urInput = tr.querySelector('.uraian-input');
                    reindexRows();
                    if (urInput) setTimeout(() => autoResize(urInput), 50);

                    {{-- PERUBAHAN: Menangani data lama array relasi untuk Kegiatan Dinamis --}}
                    if (jenisValue === 'melaksanakan_perkuliahan') {
                        if (data && data.detail) {
                            const detailRows = Array.isArray(data.detail) ? data.detail : Object.values(data.detail);
                            if (detailRows.length > 0) {
                                detailRows.forEach(row => {
                                    if (row.semester === 'genap') addSubRowPerkuliahan(currentIdx, 'genap', row);
                                    else if (row.semester === 'gasal') addSubRowPerkuliahan(currentIdx, 'gasal', row);
                                });
                            } else {
                                addSubRowPerkuliahan(currentIdx, 'genap');
                                addSubRowPerkuliahan(currentIdx, 'gasal');
                            }
                        } else if (!data) {
                            addSubRowPerkuliahan(currentIdx, 'genap');
                            addSubRowPerkuliahan(currentIdx, 'gasal');
                        }
                    } else if (jenisValue === 'membina_kemahasiswaan') {
                        if (data && data.detail) {
                            const detailRows = Array.isArray(data.detail) ? data.detail : Object.values(data.detail);
                            detailRows.forEach(row => addSubRowKemahasiswaan(currentIdx, row));
                        } else if (!data) {
                            addSubRowKemahasiswaan(currentIdx);
                        }
                    }

                    if (jenisValue === 'unsur_penunjang') {
                        if (data && data.detail) {
                            const detailRows = Array.isArray(data.detail) ? data.detail : Object.values(data.detail);
                            detailRows.forEach(row => addSubRowPenunjang(currentIdx, row));
                        } else if (!data) {
                            addSubRowPenunjang(currentIdx);
                        }
                    }
                }

                {{-- PERUBAHAN: Menyiapkan pemetaan data yang sudah ada --}}
                // Pastikan Anda mem-passing variabel $kegiatans_by_jenis (array associative berdasarkan 'jenis') dari controller
                // Contoh di Controller: $kegiatans_by_jenis = $skp->kegiatans->keyBy('jenis');
                window.existingDataMap = @json($kegiatans_by_jenis ?? []);

                window.generateAllRows = function() {
                    if (periodeSelect.value === "") return;

                    Object.keys(jsKategoriJenisMap).forEach(kat => {
                        const tbody = document.getElementById('cat-' + slugify(kat));
                        if (tbody) {
                            const items = tbody.querySelectorAll('.row-item');
                            items.forEach(item => item.remove());
                        }
                    });

                    rowIndex = 0;

                    Object.keys(jsKategoriJenisMap).forEach(kat => {
                        Object.keys(jsKategoriJenisMap[kat]).forEach(jenisVal => {
                            {{-- PERUBAHAN: Jika data untuk jenis tersebut ada di DB, lempar ke fungsi createRow --}}
                            let savedData = null;
                            if(window.existingDataMap && window.existingDataMap[jenisVal]) {
                                savedData = window.existingDataMap[jenisVal];
                            }
                            createRow(kat, jenisVal, jsKategoriJenisMap[kat][jenisVal], savedData);
                        });
                    });
                }

                // --- LOGIKA TABEL 1: TUPOKSI & BEBAN KERJA (SINKRON KE TUGAS TAMBAHAN) ---
                let rowCountTupoksi = {{ $activities->count() }};
                const tupoksiContainer = document.getElementById('tupoksi-container');
                const btnAddTupoksi = document.getElementById('btn-add-tupoksi');

                const rawBidang = @json($bidangList ?? []);
                const availableBidang = Array.isArray(rawBidang) ? rawBidang : Object.values(rawBidang);
                const penilaiRoles = @json($penilaiRoles ?? []);

                window.fetchTupoksiByBidang = function(el, selectedTupoksi = null) {
                    const tr = el.closest('tr');
                    if (!tr) return;
                    const tupoksiSelect = tr.querySelector('select[name*="tupoksi_select"]');
                    const bidang = el.value;

                    if (bidang === "") {
                        tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                        return;
                    }

                    tupoksiSelect.innerHTML = '<option value="">Memuat...</option>';

                    fetch('/lookup/daftar-tupoksi/bidang/' + encodeURIComponent(bidang))
                        .then(res => res.json())
                        .then(data => {
                            tupoksiSelect.innerHTML = '<option value="">-- Pilih Tupoksi --</option>';
                            data.forEach(item => {
                                const isSelected = (selectedTupoksi && selectedTupoksi === item
                                    .tupoksi) ? 'selected' : '';
                                const option = document.createElement('option');
                                option.value = JSON.stringify(item);
                                option.text = item.tupoksi;
                                if (isSelected) option.selected = true;
                                tupoksiSelect.appendChild(option);
                            });
                        });
                }

                window.fillTupoksiData = function(el) {
                    const tr = el.closest('tr');
                    if (!tr) return;
                    const idxStr = tr.id.split('-').pop();
                    const idx = parseInt(idxStr);

                    const hiddenTupoksi = tr.querySelector('input[name*="[tupoksi]"]');
                    const aktivitasTextarea = tr.querySelector('textarea[name*="jenis_aktivitas"]');
                    const periodeSelect = tr.querySelector('select[name*="periode"]');
                    const frekuensiInput = tr.querySelector('input[name*="frekuensi_per_tahun"]');
                    const durasiInput = tr.querySelector('input[name*="durasi_menit"]');
                    const sdmInput = tr.querySelector('input[name*="jumlah_sdm"]');
                    const penilaiSelect = tr.querySelector('select[name*="penilai_role"]');
                    const checkboxes = tr.querySelectorAll('input[type="checkbox"]');

                    const allLockedInputs = [penilaiSelect, periodeSelect, frekuensiInput, durasiInput, sdmInput];

                    if (el.value === "") {
                        if (hiddenTupoksi) hiddenTupoksi.value = "";
                        if (aktivitasTextarea) {
                            aktivitasTextarea.readOnly = true;
                            aktivitasTextarea.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                        }

                        allLockedInputs.forEach(s => {
                            if (s) {
                                s.readOnly = true;
                                s.classList.add('bg-gray-50', 'cursor-not-allowed', 'opacity-60', 'pointer-events-none');
                                s.tabIndex = -1;
                            }
                        });

                        checkboxes.forEach(cb => {
                            cb.classList.add('opacity-60', 'pointer-events-none');
                            cb.onclick = () => false;
                        });
                        return;
                    }

                    // Enable fields
                    if (aktivitasTextarea) {
                        aktivitasTextarea.readOnly = false;
                        aktivitasTextarea.classList.remove('bg-gray-50', 'cursor-not-allowed', 'opacity-60');
                    }

                    allLockedInputs.forEach(s => {
                        if (s) {
                            s.readOnly = false;
                            s.classList.remove('bg-gray-50', 'cursor-not-allowed', 'opacity-60', 'pointer-events-none', 'bg-gray-100');
                            s.tabIndex = 0;
                        }
                    });

                    checkboxes.forEach(cb => {
                        cb.classList.remove('opacity-60', 'pointer-events-none');
                        cb.onclick = null;
                    });

                    const data = JSON.parse(el.value);
                    const bidangSelect = tr.querySelector('select[name*="role_name"]');
                    const bidang = bidangSelect ? bidangSelect.value : "";

                    if (hiddenTupoksi) hiddenTupoksi.value = data.tupoksi || '';
                    if (aktivitasTextarea) {
                        aktivitasTextarea.value = data.jenis_aktivitas || '';
                        autoResize(aktivitasTextarea);
                    }
                    if (penilaiSelect) penilaiSelect.value = data.penilai_role || '';
                    if (periodeSelect) periodeSelect.value = (data.periode || '').toLowerCase();
                    if (frekuensiInput) frekuensiInput.value = data.frekuensi_per_tahun || 0;
                    if (durasiInput) durasiInput.value = data.durasi_menit || 0;
                    if (sdmInput) sdmInput.value = data.jumlah_sdm || 1;

                    // Sync category checkboxes
                    checkboxes.forEach(cb => {
                        if (cb.value === 'Utama') cb.checked = !!data.utama;
                        if (cb.value === 'Periodik') cb.checked = !!data.periodik;
                        if (cb.value === 'Bukan Tupoksi') cb.checked = !!data.bukan_tupoksi;
                    });

                    if (frekuensiInput) calcVol(frekuensiInput);

                    // SINKRON KE ALPINE
                    const skpForm = document.getElementById('skpForm');
                    if (skpForm && window.Alpine) {
                        const alpine = Alpine.$data(skpForm);
                        alpine.syncTupoksiToTugasTambahan(idx, {
                            bidang: bidang,
                            tupoksi: data.tupoksi,
                            jenis_aktivitas: data.jenis_aktivitas
                        });
                    }
                }

                window.syncJenisAktivitasToAlpine = function(el) {
                    const tr = el.closest('tr');
                    if (!tr) return;
                    const idxStr = tr.id.split('-').pop();
                    const idx = parseInt(idxStr);

                    const tupoksiVal = tr.querySelector('input[name*="[tupoksi]"]').value;
                    const aktivitasVal = el.value;

                    const skpForm = document.getElementById('skpForm');
                    if (skpForm && window.Alpine) {
                        const alpine = Alpine.$data(skpForm);
                        alpine.syncTupoksiToTugasTambahan(idx, {
                            tupoksi: tupoksiVal,
                            jenis_aktivitas: aktivitasVal
                        });
                    }
                }

                window.calcVol = function(el) {
                    const tr = el.closest('tr');
                    const freqInput = tr.querySelector('input[name*="frekuensi_per_tahun"]');
                    const durInput = tr.querySelector('input[name*="durasi_menit"]');
                    const sdmInput = tr.querySelector('input[name*="jumlah_sdm"]');

                    const freq = parseFloat(freqInput ? freqInput.value : 0) || 0;
                    const dur = parseFloat(durInput ? durInput.value : 0) || 0;
                    const sdm = parseFloat(sdmInput ? sdmInput.value : 1) || 1;
                    const vol = freq * dur * sdm;
                    tr.querySelector('.vol-display').innerText = vol.toLocaleString('id-ID');
                }

                function reindexTupoksiRows() {
                    const rows = tupoksiContainer.children;
                    for (let i = 0; i < rows.length; i++) {
                        rows[i].querySelector('.row-num').innerText = i + 1;
                        rows[i].querySelectorAll('[name^="tupoksi_items["]').forEach(input => {
                            input.name = input.name.replace(/tupoksi_items\[\d+\]/,
                                `tupoksi_items[${i}]`);
                        });
                    }
                    rowCountTupoksi = rows.length;
                }

                window.removeTupoksiRow = function(idx) {
                    const tr = document.getElementById(`row-tupoksi-${idx}`);
                    if (tr) tr.remove();

                    const skpForm = document.getElementById('skpForm');
                    if (skpForm && window.Alpine) {
                        const alpine = Alpine.$data(skpForm);
                        alpine.removeTugasTambahan(idx);
                    }

                    reindexTupoksiRows();
                }

                function createTupoksiRow(data = {}) {
                    const idx = rowCountTupoksi++;
                    const tr = document.createElement('tr');
                    tr.id = `row-tupoksi-${idx}`;
                    tr.className = "hover:bg-slate-50 transition-colors form-row-item";

                    // GET CURRENT JABATAN FROM ALPINE
                    const skpForm = document.getElementById('skpForm');
                    const alpine = skpForm ? Alpine.$data(skpForm) : null;
                    const currentJabatan = alpine ? alpine.pegawaiJabatan : "";

                    let bidangOptionsHTML = '<option value="">-- Pilih --</option>';
                    availableBidang.forEach(bidangName => {
                        // FILTER BIDANG SESUAI JABATAN
                        let isMatch = false;
                        if (!currentJabatan || currentJabatan === "......................") {
                            isMatch = true;
                        } else {
                            const bLow = bidangName.toLowerCase();
                            const jabatans = currentJabatan.split(',').map(j => j.trim().toLowerCase());
                            isMatch = jabatans.some(j => bLow === j || j.includes(bLow) || bLow.includes(j));
                        }

                        if (isMatch) {
                            bidangOptionsHTML += `<option value="${bidangName}">${bidangName}</option>`;
                        }
                    });

                    let penilaiHTML = '<option value="">-- Pilih --</option>';
                    penilaiRoles.forEach(roleItem => {
                        const penilai = (typeof roleItem === 'object' && roleItem.name) ? roleItem.name :
                            String(roleItem);
                        penilaiHTML += `<option value="${penilai}">${penilai}</option>`;
                    });

                    const periodeOptions = ["Bulanan", "Harian", "Kegiatan", "Mingguan", "Semester", "Tahunan",
                        "Triwulan"
                    ];
                    let periodeOptionsHTML = '<option value="">-- Pilih --</option>';
                    periodeOptions.forEach(p => {
                        periodeOptionsHTML += `<option value="${p.toLowerCase()}">${p}</option>`;
                    });

                    tr.innerHTML = `
                        <td class="row-num text-center text-[9px] font-bold text-slate-400 pt-3">${tupoksiContainer.children.length + 1}</td>
                        <td>
                            <select name="tupoksi_items[${idx}][role_name]" required class="table-input font-semibold text-blue-800"
                                onchange="fetchTupoksiByBidang(this)">
                                ${bidangOptionsHTML}
                            </select>
                        </td>
                        <td>
                            <select name="tupoksi_items[${idx}][tupoksi_select]" class="table-input font-bold text-gray-800"
                                onchange="fillTupoksiData(this)">
                                <option value="">-- Pilih Tupoksi --</option>
                            </select>
                            <input type="hidden" name="tupoksi_items[${idx}][tupoksi]" value="">
                        </td>
                        <td><textarea name="tupoksi_items[${idx}][jenis_aktivitas]" required readonly class="table-input leading-snug bg-gray-50 cursor-not-allowed opacity-60"
                            oninput="syncJenisAktivitasToAlpine(this)"
                            placeholder="Pilih tupoksi dahulu..."></textarea></td>
                        <td><select name="tupoksi_items[${idx}][penilai_role]" required class="table-input font-semibold text-emerald-700 bg-gray-50 cursor-not-allowed opacity-60 pointer-events-none" tabindex="-1">${penilaiHTML}</select></td>
                        <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="tupoksi_items[${idx}][kategori][]" value="Utama" class="w-3 h-3 text-blue-600 rounded opacity-60 pointer-events-none" onclick="return false;"></td>
                        <td class="checkbox-cell border-r border-slate-300"><input type="checkbox" name="tupoksi_items[${idx}][kategori][]" value="Periodik" class="w-3 h-3 text-blue-600 rounded opacity-60 pointer-events-none" onclick="return false;"></td>
                        <td class="checkbox-cell"><input type="checkbox" name="tupoksi_items[${idx}][kategori][]" value="Bukan Tupoksi" class="w-3 h-3 text-blue-600 rounded opacity-60 pointer-events-none" onclick="return false;"></td>
                        <td><select name="tupoksi_items[${idx}][periode]" required class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60 pointer-events-none" tabindex="-1">${periodeOptionsHTML}</select></td>
                        <td><input type="number" name="tupoksi_items[${idx}][frekuensi_per_tahun]" value="" min="0" step="1" required readonly class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60" oninput="calcVol(this)" placeholder="..."></td>
                        <td><input type="number" name="tupoksi_items[${idx}][durasi_menit]" value="" min="0" step="1" required readonly class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60" oninput="calcVol(this)" placeholder="..."></td>
                        <td><input type="number" name="tupoksi_items[${idx}][jumlah_sdm]" value="1" min="1" step="1" required readonly class="table-input text-center px-1 bg-gray-50 cursor-not-allowed opacity-60" oninput="calcVol(this)" placeholder="..."></td>
                        <td class="bg-slate-50 text-right px-2 font-bold text-indigo-700 text-[9px] vol-display pt-3">0</td>
                        <td class="text-center bg-white border-none align-middle">
                            <button type="button" class="text-red-400 hover:text-red-600 transition" onclick="removeTupoksiRow(${idx})">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </td>
                    `;
                    tupoksiContainer.appendChild(tr);

                    // Add corresponding row to Alpine Tugas Tambahan
                    const skpFormObj = document.getElementById('skpForm');
                    if (skpFormObj && window.Alpine) {
                        const alpineObj = Alpine.$data(skpFormObj);
                        alpineObj.addTugasTambahan("", "", 1);
                    }
                }

                if (btnAddTupoksi) {
                    btnAddTupoksi.addEventListener('click', () => createTupoksiRow());
                }

                document.getElementById('skpForm').addEventListener('submit', function(e) {
                    const rows = document.querySelectorAll('.row-item:not(.deleted-row)');
                    let hasValidRow = false;

                    // Hilangkan semua highlight error sebelumnya
                    rows.forEach(r => r.classList.remove('row-error'));

                    rows.forEach(row => {
                        const akInput = row.querySelector('.ak-input');
                        const kuantInput = row.querySelector('.kuant-input');

                        let akVal = parseFloat(akInput ? akInput.value : 0);
                        let kuantVal = parseInt(kuantInput ? kuantInput.value : 0);

                        // Jika baris ini memiliki nilai AK > 0 ATAU Kuantitas > 0, berarti ada data valid
                        if (akVal > 0 || kuantVal > 0) {
                            hasValidRow = true;
                        } else {
                            // Jika baris kosong (AK=0 dan Kuantitas=0), kita "buang" saja baris tersebut
                            // agar tidak terkirim ke server dan membuat data kotor.
                            row.classList.add('deleted-row', 'hidden');
                            row.querySelectorAll('input, select, textarea').forEach(el => el
                                .disabled = true);
                        }
                    });

                    if (!hasValidRow) {
                        e.preventDefault(); // Hentikan proses submit
                        alert(
                            "Gagal Disimpan! Anda belum mengisi/menginput satupun kegiatan dengan nilai Angka Kredit (AK) atau Jumlah. Harap isi minimal 1 kegiatan."
                            );

                        // Kembalikan form yang tadi disembunyikan (dibatalkan hapusnya) agar user bisa mengisinya
                        document.querySelectorAll('.deleted-row').forEach(row => {
                            row.classList.remove('deleted-row', 'hidden');
                            row.querySelectorAll('input, select, textarea').forEach(el => el
                                .disabled = false);
                        });
                    }
                });

                {{-- PERUBAHAN: Trigger On-Load Render untuk Halaman Edit --}}
                setTimeout(() => {
                    generateAllRows();
                    setTimeout(() => {
                        calculateAll();
                    }, 500);
                }, 100);

            });
        </script>
    @endpush
</x-app-layout>
