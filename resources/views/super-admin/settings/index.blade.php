<x-app-layout>
    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border-t-4 border-blue-600">
                <div class="flex items-center mb-8">
                    <a href="{{ route('superadmin.dashboard') }}"
                       class="inline-flex items-center text-blue-600 hover:text-blue-800 font-bold transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali ke Dashboard
                    </a>
                </div>

                <h1 class="text-3xl font-black text-gray-900 mb-2">Pengaturan Sistem</h1>
                <p class="text-gray-600 mb-8 font-medium">Kelola visibilitas menu dan fitur yang ditampilkan di sidebar pengguna.</p>

                @if(session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-5 mb-8 shadow-sm">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm font-bold text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-5 mb-8 shadow-sm">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-sm font-bold text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    {{-- Toggle: SKP Dosen Kuantitatif --}}
                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 flex items-center justify-between">
                        <div class="flex-1 min-w-0 pr-6">
                            <h3 class="text-base font-bold text-gray-900">Menu "SKP Kuantitatif"</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Tampilkan menu ini di sidebar untuk role Dosen, Pimpinan Fakultas, Kaprodi, Sekprodi, dan Kepala Lab.
                                Menu ini tetap tampil untuk Super Admin terlepas dari pengaturan ini.
                            </p>
                        </div>
                        {{-- data-turbo="false": submit diarahkan sebagai full page reload agar sidebar
                             (data-turbo-permanent) ikut ter-render ulang dengan setting terbaru.
                             x-data guard: cegah double-submit cepat yang membalik nilai dua kali. --}}
                        <form action="{{ route('superadmin.settings.toggle') }}" method="POST" data-turbo="false" class="shrink-0"
                              x-data="{ submitted: false }" x-on:submit="if (submitted) $event.preventDefault(); submitted = true">
                            @csrf
                            <input type="hidden" name="key" value="show_skp_dosen_kuantitatif">
                            <button type="submit"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#16BC2E] focus:ring-offset-2 {{ $showSkpDosenKuantitatif ? 'bg-[#16BC2E]' : 'bg-gray-300' }}">
                                <span class="sr-only">Toggle menu SKP Kuantitatif</span>
                                <span class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out {{ $showSkpDosenKuantitatif ? 'translate-x-6' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
