<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // ✅ Tambahkan ini
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $userId = Auth::id();

        // 🔴 BLOKIR JIKA SEDANG ONLINE DI DEVICE LAIN
        if (Cache::has('user-online-' . $userId)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'error',
                'Akses Ditolak! Akun Anda sedang digunakan di perangkat/browser lain.'
            );
        }

        // ✅ SET CACHE: Hanya 2 Menit (Akan diperpanjang otomatis oleh Heartbeat)
        Cache::put('user-online-' . $userId, true, now()->addMinutes(2));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ HAPUS CACHE SAAT LOGOUT MANUAL
        if ($userId) {
            Cache::forget('user-online-' . $userId);
        }

        return redirect('/');
    }
}
