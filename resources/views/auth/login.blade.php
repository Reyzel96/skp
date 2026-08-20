<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-lg font-bold text-gray-800">Selamat Datang Kembali</h2>
        <p class="text-xs text-gray-500 font-medium">Silakan login untuk mengakses akun Anda</p>
    </div>

    <!-- Session Status (default Laravel) -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- 🔴 Tambahan: Notifikasi Error dari with('error') -->
    @if(session('error'))
        <div class="p-4 mb-6 text-xs text-red-800 rounded-2xl bg-red-50 border border-red-100 flex items-center gap-3 animate-pulse" role="alert">
            <i class="fas fa-exclamation-circle text-base"></i>
            <span class="font-bold">Peringatan!</span> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label for="email" class="text-[10px] font-black text-gray-700 uppercase tracking-widest ml-1">Email Address</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-envelope"></i>
                </div>
                <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-4 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-bold text-sm text-gray-900 transition-all placeholder:text-gray-400"
                    placeholder="nama@email.com">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 ml-1" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex justify-between items-center px-1">
                <label for="password" class="text-[10px] font-black text-gray-700 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors" href="{{ route('password.request') }}">
                        Lupa?
                    </a>
                @endif
            </div>
            <div class="relative group" x-data="{ showPassword: false }">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-lock"></i>
                </div>
                <input id="password" type="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    @blur="showPassword = false"
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-12 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-bold text-sm text-gray-900 transition-all placeholder:text-gray-400"
                    placeholder="••••••••">
                <button type="button" @mousedown.prevent @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 ml-1" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between px-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" name="remember" 
                    class="rounded-lg border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-5 h-5 transition-all cursor-pointer">
                <span class="ms-3 text-[11px] font-bold text-gray-600 group-hover:text-gray-900 transition-colors uppercase tracking-wider">Ingat Saya</span>
            </label>
        </div>

        <div>
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 group">
                Masuk ke Akun
                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </div>
    </form>

    <script>
        // 1. Mencegah tampilan usang dari cache browser (Back-Forward Cache)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // 2. Auto-refresh halaman login secara berkala (sebelum session CSRF habis)
        // Agar jika user meninggalkan tab di halaman login berjam-jam, token CSRF tetap valid.
        let csrfLifetime = {{ config('session.lifetime', 120) * 60 * 1000 }};
        setTimeout(function() {
            window.location.reload();
        }, Math.max(csrfLifetime - 60000, 60000)); // Refresh 1 menit sebelum expired
    </script>
</x-guest-layout>
