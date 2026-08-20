<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-xl font-bold text-gray-800">Verifikasi OTP</h2>
        <p class="text-xs text-gray-500 font-medium mt-2 leading-relaxed">
            Kode OTP telah dikirim ke <span class="text-blue-600 font-bold">{{ $email }}</span>.
            Silakan masukkan 6 digit kode tersebut.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.otp.verify') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <!-- OTP Code -->
        <div class="space-y-1.5">
            <label for="otp" class="text-[10px] font-black text-gray-700 uppercase tracking-widest ml-1">Kode OTP</label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <input id="otp" type="text" name="otp" required autofocus
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-4 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-black text-2xl tracking-[0.5em] text-center text-gray-900 transition-all placeholder:text-gray-300"
                    placeholder="000000" maxlength="6">
            </div>
            <x-input-error :messages="$errors->get('otp')" class="mt-1 ml-1" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 group">
                Verifikasi OTP
                <i class="fas fa-check-circle group-hover:scale-110 transition-transform text-[10px]"></i>
            </button>
        </div>
    </form>

    <div class="mt-8 text-center space-y-4">
        <form method="POST" action="{{ route('password.otp.resend') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Tidak menerima kode?</p>
            <button type="submit" class="mt-2 text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors inline-flex items-center gap-2">
                <i class="fas fa-sync-alt"></i>
                Kirim Ulang OTP
            </button>
        </form>

        <div class="pt-4 border-t border-gray-100">
            <a href="{{ route('password.request') }}" class="text-[10px] font-black text-gray-400 hover:text-gray-600 uppercase tracking-widest transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Ganti Email
            </a>
        </div>
    </div>
</x-guest-layout>
