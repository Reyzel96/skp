<x-app-layout>
    @section('page-title', 'Persetujuan & Pencairan Usulan Prodi')

    {{-- ==========================================
         SIDEBAR
    ========================================== --}}
@section('sidebar')
    @include('partials.sidebar')
@endsection

    {{-- ==========================================
         CONTENT
    ========================================== --}}
    @section('content')
        <style>
            /* Hide number spinners globally in this page */
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            input[type=number] {
                -moz-appearance: textfield;
            }
        </style>
        <div class="mb-6">
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 text-emerald-700 shadow-sm rounded flex justify-between items-center font-bold">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 text-red-700 shadow-sm rounded flex justify-between items-center font-bold">
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <div x-data="{ tab: 'waiting' }">
            <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Persetujuan <span class="text-blue-600">& Pencairan</span></h2>
                    <p class="text-gray-500 text-sm font-medium mt-1">Kelola usulan program kegiatan dari seluruh program studi fakultas.</p>
                </div>

                {{-- NAV TAB --}}
                <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200 shadow-inner">
                    <button @click="tab = 'waiting'"
                            :class="tab === 'waiting'
                                ? 'bg-white text-blue-600 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'"
                            class="px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="fas fa-clock"></i> Menunggu Persetujuan
                        @if($waitingList->count() > 0)
                            <span class="bg-blue-600 text-white text-[8px] px-1.5 py-0.5 rounded-full ml-1">{{ $waitingList->count() }}</span>
                        @endif
                    </button>

                    <button @click="tab = 'history'"
                            :class="tab === 'history'
                                ? 'bg-white text-blue-600 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'"
                            class="px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="fas fa-history"></i> Riwayat Persetujuan
                    </button>
                </div>
            </div>

            {{-- ==========================================
                 TAB 1: MENUNGGU PERSETUJUAN
            ========================================== --}}
            <div x-show="tab === 'waiting'" x-transition class="animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-black text-gray-400 uppercase text-[10px] tracking-widest">Prodi</th>
                                <th class="px-4 py-3 text-left font-black text-gray-400 uppercase text-[10px] tracking-widest">Kegiatan</th>
                                <th class="px-4 py-3 text-right font-black text-gray-400 uppercase text-[10px] tracking-widest">Usulan</th>
                                <th class="px-4 py-3 text-right font-black text-blue-600 uppercase text-[10px] tracking-widest">Disetujui / Cair</th>
                                <th class="px-4 py-3 text-left w-64 font-black text-gray-400 uppercase text-[10px] tracking-widest">Status & Catatan</th>
                                <th class="px-4 py-3 text-center font-black text-gray-400 uppercase text-[10px] tracking-widest">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y align-top">
                            @forelse($waitingList as $pengajuan)
                                <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer group"
                                    onclick="window.location='{{ route('kegiatan-prodi.show', $pengajuan->id) }}'">
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-black text-gray-900 uppercase text-[10px] tracking-tight">Kaprodi {{ $pengajuan->prodi->nama ?? '-' }}</span>
                                            <span class="text-[9px] text-gray-500 font-bold italic">{{ $pengajuan->user->name }}</span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="font-bold text-gray-800 text-xs leading-tight block group-hover:text-blue-700 transition-colors">{{ $pengajuan->nama_kegiatan_prodi ?? $pengajuan->programKegiatan->nama_kegiatan ?? '-' }}</span>
                                    </td>

                                    <td class="px-4 py-4 text-right font-mono text-gray-600 text-xs">
                                        Rp {{ number_format($pengajuan->anggaran_usulan_prodi, 0, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4 text-right font-mono text-green-700 font-black text-xs">
                                        Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4">
                                        @php
                                            $statusClass = match($pengajuan->status_persetujuan) {
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'disetujui_dekan' => 'bg-blue-100 text-blue-800',
                                                'disetujui_wadek' => 'bg-indigo-100 text-indigo-800',
                                                'dicairkan' => 'bg-green-100 text-green-800',
                                                'selesai' => 'bg-gray-200 text-gray-800',
                                                'ditolak' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        @endphp

                                        <span class="px-2 py-1 rounded text-[9px] font-black uppercase {{ $statusClass }} shadow-sm">
                                            {{ str_replace('_', ' ', $pengajuan->status_persetujuan) }}
                                        </span>

                                        <div class="mt-2 flex flex-col gap-1">
                                            @if($pengajuan->status_persetujuan !== 'pending')
                                                <div class="text-[9px] text-gray-600 font-bold">
                                                    <div class="flex items-center gap-1 mb-1">
                                                        <i class="fas fa-clock text-gray-300"></i>
                                                        <span>{{ $pengajuan->updated_at->format('d/m/Y H:i') }}</span>
                                                    </div>

                                                    @if($pengajuan->catatan)
                                                        <div class="bg-white/50 p-2 rounded border border-gray-100 mt-1 shadow-sm">
                                                            <span class="italic text-gray-500 leading-tight block">"{{ Str::limit($pengajuan->catatan, 50) }}"</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-center space-y-1.5" onclick="event.stopPropagation()">
                                        @if(auth()->user()->hasRole('Dekan') && $pengajuan->status_persetujuan === 'pending')
                                            <button onclick="openAccProdiModal({{ $pengajuan->id }}, {{ $pengajuan->anggaran_usulan_prodi }})"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 text-[10px] rounded-lg font-black shadow-sm w-full transition uppercase tracking-wider">
                                                Setujui Usulan Program
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasRole('Wadek 2') && $pengajuan->status_persetujuan === 'disetujui_dekan')
                                            <button onclick="openApprovalModal({{ $pengajuan->id }}, {{ $pengajuan->anggaran_disetujui_prodi }}, {{ $pengajuan->wadek_can_edit ? 'true' : 'false' }})"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 text-[10px] rounded-lg font-black shadow-sm w-full transition uppercase tracking-wider">
                                                ACC Wadek 2
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasRole('Admin Keuangan Fakultas'))
                                            @if(in_array($pengajuan->status_persetujuan, ['disetujui_wadek', 'dicairkan']))
                                                @php
                                                    $cairSblmnya = method_exists($pengajuan, 'getTotalRealisasi') ? $pengajuan->getTotalRealisasi() : 0;
                                                    $sisaPencairan = $pengajuan->anggaran_disetujui_prodi - $cairSblmnya;
                                                @endphp

                                                @if($sisaPencairan > 0)
                                                    <button onclick="openRealisasiModal({{ $pengajuan->id }}, {{ $sisaPencairan }})"
                                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 text-[10px] rounded-lg font-black shadow-sm w-full transition flex items-center justify-center uppercase tracking-wider">
                                                        <i class="fas fa-money-bill-wave mr-1.5"></i> Cairkan
                                                    </button>
                                                @else
                                                    <span class="text-[9px] text-gray-400 italic font-bold block mt-1 uppercase">Pagu Habis</span>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-gray-400 italic">
                                        <i class="fas fa-check-circle text-4xl mb-3 text-emerald-100 block"></i>
                                        Selesai! Tidak ada usulan yang menunggu tindakan Anda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ==========================================
                 TAB 2: RIWAYAT PERSETUJUAN
            ========================================== --}}
            <div x-show="tab === 'history'" x-transition class="animate-fadeIn" style="display:none;">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-black text-gray-400 uppercase text-[10px] tracking-widest">Prodi</th>
                                <th class="px-4 py-3 text-left font-black text-gray-400 uppercase text-[10px] tracking-widest">Kegiatan</th>
                                <th class="px-4 py-3 text-right font-black text-gray-400 uppercase text-[10px] tracking-widest">Pagu Final</th>
                                <th class="px-4 py-3 text-right font-black text-green-600 uppercase text-[10px] tracking-widest">Realisasi</th>
                                <th class="px-4 py-3 text-left w-64 font-black text-gray-400 uppercase text-[10px] tracking-widest">Status & Waktu</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y align-top">
                            @forelse($historyList as $pengajuan)
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer group"
                                    onclick="window.location='{{ route('kegiatan-prodi.show', $pengajuan->id) }}'">
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-black text-gray-900 uppercase text-[10px] tracking-tight">Kaprodi {{ $pengajuan->prodi->nama ?? '-' }}</span>
                                            <span class="text-[9px] text-gray-500 font-bold italic">{{ $pengajuan->user->name }}</span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="font-bold text-gray-800 text-xs leading-tight block">{{ $pengajuan->nama_kegiatan_prodi ?? $pengajuan->programKegiatan->nama_kegiatan ?? '-' }}</span>
                                    </td>

                                    <td class="px-4 py-4 text-right font-mono text-blue-700 font-bold text-xs">
                                        Rp {{ number_format($pengajuan->anggaran_disetujui_prodi, 0, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4 text-right font-mono text-green-700 font-black text-xs">
                                        Rp {{ number_format($pengajuan->getTotalRealisasi(), 0, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4">
                                        @php
                                            $statusClass = match($pengajuan->status_persetujuan) {
                                                'dicairkan' => 'bg-green-100 text-green-800',
                                                'selesai' => 'bg-gray-200 text-gray-800',
                                                'ditolak' => 'bg-red-100 text-red-800',
                                                'disetujui_admin' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                default => 'bg-blue-50 text-blue-800 border-blue-100'
                                            };
                                        @endphp

                                        <span class="px-2 py-1 rounded text-[9px] font-black uppercase {{ $statusClass }} shadow-sm">
                                            {{ str_replace('_', ' ', $pengajuan->status_persetujuan) }}
                                        </span>

                                        <div class="mt-2 text-[9px] text-gray-400 font-bold">
                                            <div class="flex items-center gap-1">
                                                <i class="fas fa-calendar-check text-gray-300"></i>
                                                <span>Selesai: {{ $pengajuan->updated_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-gray-400 italic">
                                        Belum ada riwayat persetujuan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MODAL ACC DEKAN --}}
        <div id="accProdiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left transform transition-all animate-fadeIn">
                    <h3 class="text-lg font-black text-gray-900 mb-4 uppercase tracking-tight">Persetujuan Usulan Prodi (Dekan)</h3>
                    <form id="accForm" method="POST">
                        @csrf @method('PATCH')
                        <div class="space-y-4">
                            {{-- 1. Tindakan (Pindah ke Atas) --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tindakan</label>
                                <select name="action" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                                    <option value="setuju">Setujui Usulan (Langsung ke Admin)</option>
                                    <option value="lanjut_wadek">Lanjutkan Review ke Wadek 2</option>
                                    <option value="tolak">Tolak Usulan</option>
                                </select>
                            </div>

                            {{-- 2. Nominal Disetujui --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Disetujui (Pagu Akhir)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                                    <input type="number" name="anggaran_disetujui_prodi" id="inputPaguProdi" required
                                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border-gray-200 rounded-xl font-black text-lg focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                            </div>

                            {{-- 3. Catatan --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan (Opsional)</label>
                                <textarea name="catatan" rows="3" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500" placeholder="Berikan alasan jika ditolak atau arahan tambahan..."></textarea>
                            </div>
                        </div>
                        <div class="mt-8 flex gap-3">
                            <button type="button" onclick="closeModal('accProdiModal')" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg">Simpan Keputusan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL ACC WADEK 2 --}}
        <div id="approvalModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-left transform transition-all animate-fadeIn">
                    <h3 class="text-lg font-black text-gray-900 mb-4 uppercase tracking-tight font-black">Verifikasi Usulan (Wadek 2)</h3>
                    <form id="approvalForm" method="POST">
                        @csrf @method('PATCH')
                        <div class="space-y-4">
                            {{-- Tindakan --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tindakan</label>
                                <select name="action" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                                    <option value="setuju">Verifikasi & Lanjutkan</option>
                                    <option value="tolak">Tolak / Kembalikan</option>
                                </select>
                            </div>

                            {{-- Nominal (Read-only if dekan chose 'Setujui Usulan') --}}
                            <div id="wadekNominalContainer">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Anggaran</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                                    <input type="number" name="anggaran_disetujui_prodi" id="inputWadekPagu" required
                                        class="w-full pl-10 pr-4 py-3 border-gray-200 rounded-xl font-black text-lg focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <p id="wadekEditHint" class="text-[9px] font-bold text-gray-400 mt-1 uppercase italic hidden">* Nominal dikunci oleh Dekan (Read-only)</p>
                            </div>

                            {{-- Catatan --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan Verifikasi</label>
                                <textarea name="catatan" rows="3" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Tambahkan instruksi atau catatan..."></textarea>
                            </div>
                        </div>
                        <div class="mt-8 flex gap-3">
                            <button type="button" onclick="closeModal('approvalModal')" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Batal</button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg">Kirim Verifikasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL REALISASI (CAIRKAN) --}}
        <div id="realisasiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-left transform transition-all">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Pencairan Dana Usulan</h3>
                        <button onclick="closeModal('realisasiModal')" class="text-gray-400 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
                    </div>

                    <div class="mb-6 p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest block mb-1">Maksimal Sisa Pagu:</span>
                        <span id="textSisaPagu" class="text-2xl font-black text-emerald-800">Rp 0</span>
                    </div>

                    <form id="realisasiForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal Cair</label>
                                    <input type="date" name="tanggal_pencairan" value="{{ date('Y-m-d') }}" required
                                        class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl font-bold text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal Cair (Rp)</label>
                                    <input type="number" name="nominal" id="inputNominalCair" required readonly
                                        class="w-full p-3 bg-gray-100 border-gray-200 rounded-xl font-black text-sm text-gray-500 cursor-not-allowed [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <p class="text-[8px] font-bold text-gray-400 mt-1 uppercase italic">* Nominal dikunci sesuai verifikasi Dekan/Wadek</p>
                                </div>                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Upload Bukti Transfer</label>
                                <input type="file" name="bukti_transfer" accept="image/*,application/pdf"
                                    class="w-full p-2 bg-gray-50 border-gray-200 rounded-xl text-xs font-bold focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Keterangan / Berita Acara</label>
                                <textarea name="keterangan" rows="2" class="w-full p-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500"></textarea>
                            </div>
                        </div>
                        <div class="mt-8">
                            <button type="submit" class="w-full px-4 py-4 bg-green-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-green-700 transition shadow-lg flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i> Konfirmasi Pencairan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function closeModal(id) {
                document.getElementById(id).classList.add('hidden');
            }

            function openAccProdiModal(id, usulanAwal) {
                document.getElementById('accForm').action = `/kegiatan-prodi/${id}/approve`;
                document.getElementById('inputPaguProdi').value = usulanAwal;
                document.getElementById('accProdiModal').classList.remove('hidden');
            }

            function openApprovalModal(id) {
                document.getElementById('approvalForm').action = `/kegiatan-prodi/${id}/approve`;
                document.getElementById('approvalModal').classList.remove('hidden');
            }

            function openRealisasiModal(id, sisaMax) {
                document.getElementById('realisasiForm').action = `/kegiatan-prodi/${id}/cairkan`;
                const inputCair = document.getElementById('inputNominalCair');
                inputCair.max = sisaMax;
                inputCair.value = sisaMax;
                document.getElementById('textSisaPagu').innerText = 'Rp ' + sisaMax.toLocaleString('id-ID');
                document.getElementById('realisasiModal').classList.remove('hidden');
            }
        </script>
    @endsection
</x-app-layout>
