<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Kullanıcının bu isteği yapma yetkisi var mı?
     * Login için herkes yapabilir
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Doğrulama kuralları
     */
    public function rules(): array
    {
        return [
            // E-posta doğrulaması
            'email' => [
                'required',     // Zorunlu
                'string',       // String olmalı
                'email',        // E-posta formatında olmalı
            ],
            
            // Şifre doğrulaması
            'password' => [
                'required',     // Zorunlu
                'string',       // String olmalı
            ],
            
            // Beni hatırla (opsiyonel)
            'remember' => [
                'boolean',      // True veya false olmalı
            ],
        ];
    }

    /**
     * Kimlik doğrulama işlemi
     * E-posta ve şifre ile giriş yapmayı dene
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        // Giriş denemelerini sınırla (brute force saldırılarına karşı)
        $this->ensureIsNotRateLimited();

        // Laravel'in Auth sistemi ile giriş yap
        if (!Auth::attempt(
            $this->only('email', 'password'),  // Sadece email ve password kullan
            $this->boolean('remember')          // "Beni hatırla" seçeneği
        )) {
            // Giriş başarısız - rate limiting sayacını artır
            $this->incrementLoginAttempts();

            // Hata fırlat
            throw ValidationException::withMessages([
                'email' => 'Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.',
            ]);
        }

        // Giriş başarılı - rate limiting sayacını sıfırla
        $this->clearLoginAttempts();
    }

    /**
     * Rate limiting kontrolü
     * Çok fazla başarısız giriş denemesi yapılmış mı?
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        // 5 dakika içinde 5'ten fazla başarısız deneme yapılmış mı?
        if ($this->hasTooManyLoginAttempts()) {
            // Kaç saniye sonra tekrar deneyebilir?
            $seconds = $this->limiter()->availableIn(
                $this->throttleKey()
            );

            throw ValidationException::withMessages([
                'email' => "Çok fazla başarısız giriş denemesi. Lütfen {$seconds} saniye sonra tekrar deneyin.",
            ]);
        }
    }

    /**
     * Başarısız giriş denemesi sayısını artır
     */
    protected function incrementLoginAttempts(): void
    {
        $this->limiter()->hit(
            $this->throttleKey(),
            $seconds = 300  // 5 dakika = 300 saniye
        );
    }

    /**
     * Başarılı girişten sonra deneme sayacını sıfırla
     */
    protected function clearLoginAttempts(): void
    {
        $this->limiter()->clear($this->throttleKey());
    }

    /**
     * Çok fazla deneme yapılmış mı kontrol et
     */
    protected function hasTooManyLoginAttempts(): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey(),
            $maxAttempts = 5  // Maksimum 5 deneme
        );
    }

    /**
     * Rate limiting için benzersiz anahtar oluştur
     * E-posta + IP adresi kombinasyonu
     */
    protected function throttleKey(): string
    {
        return strtolower($this->input('email')) . '|' . $this->ip();
    }

    /**
     * Rate limiter instance'ını getir
     */
    protected function limiter()
    {
        return app('Illuminate\Cache\RateLimiter');
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'password.required' => 'Şifre alanı zorunludur.',
        ];
    }
}
