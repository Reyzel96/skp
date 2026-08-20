<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-xl font-bold text-gray-800">Lupa Password?</h2>
        <p class="text-xs text-gray-500 font-medium mt-2 leading-relaxed px-4">
            Masukkan alamat email yang terdaftar. Kami akan mengirimkan kode OTP untuk memverifikasi bahwa Anda adalah pemilik akun sebelum melakukan reset password.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label for="email" class="text-[10px] font-black text-gray-700 uppercase tracking-widest ml-1">Email Address</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-envelope"></i>
                </div>
                <input id="email" type="email" name="email" :value="old('email')" required autofocus
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-4 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-bold text-sm text-gray-900 transition-all placeholder:text-gray-400"
                    placeholder="nama@email.com">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 ml-1" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 group">
                Kirim Kode OTP
                <i class="fas fa-paper-plane group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform text-[10px]"></i>
            </button>
        </div>

        <div class="text-center pt-4">
            <a href="{{ route('login') }}" class="text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Login
            </a>
        </div>
    </form>
</x-guest-layout>
