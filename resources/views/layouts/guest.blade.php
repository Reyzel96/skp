<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Favicon --}}
        <link rel="icon" type="image/png" href="{{ asset('images/umsura.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- FontAwesome --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Custom Animations CSS --}}
        <style>
            @keyframes blob {
                0%, 100% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.06); }
                66% { transform: translate(-20px, 20px) scale(0.94); }
            }
            .animate-blob { animation: blob 14s infinite ease-in-out; }
            .animation-delay-2000 { animation-delay: 2s; }
            .animation-delay-4000 { animation-delay: 4s; }

            @keyframes fade-in-up {
                0% { opacity: 0; transform: translateY(16px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in-up { animation: fade-in-up 0.6s cubic-bezier(0.22, 1, 0.36, 1) backwards; }

            @media (prefers-reduced-motion: reduce) {
                .animate-blob, .animate-fade-in-up {
                    animation: none !important;
                    transform: none !important;
                }
                *, ::before, ::after {
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
<body class="font-sans text-gray-900 antialiased bg-gray-50 relative min-h-screen min-h-dvh flex flex-col justify-center items-center p-4 sm:p-6">
    
    {{-- Background Interaktif (Parallax Mouse) - Hanya tampil di Desktop --}}
    <div class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none z-0 hidden sm:block" 
         x-data="{ mouseX: 0, mouseY: 0 }" 
         @mousemove="mouseX = ($event.clientX / window.innerWidth - 0.5) * 30; mouseY = ($event.clientY / window.innerHeight - 0.5) * 30;"
         :style="{ transform: `translate(${mouseX}px, ${mouseY}px)` }">
        
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob will-change-transform"></div>
        <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-brand-lime/10 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob will-change-transform animation-delay-2000"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-96 h-96 bg-lime-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob will-change-transform animation-delay-4000"></div>
    </div>

    {{-- Background untuk Mobile (Statis) --}}
    <div class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none z-0 sm:hidden">
        <div class="absolute top-[-20%] right-[-20%] w-72 h-72 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
        <div class="absolute bottom-[-20%] left-[-20%] w-72 h-72 bg-lime-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
    </div>

    <div class="relative z-10 max-w-[560px] w-[calc(100%-32px)] animate-fade-in-up" style="animation-delay: 0.2s;">

        {{-- Kartu Login --}}
        {{-- Padding: 40px horizontal / 34px vertical desktop --}}
        <div class="relative bg-white shadow-[0_28px_70px_-24px_rgba(15,132,32,0.22)] overflow-hidden rounded-[32px] border border-slate-100 px-6 py-7 sm:px-10 sm:py-[34px]">
            {{-- Dekorasi sudut hijau subtle --}}
            <div class="absolute -top-20 -right-20 w-48 h-48 bg-gradient-to-br from-brand-lime/10 to-brand-yellow-green/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Bagian Logo --}}
            <div class="relative flex flex-col items-center mb-5 animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('images/umsura.png') }}" alt="Logo UMSurabaya" class="w-[178px] sm:w-[200px] max-w-full object-contain">
                </div>
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>