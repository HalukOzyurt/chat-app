import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';

/**
 * Register Component - Kullanıcı kayıt sayfası
 * 
 * Yeni kullanıcıların sisteme kaydolmasını sağlar
 */
export default function Register() {
    // Şifre gösterme durumları
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // Inertia form helper
    const { data, setData, post, processing, errors } = useForm({
        name: '',                   // Kullanıcı adı
        email: '',                  // E-posta
        password: '',               // Şifre
        password_confirmation: '',  // Şifre tekrarı
    });

    /**
     * Form gönderim işleyicisi
     */
    const handleSubmit = (e) => {
        e.preventDefault();
        post('/register');  // Laravel register route'una POST gönder
    };

    /**
     * Şifre gücü hesaplama
     * Şifrenin ne kadar güçlü olduğunu gösterir
     */
    const getPasswordStrength = () => {
        const password = data.password;
        if (!password) return { strength: 0, label: '', color: '' };

        let strength = 0;
        
        // Uzunluk kontrolü
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        
        // Büyük harf kontrolü
        if (/[A-Z]/.test(password)) strength += 15;
        
        // Küçük harf kontrolü
        if (/[a-z]/.test(password)) strength += 15;
        
        // Rakam kontrolü
        if (/\d/.test(password)) strength += 10;
        
        // Özel karakter kontrolü
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 10;

        // Güç seviyesini belirle
        if (strength < 40) return { strength, label: 'Zayıf', color: 'danger' };
        if (strength < 70) return { strength, label: 'Orta', color: 'warning' };
        return { strength, label: 'Güçlü', color: 'success' };
    };

    const passwordStrength = getPasswordStrength();

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
            <div className="container">
                <div className="row justify-content-center">
                    <div className="col-md-7 col-lg-6">
                        {/* Kayıt kartı */}
                        <div className="card shadow-lg border-0 rounded-4">
                            <div className="card-body p-5">
                                {/* Logo ve başlık */}
                                <div className="text-center mb-4">
                                    <div className="mb-3">
                                        <div className="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle" 
                                             style={{ width: '80px', height: '80px', fontSize: '2rem' }}>
                                            💬
                                        </div>
                                    </div>
                                    <h2 className="fw-bold text-dark">ChatConnect'e Katılın</h2>
                                    <p className="text-muted">Ücretsiz hesap oluşturun ve sohbete başlayın</p>
                                </div>

                                {/* Kayıt formu */}
                                <form onSubmit={handleSubmit}>
                                    {/* İsim input */}
                                    <div className="mb-3">
                                        <label htmlFor="name" className="form-label fw-semibold">
                                            Ad Soyad
                                        </label>
                                        <input
                                            id="name"
                                            type="text"
                                            className={`form-control form-control-lg ${errors.name ? 'is-invalid' : ''}`}
                                            placeholder="Ahmet Yılmaz"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            disabled={processing}
                                            autoFocus
                                        />
                                        {errors.name && (
                                            <div className="invalid-feedback">{errors.name}</div>
                                        )}
                                    </div>

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
                                            onChange={e => setData('email', e.target.value)}
                                            disabled={processing}
                                        />
                                        {errors.email && (
                                            <div className="invalid-feedback">{errors.email}</div>
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
                                                type={showPassword ? 'text' : 'password'}
                                                className={`form-control form-control-lg ${errors.password ? 'is-invalid' : ''}`}
                                                placeholder="••••••••"
                                                value={data.password}
                                                onChange={e => setData('password', e.target.value)}
                                                disabled={processing}
                                            />
                                            <button
                                                className="btn btn-outline-secondary"
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                disabled={processing}
                                            >
                                                {showPassword ? '👁️' : '👁️‍🗨️'}
                                            </button>
                                            {errors.password && (
                                                <div className="invalid-feedback">{errors.password}</div>
                                            )}
                                        </div>

                                        {/* Şifre gücü göstergesi */}
                                        {data.password && (
                                            <div className="mt-2">
                                                <div className="d-flex justify-content-between align-items-center mb-1">
                                                    <small className="text-muted">Şifre gücü:</small>
                                                    <small className={`text-${passwordStrength.color} fw-semibold`}>
                                                        {passwordStrength.label}
                                                    </small>
                                                </div>
                                                <div className="progress" style={{ height: '4px' }}>
                                                    <div 
                                                        className={`progress-bar bg-${passwordStrength.color}`}
                                                        style={{ width: `${passwordStrength.strength}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Şifre tekrar input */}
                                    <div className="mb-4">
                                        <label htmlFor="password_confirmation" className="form-label fw-semibold">
                                            Şifre Tekrarı
                                        </label>
                                        <div className="input-group">
                                            <input
                                                id="password_confirmation"
                                                type={showConfirmPassword ? 'text' : 'password'}
                                                className={`form-control form-control-lg ${errors.password ? 'is-invalid' : ''}`}
                                                placeholder="••••••••"
                                                value={data.password_confirmation}
                                                onChange={e => setData('password_confirmation', e.target.value)}
                                                disabled={processing}
                                            />
                                            <button
                                                className="btn btn-outline-secondary"
                                                type="button"
                                                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                                disabled={processing}
                                            >
                                                {showConfirmPassword ? '👁️' : '👁️‍🗨️'}
                                            </button>
                                        </div>
                                        
                                        {/* Şifre eşleşme kontrolü */}
                                        {data.password_confirmation && (
                                            <small className={data.password === data.password_confirmation ? 'text-success' : 'text-danger'}>
                                                {data.password === data.password_confirmation ? '✓ Şifreler eşleşiyor' : '✗ Şifreler eşleşmiyor'}
                                            </small>
                                        )}
                                    </div>

                                    {/* Kayıt butonu */}
                                    <button
                                        type="submit"
                                        className="btn btn-primary btn-lg w-100 mb-3"
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Kayıt oluşturuluyor...
                                            </>
                                        ) : (
                                            'Hesap Oluştur'
                                        )}
                                    </button>

                                    {/* Giriş yap linki */}
                                    <div className="text-center">
                                        <p className="text-muted mb-0">
                                            Zaten hesabınız var mı?{' '}
                                            <a href="/login" className="text-primary text-decoration-none fw-semibold">
                                                Giriş Yapın
                                            </a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="text-center mt-4">
                            <p className="text-muted small">
                                Kaydolarak <a href="#" className="text-decoration-none">Kullanım Şartları</a> ve{' '}
                                <a href="#" className="text-decoration-none">Gizlilik Politikası</a>'nı kabul etmiş olursunuz.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
