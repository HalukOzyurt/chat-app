<?php

namespace Tests\Feature\Auth;

use App\Events\UserOnline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Login sayfası başarıyla gösterilir
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    /**
     * Geçerli kimlik bilgileriyle giriş başarılı olur
     */
    public function test_users_can_login_with_valid_credentials(): void
    {
        // Kullanıcı oluştur
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Event'leri dinle
        Event::fake();

        // Giriş yap
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Giriş başarılı mı kontrol et
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('chat.index'));
        $response->assertSessionHas('success');

        // Kullanıcı online olarak işaretlendi mi?
        $user->refresh();
        $this->assertTrue($user->is_online);
        $this->assertNotNull($user->last_seen_at);

        // UserOnline event'i broadcast edildi mi?
        Event::assertDispatched(UserOnline::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Geçersiz şifre ile giriş başarısız olur
     */
    public function test_users_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Geçersiz e-posta ile giriş başarısız olur
     */
    public function test_users_cannot_login_with_invalid_email(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * E-posta alanı zorunludur
     */
    public function test_email_is_required(): void
    {
        $response = $this->post(route('login'), [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Şifre alanı zorunludur
     */
    public function test_password_is_required(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * E-posta geçerli formatta olmalıdır
     */
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'invalid-email-format',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * "Beni hatırla" özelliği çalışır
     */
    public function test_remember_me_functionality_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('chat.index'));
        $this->assertAuthenticatedAs($user);

        // Remember token oluşturuldu mu?
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    /**
     * Rate limiting - Çok fazla başarısız giriş denemesi engellenir
     */
    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 5 kere yanlış şifre ile dene
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6. denemede rate limiting devreye girmeli
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('saniye', session('errors')->first('email'));
    }

    /**
     * Başarılı giriş sonrası rate limiting sıfırlanır
     */
    public function test_rate_limiting_is_cleared_after_successful_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 3 kere yanlış şifre ile dene
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Doğru şifre ile giriş yap
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('chat.index'));
        $this->assertAuthenticatedAs($user);

        // Çıkış yap
        Auth::logout();

        // Tekrar yanlış şifre ile denediğimizde rate limiting sıfırlanmış olmalı
        // (yani hemen engellenmemeliyiz)
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Bu sefer sadece geçersiz kimlik hatası almalıyız, rate limiting hatası değil
        $this->assertStringNotContainsString('saniye', session('errors')->first('email'));
    }

    /**
     * Session güvenliği - Session regeneration
     */
    public function test_session_is_regenerated_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Session başlat
        $this->session(['test_key' => 'test_value']);
        $oldSessionId = session()->getId();

        // Giriş yap
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Session ID değişmiş olmalı (session fixation saldırılarına karşı)
        $newSessionId = session()->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }
}
