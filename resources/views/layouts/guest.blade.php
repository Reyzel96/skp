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
    </head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50">

        <div class="w-full sm:max-w-md mt-0 px-8 py-12 bg-white shadow-2xl shadow-gray-200/50 overflow-hidden sm:rounded-[3rem] border border-gray-100">
            {{-- Bagian Logo Saja --}}
            <div class="flex flex-col items-center mb-10">
                <div class="w-30 h-25 rounded-3xl flex items-center justify-center overflow-hidden">
                    {{-- File ini harus diletakkan di: public/images/umsura.png --}}
                    <img src="{{ asset('images/umsura.png') }}" alt="Logo UMSurabaya" class="w-50  h-50 object-contain">
                </div>
            </div>
            <br>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
