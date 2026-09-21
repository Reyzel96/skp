<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $lifetime = (int) config('session.lifetime', 120) * 60;

        $lock = Cache::lock('login-lock-' . $userId, 5);

        try {
            $lock->block(3);

            // Buang sesi expired milik user sebelum menghitung
            DB::table('sessions')
                ->where('user_id', $userId)
                ->where('last_activity', '<', now()->getTimestamp() - $lifetime)
                ->delete();

            // Sesi aktif milik user ini, di luar sesi yang sedang aktif pada request ini
            $activeCount = DB::table('sessions')
                ->where('user_id', $userId)
                ->where('id', '!=', $request->session()->getId())
                ->count();

            $ejected = 0;
            if ($activeCount >= 3) {
                $ejected = $activeCount - 2;

                $currentId = $request->session()->getId();
                $ejectIds = DB::table('sessions')
                    ->where('user_id', $userId)
                    ->where('id', '!=', $currentId)
                    ->orderBy('last_activity', 'asc')
                    ->limit($ejected)
                    ->pluck('id');

                DB::table('sessions')->whereIn('id', $ejectIds)->delete();
            }
        } catch (LockTimeoutException) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with(
                'error',
                'Server sedang sibuk, coba lagi'
            );
        } finally {
            $lock->release();
        }

        Log::info('login.success', [
            'user_id' => $userId,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'ejected_sessions' => $ejected,
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
