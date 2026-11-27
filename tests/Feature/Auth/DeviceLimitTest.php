<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeviceLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * phpunit.xml memakai SESSION_DRIVER=array sehingga sesi tidak pernah
     * tersimpan di tabel sessions. Uji device-limit butuh driver database.
     *
     * PENTING: override env dilakukan di setUp(), BUKAN di constructor.
     * PHPUnit meng-instantiate semua class test saat discovery, jadi
     * putenv() di constructor mencemari environment proses untuk SEMUA
     * test lain (mis. ExampleTest ikut memakai driver database tanpa
     * tabel sessions -> "no such table: sessions").
     */
    protected function setUp(): void
    {
        putenv('SESSION_DRIVER=database');
        $_ENV['SESSION_DRIVER'] = 'database';
        $_SERVER['SESSION_DRIVER'] = 'database';

        parent::setUp();
    }

    /**
     * putenv() mencemari environment level proses dan bertahan setelah
     * test ini selesai; kembalikan ke nilai phpunit.xml (array).
     */
    protected function tearDown(): void
    {
        putenv('SESSION_DRIVER=array');
        $_ENV['SESSION_DRIVER'] = 'array';
        $_SERVER['SESSION_DRIVER'] = 'array';

        parent::tearDown();
    }

    /**
     * UserFactory memasukkan kolom "name" yang tidak ada di tabel users,
     * jadi test ini membuat user langsung via DB.
     */
    private function makeUser(string $email = 'user@example.test', string $password = 'password', ?string $rememberToken = null): int
    {
        return DB::table('users')->insertGetId([
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'remember_token' => $rememberToken,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSession(string $id, int $userId, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(''),
            'last_activity' => $lastActivity,
        ]);
    }

    public function test_new_login_deletes_oldest_session_when_limit_exceeded(): void
    {
        $userId = $this->makeUser();

        // 3 sesi aktif lain; login baru = aktif ke-4, eject yang terlama
        $this->insertSession('old-session-0', $userId, now()->getTimestamp() - 900);
        $this->insertSession('old-session-1', $userId, now()->getTimestamp() - 600);
        $this->insertSession('old-session-2', $userId, now()->getTimestamp() - 300);

        $this->post('/login', [
            'email' => 'user@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // Tersisa: 2 sesi tersisa + session login baru
        $this->assertSame(3, DB::table('sessions')->where('user_id', $userId)->count());
        $this->assertFalse(
            DB::table('sessions')->where('id', 'old-session-0')->exists(),
            'Sesi terlama seharusnya dihapus'
        );
        $this->assertTrue(DB::table('sessions')->where('id', 'old-session-2')->exists());
    }

    public function test_login_allowed_when_under_limit(): void
    {
        $userId = $this->makeUser();

        $this->insertSession('kept-session-0', $userId, now()->getTimestamp() - 600);
        $this->insertSession('kept-session-1', $userId, now()->getTimestamp() - 300);

        $this->post('/login', [
            'email' => 'user@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(DB::table('sessions')->where('id', 'kept-session-0')->exists());
        $this->assertTrue(DB::table('sessions')->where('id', 'kept-session-1')->exists());
    }

    public function test_expired_sessions_cleaned_before_counting(): void
    {
        $userId = $this->makeUser();
        $lifetime = (int) config('session.lifetime', 120) * 60;

        // Sesi expired (melewati session.lifetime) tidak dihitung
        $this->insertSession('expired-session-1', $userId, now()->getTimestamp() - $lifetime - 3601);
        $this->insertSession('expired-session-2', $userId, now()->getTimestamp() - $lifetime - 3602);

        $this->insertSession('active-session-1', $userId, now()->getTimestamp() - 120);
        $this->insertSession('active-session-2', $userId, now()->getTimestamp() - 60);

        $this->post('/login', [
            'email' => 'user@example.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertFalse(DB::table('sessions')->where('id', 'expired-session-1')->exists());
        $this->assertFalse(DB::table('sessions')->where('id', 'expired-session-2')->exists());
        // Aktif hanya 2 lainnya + login baru = 3, tidak ada yang di-eject
        $this->assertTrue(DB::table('sessions')->where('id', 'active-session-1')->exists());
        $this->assertTrue(DB::table('sessions')->where('id', 'active-session-2')->exists());
    }

    public function test_password_update_keeps_current_session_deletes_others(): void
    {
        $userId = $this->makeUser('update@example.test', 'old-password');
        $user = User::query()->findOrFail($userId);

        $this->insertSession('other-device-1', $userId, now()->getTimestamp() - 30);
        $this->insertSession('other-device-2', $userId, now()->getTimestamp() - 60);
        $this->insertSession('other-device-3', $userId, now()->getTimestamp() - 90);

        $response = $this->actingAs($user)
            ->put('/password', [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response->assertSessionHasNoErrors();

        // Sesi tersisip manual hilang; tersisa tepat 1 = session milik request ini
        $this->assertFalse(DB::table('sessions')->where('id', 'other-device-1')->exists());
        $this->assertFalse(DB::table('sessions')->where('id', 'other-device-2')->exists());
        $this->assertFalse(DB::table('sessions')->where('id', 'other-device-3')->exists());
        $this->assertSame(
            1,
            DB::table('sessions')->where('user_id', $userId)->count(),
            'Session saat ini seharusnya dipertahankan'
        );
    }

    public function test_password_reset_deletes_all_user_sessions(): void
    {
        $userId = $this->makeUser('reset@example.test');
        $user = User::query()->findOrFail($userId);

        $this->insertSession('reset-target-1', $userId, now()->getTimestamp());
        $this->insertSession('reset-target-2', $userId, now()->getTimestamp() - 100);

        $response = $this->withSession([
            'otp_verified_email' => $user->email,
        ])->post('/reset-password', [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse(DB::table('sessions')->where('id', 'reset-target-1')->exists());
        $this->assertFalse(DB::table('sessions')->where('id', 'reset-target-2')->exists());
        // Session yang dibuat oleh request ini milik tamu (user_id null), bukan milik user
        $this->assertSame(
            0,
            DB::table('sessions')->where('user_id', $userId)->count()
        );
    }

    public function test_remember_me_is_always_false(): void
    {
        $this->makeUser('remember@example.test', 'password', 'seeded-token');

        $this->post('/login', [
            'email' => 'remember@example.test',
            'password' => 'password',
            'remember' => 'on',
        ]);

        $this->assertAuthenticated();

        $token = DB::table('users')->where('email', 'remember@example.test')->value('remember_token');
        $this->assertSame(
            'seeded-token',
            $token,
            'remember_token seharusnya tidak berubah karena remember dipaksakan false'
        );
    }

    public function test_login_page_has_no_remember_checkbox(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('name="remember"', false);
        $response->assertDontSee('Ingat saya di perangkat ini', false);
    }
}
