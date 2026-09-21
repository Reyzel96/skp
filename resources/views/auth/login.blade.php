<x-guest-layout>
    <div class="text-center animate-fade-in-up" style="animation-delay: 0.6s;">
        <h1 class="text-[25px] sm:text-[29px] leading-tight font-bold text-[#111827] tracking-tight">Selamat Datang Kembali</h1>
        <p class="mt-1.5 sm:mt-2 text-[13.5px] sm:text-[15px] text-slate-400 font-medium">Silakan login untuk mengakses akun Anda</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mt-6" :status="session('status')" />

    <!-- Notifikasi Error -->
    @if(session('error'))
        <div class="mt-6 p-3.5 text-xs text-red-800 rounded-2xl bg-red-50 border border-red-100 flex items-center gap-3 animate-fade-in-up" style="animation-delay: 0.7s;" role="alert">
            <i class="fas fa-exclamation-circle text-base shrink-0"></i>
            <span class="font-bold">Peringatan!</span> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-[22px] animate-fade-in-up" style="animation-delay: 0.8s;" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <!-- Email -->
        <div class="space-y-1.5">
            <label for="email" class="block text-[12px] font-semibold text-slate-500 uppercase tracking-[0.12em] ml-1">Email</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-[18px] flex items-center pointer-events-none text-slate-400 group-focus-within:text-brand-lime transition-colors duration-200">
                    <i class="fas fa-envelope text-[14px]"></i>
                </div>
                <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                    class="w-full h-[58px] bg-[#F8FAFC] border border-slate-200 rounded-2xl pl-11 pr-4 focus:bg-white focus:ring-4 focus:ring-brand-lime/10 focus:border-brand-lime focus:outline-none font-semibold text-[14px] text-slate-900 transition-all duration-200 placeholder:text-slate-400/90 placeholder:font-normal placeholder:text-[13.5px]"
                    placeholder="nama@email.com">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 ml-1" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex justify-between items-center ml-1 mr-1">
                <label for="password" class="text-[12px] font-semibold text-slate-500 uppercase tracking-[0.12em]">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-[12.5px] font-bold text-brand-green-dark hover:text-brand-lime tracking-wide transition-colors" href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>
            <div class="relative group" x-data="{ showPassword: false }">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-brand-lime transition-colors duration-200">
                    <i class="fas fa-lock text-[14px]"></i>
                </div>
                <input id="password" type="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    @blur="showPassword = false"
                    class="w-full h-[58px] bg-[#F8FAFC] border border-slate-200 rounded-2xl pl-11 pr-11 focus:bg-white focus:ring-4 focus:ring-brand-lime/10 focus:border-brand-lime focus:outline-none font-semibold text-[14px] text-slate-900 transition-all duration-200 placeholder:text-slate-400/90 placeholder:font-normal placeholder:text-[13.5px]"
                    placeholder="Masukkan password">
                <button type="button" @mousedown.prevent @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-5 flex items-center text-slate-400 hover:text-brand-lime focus:outline-none transition-colors duration-200" aria-label="Tampilkan password">
                    <i class="fas text-[14px]" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 ml-1" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex items-center cursor-pointer group ml-1">
            <input id="remember_me" type="checkbox" name="remember"
                class="rounded-[6px] border-slate-300 text-brand-lime focus:ring-brand-lime/40 focus:ring-offset-0 w-[18px] h-[18px] transition-all cursor-pointer">
            <span class="ms-2.5 text-[13px] sm:text-[14px] font-medium text-slate-500 group-hover:text-slate-800 transition-colors">Ingat saya di perangkat ini</span>
        </label>

        <!-- Submit -->
        <button type="submit" class="relative w-full h-[59px] rounded-[18px] font-bold text-[14px] uppercase tracking-[0.15em] text-white bg-gradient-to-r from-[#119675] to-[#2DBE68] shadow-lg shadow-[#119675]/30 transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#119675]/40 active:translate-y-0 active:scale-[0.99] flex items-center justify-center gap-3 group overflow-hidden disabled:opacity-70 disabled:cursor-not-allowed disabled:translate-y-0 disabled:active:scale-100" :disabled="loading">
            <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/15 to-transparent pointer-events-none"></span>
            <span x-show="!loading" class="flex items-center gap-3">
                Masuk ke Akun
                <i class="fas fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform duration-200"></i>
            </span>
            <span x-show="loading" class="flex items-center gap-3">
                <i class="fas fa-circle-notch fa-spin"></i>
                Memproses...
            </span>
        </button>
    </form>

    <!-- Footer Card -->
    <div class="mt-5 pt-4 border-t border-slate-100 relative animate-fade-in-up" style="animation-delay: 0.9s;">
        <div class="flex items-center justify-between">
            <span class="text-[12.5px] italic font-medium text-slate-400">Berilmu • Berakhlak • Berkemajuan</span>
            <span class="w-[58px] h-[2.5px] rounded-full bg-gradient-to-r from-brand-lime to-brand-yellow-green"></span>
        </div>
    </div>

    <script>
        // 1. Mencegah tampilan usang dari cache browser (Back-Forward Cache)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // 2. Auto-refresh halaman login secara berkala (sebelum session CSRF habis)
        let csrfLifetime = {{ config('session.lifetime', 120) * 60 * 1000 }};
        setTimeout(function() {
            window.location.reload();
        }, Math.max(csrfLifetime - 60000, 60000)); // Refresh 1 menit sebelum expired
    </script>
</x-guest-layout>
