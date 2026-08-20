<x-guest-layout>
    <div class="mb-8 text-center">
        <h2 class="text-xl font-bold text-gray-800">Reset Password</h2>
        <p class="text-xs text-gray-500 font-medium mt-2 leading-relaxed">
            Akun terverifikasi: <span class="text-blue-600 font-bold">{{ $email }}</span>.
            Silakan masukkan password baru Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
        @csrf

        <!-- Password -->
        <div class="space-y-1.5">
            <label for="password" class="text-[10px] font-black text-gray-700 uppercase tracking-widest ml-1">Password Baru</label>
            <div class="relative group" x-data="{ showPassword: false }">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-key"></i>
                </div>
                <input id="password" type="password" :type="showPassword ? 'text' : 'password'" name="password" required autofocus autocomplete="new-password"
                    @blur="showPassword = false"
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-12 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-bold text-sm text-gray-900 transition-all placeholder:text-gray-400"
                    placeholder="••••••••">
                <button type="button" @mousedown.prevent @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 ml-1" />
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label for="password_confirmation" class="text-[10px] font-black text-gray-700 uppercase tracking-widest ml-1">Konfirmasi Password Baru</label>
            <div class="relative group" x-data="{ showPassword: false }">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                    <i class="fas fa-check-double"></i>
                </div>
                <input id="password_confirmation" type="password" :type="showPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                    @blur="showPassword = false"
                    class="w-full bg-gray-50 border-gray-100 rounded-2xl py-4 pl-12 pr-12 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 font-bold text-sm text-gray-900 transition-all placeholder:text-gray-400"
                    placeholder="••••••••">
                <button type="button" @mousedown.prevent @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 ml-1" />
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 group">
                Update Password
                <i class="fas fa-save group-hover:scale-110 transition-transform text-[10px]"></i>
            </button>
        </div>
    </form>
</x-guest-layout>
