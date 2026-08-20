<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = $request->email;
        $otp = rand(100000, 999999);

        // Simpan OTP di cache selama 5 menit
        Cache::put('password_otp_' . $email, $otp, now()->addMinutes(5));

        // Kirim Notification
        $user = User::where('email', $email)->first();
        $user->notify(new ResetPasswordOtp($otp));

        return redirect()->route('password.otp.view', ['email' => $email])
            ->with('status', 'Kode OTP telah dikirim ke email Anda.');
    }

    /**
     * Tampilkan view input OTP
     */
    public function otpView(Request $request): View
    {
        $email = $request->email;
        if (!$email) {
            return view('auth.forgot-password');
        }
        return view('auth.verify-otp', compact('email'));
    }

    /**
     * Verifikasi kode OTP
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        $cachedOtp = Cache::get('password_otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau telah kadaluarsa.']);
        }

        // OTP Valid, simpan penanda di session bahwa user ini sudah verifikasi OTP
        session(['otp_verified_email' => $request->email]);
        
        // Hapus OTP dari cache setelah berhasil verifikasi
        Cache::forget('password_otp_' . $request->email);

        return redirect()->route('password.reset');
    }

    /**
     * Kirim ulang OTP
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->email;
        $otp = rand(100000, 999999);

        Cache::put('password_otp_' . $email, $otp, now()->addMinutes(5));

        $user = User::where('email', $email)->first();
        $user->notify(new ResetPasswordOtp($otp));

        return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }
}
