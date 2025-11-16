import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';

/**
 * Login Component - Kullanıcı giriş sayfası
 * 
 * Bu component kullanıcıların sisteme giriş yapmasını sağlar
 * Inertia.js form helper'ını kullanarak Laravel backend ile iletişim kurar
 */
export default function Login() {
    // Şifre göster/gizle durumu
    const [showPassword, setShowPassword] = useState(false);

    // Inertia form helper - Laravel'e form gönderimi için
    // data: Form verileri
    // setData: Form verilerini güncelleme
    // post: POST isteği gönderme
    // processing: İstek işleniyor mu?
    // errors: Validasyon hataları
    const { data, setData, post, processing, errors } = useForm({
        email: '',        // E-posta alanı
        password: '',     // Şifre alanı
        remember: false,  // Beni hatırla seçeneği
    });

    /**
     * Form gönderim işleyicisi
     * Prevent default ile sayfanın yenilenmesini engeller
     * Laravel'e POST isteği gönderir
     */
    const handleSubmit = (e) => {
        e.preventDefault();  // Sayfa yenilemeyi engelle
        post('/login');      // Laravel login route'una POST isteği gönder
    };

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light">
            {/* Ana container - Ortalanmış kart */}
            <div className="container">
                <div className="row justify-content-center">
                    <div className="col-md-6 col-lg-5">
                        {/* Giriş kartı */}
                        <div className="card shadow-lg border-0 rounded-4">
                            <div className="card-body p-5">
                                {/* Logo ve başlık */}
                                <div className="text-center mb-4">
                                    <div className="mb-3">
                                        {/* Logo placeholder - İkon olarak chat balonu */}
                                        <div className="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle" 
                                             style={{ width: '80px', height: '80px', fontSize: '2rem' }}>
                                            💬
                                        </div>
                                    </div>
                                    <h2 className="fw-bold text-dark">ChatConnect</h2>
                                    <p className="text-muted">Hesabınıza giriş yapın</p>
                                </div>

                                {/* Giriş formu */}
                                <form onSubmit={handleSubmit}>
                                    {/* E-posta input */}
                                    <div className="mb-3">
                                        <label htmlFor="email" className="form-label fw-semibold">
                                            E-posta Adresi
                                        </label>
                                        <input
                                            id="email"
                                            type="email"
                                            className={`form-control form-control-lg ${errors.email ? 'is-invalid' : ''}`}
                                            placeholder="ornek@email.com"
                                            value={data.email}
                                            onChange={e => setData('email', e.target.value)}  // Değer değiştiğinde state'i güncelle
                                            disabled={processing}  // İstek gönderilirken disable et
                                            autoFocus  // Sayfa açıldığında otomatik focus
                                        />
                                        {/* Validasyon hatası gösterimi */}
                                        {errors.email && (
                                            <div className="invalid-feedback">
                                                {errors.email}
                                            </div>
                                        )}
                                    </div>

                                    {/* Şifre input */}
                                    <div className="mb-3">
                                        <label htmlFor="password" className="form-label fw-semibold">
                                            Şifre
                                        </label>
                                        <div className="input-group">
                                            <input
                                                id="password"
                                                type={showPassword ? 'text' : 'password'}  // Şifre göster/gizle
                                                className={`form-control form-control-lg ${errors.password ? 'is-invalid' : ''}`}
                                                placeholder="••••••••"
                                                value={data.password}
                                                onChange={e => setData('password', e.target.value)}
                                                disabled={processing}
                                            />
                                            {/* Şifre göster/gizle butonu */}
                                            <button
                                                className="btn btn-outline-secondary"
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                disabled={processing}
                                            >
                                                {showPassword ? '👁️' : '👁️‍🗨️'}
                                            </button>
                                            {errors.password && (
                                                <div className="invalid-feedback">
                                                    {errors.password}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Beni hatırla checkbox */}
                                    <div className="mb-4 form-check">
                                        <input
                                            id="remember"
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={data.remember}
                                            onChange={e => setData('remember', e.target.checked)}
                                            disabled={processing}
                                        />
                                        <label htmlFor="remember" className="form-check-label text-muted">
                                            Beni hatırla
                                        </label>
                                    </div>

                                    {/* Giriş butonu */}
                                    <button
                                        type="submit"
                                        className="btn btn-primary btn-lg w-100 mb-3"
                                        disabled={processing}  // İstek gönderilirken disable
                                    >
                                        {processing ? (
                                            <>
                                                {/* Yükleniyor spinner */}
                                                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Giriş yapılıyor...
                                            </>
                                        ) : (
                                            'Giriş Yap'
                                        )}
                                    </button>

                                    {/* Kayıt ol linki */}
                                    <div className="text-center">
                                        <p className="text-muted mb-0">
                                            Hesabınız yok mu?{' '}
                                            <a href="/register" className="text-primary text-decoration-none fw-semibold">
                                                Kayıt Olun
                                            </a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {/* Footer bilgi */}
                        <div className="text-center mt-4">
                            <p className="text-muted small">
                                © 2024 ChatConnect. Tüm hakları saklıdır.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
