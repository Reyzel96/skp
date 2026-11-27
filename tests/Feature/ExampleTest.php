<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root URL mengarahkan tamu (belum login) ke halaman login.
     *
     * Catatan: sebelumnya skeleton bawaan mengharapkan 200, padahal
     * routes/web.php melakukan Route::redirect('/', '/login') — 302
     * adalah perilaku benar aplikasi untuk tamu.
     */
    public function test_root_mengarahkan_tamu_ke_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
