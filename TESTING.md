# Test Dokümantasyonu

Bu döküman, ChatConnect uygulaması için yazılan testlerin nasıl çalıştırılacağını ve test altyapısını açıklar.

## 📋 İçindekiler

- [Test Altyapısı](#test-altyapısı)
- [Backend Testleri (PHPUnit)](#backend-testleri-phpunit)
- [Frontend Testleri (Vitest)](#frontend-testleri-vitest)
- [Test Kapsamı](#test-kapsamı)
- [Kurulum](#kurulum)

---

## 🏗️ Test Altyapısı

### Backend (PHP/Laravel)
- **Framework**: PHPUnit
- **Veritabanı**: SQLite (in-memory) - Test ortamı için
- **Yapılandırma**: `phpunit.xml`

### Frontend (JavaScript/React)
- **Framework**: Vitest
- **Test Kütüphaneleri**:
  - `@testing-library/react` - Component testleri
  - `@testing-library/user-event` - Kullanıcı etkileşim simülasyonu
  - `@testing-library/jest-dom` - DOM assertion'ları
- **Ortam**: jsdom (Tarayıcı simülasyonu)
- **Yapılandırma**: `vitest.config.js`

---

## 🔧 Kurulum

### 1. Bağımlılıkları Yükle

#### Backend
```bash
composer install
```

#### Frontend
```bash
npm install
```

### 2. Test Ortamını Hazırla

Backend testleri için `.env.testing` dosyası oluşturun (isteğe bağlı):
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## 🧪 Backend Testleri (PHPUnit)

### Tüm Testleri Çalıştır
```bash
php artisan test
# veya
./vendor/bin/phpunit
```

### Belirli Bir Test Dosyasını Çalıştır
```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

### Belirli Bir Test Metodunu Çalıştır
```bash
php artisan test --filter test_users_can_login_with_valid_credentials
```

### Test Kapsamı Raporu
```bash
php artisan test --coverage
```

### Mevcut Backend Testleri

#### 🔐 Authentication Tests
**Dosya**: `tests/Feature/Auth/LoginTest.php`
- ✅ Login sayfası render testi
- ✅ Geçerli kimlik bilgileri ile giriş
- ✅ Geçersiz şifre ile giriş reddi
- ✅ Geçersiz e-posta ile giriş reddi
- ✅ E-posta validasyonu
- ✅ Şifre validasyonu
- ✅ "Beni hatırla" özelliği
- ✅ Rate limiting (Brute force koruması)
- ✅ Session güvenliği (Session regeneration)

**Dosya**: `tests/Feature/Auth/RegisterTest.php`
- ✅ Kayıt sayfası render testi
- ✅ Geçerli verilerle kayıt
- ✅ Şifre hash'leme kontrolü
- ✅ İsim validasyonu (zorunlu, min 2, max 255)
- ✅ E-posta validasyonu (zorunlu, format, benzersizlik)
- ✅ Şifre validasyonu (min 8, büyük harf, küçük harf, rakam, özel karakter)
- ✅ Şifre onayı eşleşme kontrolü
- ✅ Otomatik giriş yapma

#### 💬 Message Tests
**Dosya**: `tests/Feature/MessageTest.php`
- ✅ Konuşma mesajlarını görüntüleme
- ✅ Yetkisiz erişim engelleme
- ✅ Metin mesajı gönderme
- ✅ Dosya yükleme (resim, video, ses, dosya)
- ✅ Dosya klasör yapısı
- ✅ Mesaj okuma işaretleme
- ✅ Mesaj düzenleme
- ✅ Mesaj silme (soft delete)
- ✅ Dosya silme (mesaj silindiğinde)
- ✅ Yanıt mesajları

---

## ⚛️ Frontend Testleri (Vitest)

### Tüm Testleri Çalıştır
```bash
npm test
# veya
npm run test
```

### Watch Modunda Çalıştır (Otomatik yenileme)
```bash
npm run test -- --watch
```

### UI Arayüzü ile Çalıştır
```bash
npm run test:ui
```

### Test Kapsamı Raporu
```bash
npm run test:coverage
```

### Mevcut Frontend Testleri

#### 🔐 Login Component Tests
**Dosya**: `resources/js/Pages/Auth/__tests__/Login.test.jsx`
- ✅ Component render testi
- ✅ Form alanları görüntüleme
- ✅ Input placeholder'ları
- ✅ Şifre göster/gizle özelliği
- ✅ "Beni hatırla" checkbox
- ✅ Form gönderimi
- ✅ Processing (yükleniyor) durumu
- ✅ Validasyon hatası gösterimi
- ✅ Erişilebilirlik (Accessibility)

#### 🔐 Encryption Service Tests
**Dosya**: `resources/js/Services/__tests__/encryption.test.js`
- ✅ Anahtar çifti oluşturma (RSA 2048-bit)
- ✅ Public key localStorage'a kaydetme
- ✅ Public key yükleme
- ✅ Diğer kullanıcıların public key'lerini kaydetme
- ✅ Mesaj şifreleme (AES-256-GCM + RSA-OAEP hibrit)
- ✅ Mesaj şifre çözme
- ✅ Özel karakterler desteği
- ✅ Boş ve uzun mesaj desteği
- ✅ Base64 dönüşümleri
- ✅ Public key JWK export
- ✅ Güvenlik testleri (rastgele IV, yanlış anahtar reddi)

---

## 📊 Test Kapsamı

### Mevcut Test İstatistikleri

#### Backend
- **Authentication**: 18 test
- **Messages**: 16 test
- **Toplam**: 34+ backend test

#### Frontend
- **Login Component**: 20+ test
- **Encryption Service**: 25+ test
- **Toplam**: 45+ frontend test

### Kapsanan Alanlar

#### ✅ Kritik Öncelik (Tamamlandı)
- 🔐 Authentication (Login & Register)
- 💬 Message System (CRUD operations)
- 🔒 Encryption Service

#### 🟡 Gelecek Öncelikler
- 📞 Call System (Video/Audio calls)
- 🌐 WebSocket Service
- 👥 Conversation Management
- 📢 Broadcasting Channels
- 🎨 UI Components
- 📝 Models & Relationships

---

## 🎯 Test Yazma Rehberi

### Backend Test Örneği
```php
public function test_user_can_login_with_valid_credentials(): void
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('chat.index'));
}
```

### Frontend Test Örneği
```javascript
it('component başarıyla render edilir', () => {
    render(<Login />);

    expect(screen.getByText('ChatConnect')).toBeInTheDocument();
    expect(screen.getByText('Hesabınıza giriş yapın')).toBeInTheDocument();
});
```

---

## 🐛 Hata Ayıklama

### Backend
- Testler başarısız olursa, detaylı hata mesajları için:
  ```bash
  php artisan test --verbose
  ```

### Frontend
- Testler başarısız olursa, detaylı çıktı için:
  ```bash
  npm test -- --reporter=verbose
  ```

- Tarayıcıda debug için:
  ```bash
  npm run test:ui
  ```

---

## 📚 Ek Kaynaklar

### Backend
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing Guide](https://laravel.com/docs/testing)

### Frontend
- [Vitest Documentation](https://vitest.dev/)
- [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/)

---

## 🤝 Katkıda Bulunma

Yeni test yazarken lütfen şu kurallara uyun:

1. **Test isimleri açıklayıcı olmalı** - `test_user_can_login_with_valid_credentials`
2. **Her test tek bir şeyi test etmeli** - Single Responsibility
3. **Arrange-Act-Assert** pattern'ini kullanın
4. **Test verileri factory kullanılarak oluşturulmalı**
5. **Mock'lar gerektiğinde kullanılmalı** - Ancak gerçek testler tercih edilmeli

---

## 📝 Notlar

- Backend testleri her çalıştırıldığında veritabanı sıfırlanır (`RefreshDatabase` trait)
- Frontend testleri her testten sonra component'leri temizler (`cleanup`)
- Test ortamı production'dan bağımsızdır
- CI/CD pipeline'a entegre edilebilir

---

**Son Güncelleme**: 2024
**Versiyon**: 1.0.0
