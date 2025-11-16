<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Kullanıcının bu isteği yapma yetkisi var mı?
     * Register için herkes yapabilir, kimlik doğrulama gerekmez
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Doğrulama kuralları
     * Kayıt formu için gerekli alanları ve kuralları belirler
     */
    public function rules(): array
    {
        return [
            // İsim alanı
            'name' => [
                'required',           // Zorunlu alan
                'string',             // String olmalı
                'max:255',            // Maksimum 255 karakter
                'min:2',              // Minimum 2 karakter
            ],
            
            // E-posta alanı
            'email' => [
                'required',           // Zorunlu alan
                'string',             // String olmalı
                'email',              // Geçerli e-posta formatı olmalı
                'max:255',            // Maksimum 255 karakter
                'unique:users,email', // Users tablosunda benzersiz olmalı
            ],
            
            // Şifre alanı
            'password' => [
                'required',           // Zorunlu alan
                'confirmed',          // password_confirmation alanı ile eşleşmeli
                Password::min(8)      // Minimum 8 karakter
                    ->letters()       // En az bir harf içermeli
                    ->mixedCase()     // Hem büyük hem küçük harf içermeli
                    ->numbers()       // En az bir rakam içermeli
                    ->symbols(),      // En az bir özel karakter içermeli (!@#$%^ vb.)
            ],
        ];
    }

    /**
     * Özel hata mesajları
     * Türkçe kullanıcı dostu mesajlar
     */
    public function messages(): array
    {
        return [
            // İsim hataları
            'name.required' => 'İsim alanı zorunludur.',
            'name.min' => 'İsim en az :min karakter olmalıdır.',
            'name.max' => 'İsim en fazla :max karakter olabilir.',
            
            // E-posta hataları
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'email.max' => 'E-posta en fazla :max karakter olabilir.',
            
            // Şifre hataları
            'password.required' => 'Şifre alanı zorunludur.',
            'password.confirmed' => 'Şifreler eşleşmiyor.',
            'password.min' => 'Şifre en az :min karakter olmalıdır.',
        ];
    }

    /**
     * Alan adlarını özelleştir
     * Hata mesajlarında kullanılacak alan isimleri
     */
    public function attributes(): array
    {
        return [
            'name' => 'isim',
            'email' => 'e-posta',
            'password' => 'şifre',
        ];
    }
}
