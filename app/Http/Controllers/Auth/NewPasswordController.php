<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        // Pastikan user sudah verifikasi OTP
        if (!session('otp_verified_email')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Silakan verifikasi OTP terlebih dahulu.']);
        }

        return view('auth.reset-password', [
            'email' => session('otp_verified_email')
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = session('otp_verified_email');

        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Sesi reset password telah berakhir.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.request')->withErrors(['email' => 'User tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        // Hapus semua session milik user (force logout semua device)
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Hapus session setelah berhasil reset
        session()->forget('otp_verified_email');

        return redirect()->route('login')->with('success', 'Password Anda telah berhasil diperbarui.');
    }
}
