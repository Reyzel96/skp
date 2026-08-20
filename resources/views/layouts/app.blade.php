<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Penting: csrf-token diperlukan untuk AJAX/Fetch (Heartbeat) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Perfoma') }}</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('images/umsura.png') }}">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Animasi Alert */
        @keyframes slideInDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-animated { animation: slideInDown 0.4s ease-out forwards; }
    </style>
</head>

<body class="font-sans antialiased text-gray-800 bg-gray-50" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }" x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 1cm;
            }

            html, body {
                height: auto !important;
                overflow: visible !important;
                background: #fff !important;
            }

            body > div.min-h-screen,
            body > div.min-h-screen > div.flex-1 {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
            }

            aside, header, .no-print {
                display: none !important;
            }

            main#print-area {
                display: block !important;
                position: static !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #print-area table {
                page-break-inside: auto;
            }
            #print-area th {
                page-break-inside: avoid;
            }
            #print-area table td, #print-area table th {
                padding: 4px 6px !important;
                font-size: 11px !important;
                line-height: 1.3 !important;
            }
            #print-area textarea {
                min-height: 30px !important;
                height: auto !important;
                overflow: visible !important;
            }

            /* Perbaikan kolom kosong & lebar tabel Perilaku Kerja saat print:
               kolom "REALISASI & BUKTI DUKUNG" disembunyikan (.no-print),
               sisa kolom diberi lebar tetap agar tidak ada ruang kosong
               dan kolom UMPAN BALIK lebih lega (~32%). */
            /* Perbaikan kolom kosong di tabel Perilaku Kerja saat print:
               kolom "REALISASI & BUKTI DUKUNG" disembunyikan (.no-print),
               jadi biarkan sisa kolom auto melebar mengisi 100% lebar tabel. */
            #print-area table.table-perilaku {
                table-layout: auto !important;
            }
            #print-area table.table-perilaku th,
            #print-area table.table-perilaku td {
                width: auto !important;
            }

            /* Perlebar khusus kolom UMPAN BALIK di tabel Perilaku Kerja saat print,
               tanpa menyentuh kolom lain (untuk hindari blank column berulang). */
            #print-area table.table-perilaku thead th:nth-child(3),
            #print-area table.table-perilaku tbody td:nth-child(4) {
                width: 30% !important;
            }

            #print-area div:has(> canvas) {
                height: 180px !important;
                page-break-inside: avoid;
            }
        }
    </style>
    <div class="min-h-screen flex overflow-hidden">

        {{-- SIDEBAR KIRI - Modern & Responsive --}}
        <aside class="bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white flex flex-col shrink-0 z-20 shadow-2xl transition-all duration-300 ease-in-out"
            :class="sidebarOpen ? 'w-64' : 'w-20'">

            {{-- Logo Header --}}
            <div class="p-4 text-center border-b border-white/10 overflow-hidden h-[81px] flex flex-col justify-center bg-slate-900/30">
                <template x-if="sidebarOpen">
                    <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <h2 class="text-2xl font-black tracking-wider text-emerald-400 truncate">UMSURABAYA</h2>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium tracking-widest truncate">SISTEM SKP</p>
                    </div>
                </template>
                <template x-if="!sidebarOpen">
                    <div class="flex justify-center" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <span class="text-3xl font-black text-emerald-400">U</span>
                    </div>
                </template>
            </div>

            {{-- Navigation Menu --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent;">
                @yield('sidebar')
            </nav>

            {{-- Footer --}}
            <div class="p-4 border-t border-white/10 text-xs text-slate-500 text-center bg-slate-900/30 overflow-hidden">
                <template x-if="sidebarOpen">
                    <p x-transition class="truncate">&copy; {{ date('Y') }} UMSURABAYA</p>
                </template>
                <template x-if="!sidebarOpen">
                    <p x-transition>&copy;</p>
                </template>
            </div>
        </aside>

        {{-- KONTEN KANAN --}}
        <div class="flex-1 flex flex-col h-screen overflow-hidden">

            {{-- HEADER ATAS --}}
            <header class="bg-white border-b border-gray-200 shrink-0 z-10 shadow-sm relative">
                <div class="flex justify-between items-center px-6 py-4">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-blue-600 transition-colors p-2 rounded-lg hover:bg-blue-50 focus:outline-none">
                            <i class="fas fa-bars-staggered text-lg" :class="sidebarOpen ? '' : 'rotate-180'"></i>
                        </button>
                        <h1 class="text-xl font-black text-gray-800 tracking-tight">
                            @yield('page-title', 'Dashboard')
                        </h1>
                    </div>

                    <div class="flex items-center space-x-6">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-gray-800">{{ auth()->user()->profile->nama_lengkap ?? auth()->user()->name ?? 'Guest' }}</div>
                            <div class="text-[10px] uppercase font-bold tracking-widest text-blue-600">{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</div>
                        </div>

                        <div class="h-8 w-px bg-gray-200"></div>

                        <form method="POST" action="{{ route('logout') }}" class="m-0" id="logout-form" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
                            @csrf
                            <button type="submit"
                                class="text-gray-400 hover:text-red-600 transition-colors flex items-center justify-center p-2 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-200"
                                title="Keluar / Logout">
                                <i class="fas fa-power-off text-lg"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- AREA KONTEN UTAMA --}}
            <main id="print-area" class="flex-1 overflow-y-auto p-6 bg-gray-50/50">

                {{-- ========================================================== --}}
                {{-- FLASH MESSAGES GLOBAL (DENGAN ALPINE.JS BISA DICLOSE/HILANG) --}}
                {{-- ========================================================== --}}

                @if (session('success'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition.duration.500ms
                         class="alert-animated bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-lg mb-6 shadow-sm flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-emerald-500 text-xl mt-0.5"></i>
                            <div>
                                <h3 class="font-bold text-sm">Berhasil!</h3>
                                <p class="text-sm mt-1 opacity-90">{{ session('success') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-transition.duration.500ms
                         class="alert-animated bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg mb-6 shadow-sm flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
                            <div>
                                <h3 class="font-bold text-sm">Terjadi Kesalahan!</h3>
                                <p class="text-sm mt-1 opacity-90">{{ session('error') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-700 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                {{-- Inject Konten Halaman --}}
                @yield('content')

            </main>
        </div>
    </div>

    @stack('scripts')

    {{-- =========================================================================
         🔴 SCRIPT AUTO-LOGOUT & HEARTBEAT (ANTI GHOST SESSION & MULTIPLE LOGIN)
         ========================================================================= --}}
    @auth
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let heartbeatInterval;

                // 1. HEARTBEAT LOGIC: Kirim sinyal setiap 60 detik (60.000ms)
                const startHeartbeat = () => {
                    heartbeatInterval = setInterval(function() {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        if (!csrfToken) return;

                        fetch("{{ route('keep-alive') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        }).then(response => {
                            if (response.status === 401 || response.status === 419) {
                                window.location.href = "{{ route('login') }}";
                            }
                        }).catch(error => console.log('Heartbeat terputus atau jaringan offline'));
                    }, 60000);
                };

                startHeartbeat();

                // 2. AUTO LOGOUT LOGIC: Jika tidak ada pergerakan mouse/keyboard
                let sessionLifetimeMs = {{ config('session.lifetime') * 60 * 1000 }};
                let timeoutDuration = sessionLifetimeMs + 2000;
                let sessionTimeout;

                const startTimer = () => {
                    sessionTimeout = setTimeout(function() {
                        clearInterval(heartbeatInterval);
                        const logoutForm = document.getElementById('logout-form');
                        if (logoutForm) {
                            logoutForm.submit();
                        } else {
                            window.location.href = "{{ route('login') }}";
                        }
                    }, timeoutDuration);
                };

                const resetTimer = () => {
                    clearTimeout(sessionTimeout);
                    startTimer();
                };

                document.addEventListener('click', resetTimer, { passive: true });
                document.addEventListener('keypress', resetTimer, { passive: true });
                document.addEventListener('mousemove', resetTimer, { passive: true });
                document.addEventListener('scroll', resetTimer, { passive: true });

                startTimer();
            });
        </script>
    @endauth
</body>
</html>
