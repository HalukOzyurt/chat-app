<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kayıt sayfası başarıyla gösterilir
     */
    public function test_register_page_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    /**
     * Geçerli verilerle kayıt başarılı olur
     */
    public function test_users_can_register_with_valid_data(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Kullanıcı oluşturuldu mu?
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Kullanıcı otomatik giriş yaptı mı?
        $user = User::where('email', 'test@example.com')->first();
        $this->assertAuthenticatedAs($user);

        // Chat sayfasına yönlendirildi mi?
        $response->assertRedirect(route('chat.index'));
        $response->assertSessionHas('success');

        // Kullanıcı online olarak işaretlendi mi?
        $this->assertTrue($user->is_online);
        $this->assertNotNull($user->last_seen_at);
    }

    /**
     * Şifre hash'lenerek kaydedilir
     */
    public function test_password_is_hashed_when_stored(): void
    {
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        // Şifre düz metin olarak saklanmamalı
        $this->assertNotEquals('Password123!', $user->password);

        // Şifre doğru hash'lenmiş mi?
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    /**
     * İsim alanı zorunludur
     */
    public function test_name_is_required(): void
    {
        $response = $this->post(route('register'), [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * İsim minimum 2 karakter olmalıdır
     */
    public function test_name_must_be_at_least_2_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'A',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * İsim maksimum 255 karakter olabilir
     */
    public function test_name_cannot_exceed_255_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * E-posta alanı zorunludur
     */
    public function test_email_is_required(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * E-posta geçerli formatta olmalıdır
     */
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'invalid-email-format',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * E-posta benzersiz olmalıdır
     */
    public function test_email_must_be_unique(): void
    {
        // İlk kullanıcıyı oluştur
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Aynı e-posta ile kayıt olmaya çalış
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Sadece ilk kullanıcı var olmalı
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Şifre alanı zorunludur
     */
    public function test_password_is_required(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Şifre minimum 8 karakter olmalıdır
     */
    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Pass1!',
            'password_confirmation' => 'Pass1!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Şifre hem büyük hem küçük harf içermelidir
     */
    public function test_password_must_contain_mixed_case(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Şifre en az bir rakam içermelidir
     */
    public function test_password_must_contain_numbers(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password!',
            'password_confirmation' => 'Password!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Şifre en az bir özel karakter içermelidir
     */
    public function test_password_must_contain_symbols(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Şifre onayı eşleşmelidir
     */
    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Geçerli bir şifre örneği kabul edilir
     */
    public function test_valid_password_formats_are_accepted(): void
    {
        $validPasswords = [
            'Password123!',
            'MyP@ssw0rd',
            'Secure#Pass1',
            'Test@1234',
        ];

        foreach ($validPasswords as $password) {
            $response = $this->post(route('register'), [
                'name' => 'Test User',
                'email' => 'test' . uniqid() . '@example.com', // Her test için farklı email
                'password' => $password,
                'password_confirmation' => $password,
            ]);

            $response->assertRedirect(route('chat.index'));
        }

        // 4 kullanıcı oluşturulmuş olmalı
        $this->assertDatabaseCount('users', 4);
    }

    /**
     * Kayıt sonrası kullanıcı otomatik giriş yapar
     */
    public function test_user_is_automatically_logged_in_after_registration(): void
    {
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertAuthenticatedAs($user);
    }
}
