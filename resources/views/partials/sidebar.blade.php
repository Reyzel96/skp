@php
    $user = auth()->user();
    if (!$user) return;
    
    $roles = $user->roles;
    $userRoles = $roles->pluck('name');

    $isSuperAdmin = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'super admin'));
    $isDekan = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'dekan'));
    $isWadek1 = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'wadek 1'));
    $isWadek2 = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'wadek 2'));
    $isAdminKeuangan = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'admin keuangan') || str_contains(strtolower($n), 'keuangan fakultas'));
    $isKaprodi = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'kaprodi'));
    $isSekprodi = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'sekprodi'));
    $isKepalaLab = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'kepala lab') || str_contains(strtolower($n), 'laboratorium'));
    $isKepalaUPF = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'kepala upf') || str_contains(strtolower($n), 'unit penunjang'));
    $isDosen = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'dosen'));
    $isRektorat = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'rektorat'));
    $isTendik = $roles->contains('kategori', 'tendik');
    $isPimpinanFakultas = ($isDekan || $isWadek1 || $isWadek2);
    $isActivityReviewer = \App\Models\MasterSkpTendik::whereIn('penilai_role', $userRoles)->exists();
    $isReviewer = ($isPimpinanFakultas || $isKaprodi || $isSekprodi || $isKepalaLab || $isKepalaUPF || $isRektorat || $isActivityReviewer);
    $showRekap = ($isDekan || $isKaprodi || $isRektorat);
    $isAdminProdi = $userRoles->contains(fn($n) => str_contains(strtolower($n), 'administrasi prodi'));
    $showAnggaran = ($isDekan || $isWadek2 || $isAdminKeuangan || $isKaprodi || $isSekprodi || $isKepalaUPF || $isAdminProdi || $isRektorat) && !$isWadek1;
    $isUnitPengusul = ($isKaprodi || $isSekprodi || $isAdminProdi || $isKepalaUPF);

    $dashRoute = 'dashboard';
    if ($isSuperAdmin) $dashRoute = 'superadmin.dashboard';
    elseif ($isRektorat) $dashRoute = 'rektorat.dashboard';
    elseif ($isDekan) $dashRoute = 'dekan.dashboard';
    elseif ($isWadek1 || $isWadek2) $dashRoute = 'wakildekan.dashboard';
    elseif ($isKaprodi) $dashRoute = 'kaprodi.dashboard';
    elseif ($isSekprodi) $dashRoute = 'sekprodi.dashboard';
    elseif ($isKepalaLab) $dashRoute = 'kepalalab.dashboard';
    elseif ($isKepalaUPF) $dashRoute = 'unitpenunjang.dashboard';
    elseif ($isAdminKeuangan) $dashRoute = 'adminkeuanganfakultas.dashboard';
    elseif ($isTendik) $dashRoute = 'pegawai.dashboard';
@endphp

{{-- 1. DASHBOARD --}}
<div class="mb-4">
    <a href="{{ route($dashRoute) }}"
       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('*.dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
        <i class="fas fa-home w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
        <span x-show="sidebarOpen" x-transition>Dashboard</span>
    </a>
</div>

{{-- 2. AREA SUPER ADMIN --}}
@if($isSuperAdmin)
    <div class="mb-4">
        <p x-show="sidebarOpen" x-transition class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Manajemen Sistem</p>
        <div class="space-y-0.5">
            <a href="{{ route('superadmin.users.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.users.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-users w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Kelola User</span>
            </a>
            <a href="{{ route('superadmin.prodi.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.prodi.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-university w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Kelola Prodi</span>
            </a>
            <a href="{{ route('superadmin.roles.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.roles.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-user-shield w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Kelola Jabatan</span>
            </a>
            <a href="{{ route('superadmin.periode-skp.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.periode-skp.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-calendar-alt w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Periode SKP</span>
            </a>
            <a href="{{ route('superadmin.daftar-tupoksi.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.daftar-tupoksi.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-book w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Daftar Tupoksi</span>
            </a>
        </div>
    </div>

    <div class="mb-4">
        <p x-show="sidebarOpen" x-transition class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Generate SKP</p>
        <div class="space-y-0.5">
            <a href="{{ route('superadmin.master-skp-tendik.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.master-skp-tendik.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-copy w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>SKP Tendik</span>
            </a>
            <a href="{{ route('superadmin.skp-dosen.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.skp-dosen.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-chalkboard-teacher w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>SKP Dosen Kuantitatif</span>
            </a>
            <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('superadmin.skp-dosen-kualitatif.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-chalkboard w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>SKP Dosen Kualitatif</span>
            </a>
        </div>
    </div>
@endif

{{-- 3. MANAJEMEN ANGGARAN --}}
@if($showAnggaran)
    <div class="mb-4">
        <p x-show="sidebarOpen" x-transition class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Anggaran & LPJ</p>
        <div class="space-y-0.5">
            @if($isAdminKeuangan)
                <a href="{{ route('periode-anggaran.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('periode-anggaran.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <i class="fas fa-calendar-check w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                    <span x-show="sidebarOpen" x-transition>Periode Anggaran</span>
                </a>
            @endif
            <a href="{{ route('program-kegiatan.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('program-kegiatan.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-database w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Program Fakultas</span>
            </a>
            @if($isUnitPengusul)
                <a href="{{ route('kegiatan-prodi.my-pengajuan') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('kegiatan-prodi.my-pengajuan') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <i class="fas fa-paper-plane w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                    <span x-show="sidebarOpen" x-transition>Usulan Kegiatan Prodi</span>
                </a>
                <a href="{{ route('lpj.my-lpj') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('lpj.my-lpj') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <i class="fas fa-file-invoice w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                    <span x-show="sidebarOpen" x-transition>LPJ Program Kegiatan</span>
                </a>
            @else
                <a href="{{ route('kegiatan-prodi.list-admin') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('kegiatan-prodi.list-admin') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <i class="fas fa-clipboard-check w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                    <span x-show="sidebarOpen" x-transition>Usulan Program Prodi</span>
                </a>
                <a href="{{ route('lpj.monitoring') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('lpj.monitoring') || request()->routeIs('lpj.show') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                    <i class="fas fa-chart-line w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                    <span x-show="sidebarOpen" x-transition>Monitoring LPJ</span>
                </a>
            @endif
        </div>
    </div>
@endif

{{-- 4. KINERJA & SDM --}}
<div class="mb-4">
    <p x-show="sidebarOpen" x-transition class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Kinerja & SDM</p>
    <div class="space-y-0.5">
        @if($isReviewer)
            <a href="{{ route('penilai.approval-skp-tendik.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('penilai.approval-skp-tendik.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-clipboard-check w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Approval SKP Tendik</span>
            </a>
            <a href="{{ route('penilai.approval-skp-dosen-kualitatif.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('penilai.approval-skp-dosen-kualitatif.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-clipboard-list w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Approval SKP Kualitatif</span>
            </a>
            <a href="{{ route('dekan.persetujuan-skp') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('dekan.persetujuan-skp') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-file-signature w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Approval SKP Dosen</span>
            </a>
            <a href="{{ route('penilai.evaluasi.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('penilai.evaluasi.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-users-cog w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Penilaian SKP</span>
            </a>
        @endif

        @if($showRekap)
            <a href="{{ route('rekap-skp.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('rekap-skp.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                <i class="fas fa-list-alt w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                <span x-show="sidebarOpen" x-transition>Rekap SKP</span>
            </a>
        @endif

        @if(!$isSuperAdmin)
            <div class="mt-3 pt-3 border-t border-white/10">
                <p x-show="sidebarOpen" x-transition class="px-3 text-[9px] font-semibold text-slate-500 uppercase tracking-wider mb-1">SKP Saya</p>
                @if($isDosen || $isPimpinanFakultas || $isKaprodi || $isSekprodi || $isKepalaLab)
                    <a href="{{ route('pegawai.skp-dosen.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('pegawai.skp-dosen.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                        <i class="fas fa-user-graduate w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                        <span x-show="sidebarOpen" x-transition>SKP Kuantitatif</span>
                    </a>
                    <a href="{{ route('pegawai.skp-dosen-kualitatif.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('pegawai.skp-dosen-kualitatif.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                        <i class="fas fa-chalkboard w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                        <span x-show="sidebarOpen" x-transition>SKP Kualitatif</span>
                    </a>
                @endif
                @if($isTendik || $isAdminKeuangan || $isKepalaUPF)
                    <a href="{{ route('pegawai.skp-tendik.index') }}" class="flex items-center px-3 py-2 text-sm rounded-lg transition-all {{ request()->routeIs('pegawai.skp-tendik.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10' }}">
                        <i class="fas fa-user-tie w-5 text-center" :class="sidebarOpen ? 'mr-3' : 'mx-auto'"></i> 
                        <span x-show="sidebarOpen" x-transition>SKP Tendik</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
