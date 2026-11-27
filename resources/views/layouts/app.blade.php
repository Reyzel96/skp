<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Penting: csrf-token diperlukan untuk AJAX/Fetch (Heartbeat) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Data untuk script heartbeat/auto-logout di resources/js/app.js --}}
    <meta name="session-lifetime" content="{{ config('session.lifetime') }}">
    <meta name="route-keep-alive" content="{{ route('keep-alive') }}">
    <meta name="route-login" content="{{ route('login') }}">
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

        /* Mencegah flicker (kedip) saat Alpine.js pertama kali load */
        [x-cloak] { display: none !important; }
    </style>
</head>

<body data-route="{{ Route::currentRouteName() }}" class="font-sans antialiased text-gray-800 bg-gray-50" x-data="{}">
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
                padding: 5px 7px !important;
                font-size: 12px !important;
                line-height: 1.35 !important;
            }
            #print-area textarea {
                min-height: 30px !important;
                height: auto !important;
                overflow: visible !important;
            }

            /* Tabel Perilaku Kerja saat print: kolom "REALISASI PEGAWAI & BUKTI
               DUKUNG" KINI IKUT DICETAK (td no-print dihapus dari show.blade.php),
               jadi layout kolom biarkan auto mengikuti proporsi aslinya. */
            #print-area table.table-perilaku {
                table-layout: auto !important;
            }

            /* Perlebar khusus kolom UMPAN BALIK di tabel Perilaku Kerja saat print
               (thead th ke-4 / tbody td ke-4 karena kolom realisasi sekarang tampil),
               tanpa menyentuh kolom lain. */
            #print-area table.table-perilaku thead th:nth-child(4),
            #print-area table.table-perilaku tbody td:nth-child(4) {
                width: 30% !important;
            }

            #print-area div:has(> canvas) {
                height: 180px !important;
                page-break-inside: avoid;
            }
        }
    </style>
    
    <div class="min-h-screen flex">

        {{-- SIDEBAR KIRI - Modern & Responsive --}}
        @php $sbOpen = request()->cookie('sidebarOpen', 'true') !== 'false'; @endphp
        
        {{-- PERBAIKAN ANIMASI & ANTI-FLICKER: 
             1. Class dasar HTML disamakan dengan state awal Alpine ({{ $sbOpen ? 'md:w-64' : 'md:w-0' }}) agar tidak muter-muter saat pindah menu.
             2. Gunakan transition-all agar animasi width di desktop dan transform di mobile sama-sama jalan mulus. --}}
        <aside id="sidebar" data-turbo-permanent 
            class="bg-white border-r border-gray-100 shrink-0 z-50 shadow-xl w-64
                   fixed inset-y-0 left-0 -translate-x-full overflow-y-auto overflow-x-hidden
                   md:translate-x-0 md:sticky md:top-0 md:h-screen md:overflow-y-auto md:z-20
                   transition-all duration-300 ease-in-out
                   {{ $sbOpen ? 'md:w-64' : 'md:w-0' }}"
            :class="{
                'translate-x-0': $store.sidebar.mobileOpen,
                'md:w-64': $store.sidebar.open,
                'md:w-0': !$store.sidebar.open
            }">

            {{-- Wrapper tetap w-64 agar konten tidak pecah saat sidebar mengecil di desktop --}}
            <div class="flex flex-col h-full w-64 min-h-0 overflow-hidden">
                @yield('sidebar')
            </div>
        </aside>

        {{-- Backdrop overlay (mobile only) --}}
        <div :class="$store.sidebar.mobileOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
             @click="$store.sidebar.closeMobile()"
             class="fixed inset-0 bg-black/50 z-40 md:hidden print:hidden transition-opacity duration-300 ease-in-out"></div>

        {{-- KONTEN KANAN --}}
        <div class="flex-1 flex flex-col min-h-screen">

            {{-- HEADER ATAS --}}
            <header class="shrink-0 z-10 bg-white border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">
                    {{-- Tombol Hamburger: Cek lebar layar langsung saat diklik --}}
                    <button @click="window.innerWidth < 768 ? $store.sidebar.toggleMobile() : $store.sidebar.toggle()"
                            class="text-gray-500 hover:text-gray-700 transition-colors p-2 rounded-lg focus:outline-none z-50"
                            title="Toggle Sidebar">
                        <i class="fas fa-bars-staggered text-lg transition-transform duration-300 ease-in-out" :class="($store.sidebar.open || $store.sidebar.mobileOpen) ? 'rotate-180' : ''"></i>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800 tracking-tight">
                        @yield('page-title', 'Dashboard')
                    </h1>
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
</body>
</html>