<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'prevent-back' => \App\Http\Middleware\PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 1. HANDLE 403 (Unauthorized - Spatie)
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, Request $request) {
            if (Auth::check()) {
                $userId = Auth::id();
                Auth::logout();

                Cache::forget('user-online-' . $userId);

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with(
                    'error',
                    'Pelanggaran Akses! Anda dikeluarkan secara otomatis karena mencoba mengakses menu yang bukan wewenang Anda.'
                );
            }
            return redirect()->route('login');
        });

        // 2. HANDLE 419 & TokenMismatchException secara global
        // Menangkap exception spesifik TokenMismatch
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()->route('login')->with(
                'error',
                'Sesi Anda telah berakhir. Silakan login kembali untuk memperbarui sesi.'
            );
        });

        // Menangkap response 419 apa pun (Fallback paling kuat)
        $exceptions->respond(function ($response) {
            if ($response->getStatusCode() === 419) {
                return redirect()->route('login')->with(
                    'error',
                    'Sesi login telah kadaluarsa. Silakan masuk kembali.'
                );
            }
            return $response;
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('periode:auto-close')->daily();
    })
    ->create();
