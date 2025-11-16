# 🚀 WEBSOCKET VE DOSYA YÜKLEME KURULUM REHBERİ

## 🎉 Tebrikler!

WebSocket (gerçek zamanlı mesajlaşma) ve Dosya Yükleme özellikleri başarıyla projenize eklendi!

---

## 📋 EKLENENLERİN ÖZETİ

### ✅ WebSocket Özellikleri
- ✅ **Gerçek zamanlı mesaj gönderme/alma**
- ✅ **Yazıyor göstergesi** ("... yazıyor")
- ✅ **Çevrimiçi/çevrimdışı durumu** (kullanıcı giriş/çıkış)
- ✅ **Mesaj okundu bilgisi** (anlık güncelleme)
- ✅ **Presence Channel** (konuşmada kimler var?)

### ✅ Dosya Yükleme Özellikleri
- ✅ **Resim gönderme** (JPG, PNG, GIF, WebP)
- ✅ **Video gönderme** (MP4, MOV, AVI)
- ✅ **Ses dosyası gönderme** (MP3, WAV, OGG)
- ✅ **Döküman gönderme** (PDF, DOC, XLS, ZIP)
- ✅ **Dosya önizleme** (gönderilmeden önce)
- ✅ **Maksimum 50MB** dosya boyutu desteği

---

## 🛠️ KURULUM ADIMLARI

### 1. NPM Paketlerini Yükleyin

```bash
cd chat-app
npm install
```

Bu komut şu paketleri yükleyecek:
- `laravel-echo@^1.15.0` - WebSocket client
- `pusher-js@^8.3.0` - Pusher JavaScript kütüphanesi

---

### 2. PUSHER HESABI OLUŞTURUN (ÜCRETSİZ)

#### Adım 1: Pusher'a kaydolun
1. https://pusher.com adresine gidin
2. "Sign Up Free" butonuna tıklayın
3. Ücretsiz hesap oluşturun

#### Adım 2: Yeni App oluşturun
1. Dashboard'da "Create App" butonuna tıklayın
2. App Name: `ChatConnect`
3. Cluster: `eu` (Avrupa için) veya `us` (Amerika için)
4. Tech Stack: `Laravel` seçin
5. "Create App" butonuna tıklayın

#### Adım 3: API Bilgilerini alın
Dashboard'da "App Keys" sekmesine gidin ve şu bilgileri kopyalayın:
- `app_id`
- `key`
- `secret`
- `cluster`

---

### 3. .ENV DOSYASINI YAPILANDIRIN

`.env` dosyasını açın ve şu satırları ekleyin/güncelleyin:

```env
# Broadcast Driver (varsayılan: log)
BROADCAST_DRIVER=pusher

# Pusher Ayarları (Pusher Dashboard'dan alın)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=eu

# WebSocket Ayarları
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**ÖNEMLİ:** `your_app_id`, `your_app_key`, `your_app_secret` değerlerini Pusher Dashboard'dan aldığınız gerçek değerlerle değiştirin!

---

### 4. COMPOSER PAKETLERINI YÜKLEYIN

```bash
composer require pusher/pusher-php-server
```

---

### 5. BROADCASTING YAPILANDIRMASI

Laravel'in broadcasting konfigürasyonunu yapın:

#### config/broadcasting.php

Dosya zaten mevcut olmalı. Pusher connection'ı kontrol edin:

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ],
],
```

---

### 6. QUEUE WORKER'I BAŞLATIN (Opsiyonel)

WebSocket event'leri queue üzerinden göndermek isterseniz:

```bash
# .env dosyasında
QUEUE_CONNECTION=database

# Migration'ı çalıştırın
php artisan queue:table
php artisan migrate

# Queue worker'ı başlatın
php artisan queue:work
```

**NOT:** Development için queue kullanmadan da çalışabilir (sync driver).

---

### 7. STORAGE İÇİN PUBLIC LINK OLUŞTURUN

Dosya yüklemeleri için storage link'i zaten oluşturduysanız bu adımı atlayın:

```bash
php artisan storage:link
```

Bu komut `public/storage` → `storage/app/public` symbolic link'i oluşturur.

---

### 8. KLASÖR İZİNLERİNİ AYARLAYIN

```bash
# Storage klasörüne yazma izni verin
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Eğer Linux/Mac kullanıyorsanız:
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache
```

---

### 9. UYGULAMAYI ÇALIŞTIRIN

**Terminal 1 - Laravel Backend:**
```bash
php artisan serve
```

**Terminal 2 - Vite Dev Server (React):**
```bash
npm run dev
```

**Terminal 3 - Queue Worker (Opsiyonel):**
```bash
php artisan queue:work
```

---

## 🧪 TEST ETME

### WebSocket Testi

1. **İki farklı tarayıcı** açın (veya normal + incognito mode)
2. Her tarayıcıda **farklı kullanıcı** ile giriş yapın
3. Birbirinize mesaj gönderin
4. **Gerçek zamanlı** olarak mesajların geldiğini görün! ✨

**Test edilecekler:**
- ✅ Mesaj gönder → Karşı tarafta hemen görünsün
- ✅ Yazmaya başla → Karşı tarafta "... yazıyor" görünsün
- ✅ Çıkış yap → Kullanıcı offline olsun
- ✅ Giriş yap → Kullanıcı online olsun

### Dosya Yükleme Testi

1. Sohbet penceresinde **📎 butonu**na tıklayın
2. Bir dosya seçin (resim, video, vb.)
3. **Önizlemeyi** görün
4. **Gönder** butonuna tıklayın
5. Dosyanın yüklendiğini ve mesaj olarak göründüğünü kontrol edin

**Test edilecek dosya tipleri:**
- ✅ Resim (JPG, PNG)
- ✅ Video (MP4)
- ✅ Ses (MP3)
- ✅ Döküman (PDF)

---

## 🔧 SORUN GİDERME

### WebSocket Bağlanamıyor

**Problem:** Console'da "WebSocket connection failed" hatası

**Çözüm:**
1. `.env` dosyasındaki Pusher bilgilerini kontrol edin
2. `npm run dev` çalışıyor mu kontrol edin
3. Browser console'u açın ve hataları kontrol edin
4. Pusher Dashboard'da "Debug Console" açın ve event'leri izleyin

### Dosya Yüklenmiyor

**Problem:** Dosya gönderilirken hata alınıyor

**Çözüm:**
1. `storage/app/public` klasörü mevcut mu?
2. `php artisan storage:link` komutunu çalıştırdınız mı?
3. `storage` klasörüne yazma izni var mı?
4. `php.ini`'de `upload_max_filesize` ve `post_max_size` değerleri yeterli mi?

```ini
# php.ini
upload_max_filesize = 50M
post_max_size = 50M
```

### Mesajlar Gerçek Zamanlı Gelmiyor

**Problem:** Mesajları görmek için sayfa yenilenmesi gerekiyor

**Çözüm:**
1. `.env` dosyasında `BROADCAST_DRIVER=pusher` olduğundan emin olun
2. Pusher Dashboard'da "Debug Console" açın
3. Mesaj gönderdiğinizde event'in Pusher'a gittiğini kontrol edin
4. Browser console'da hata var mı kontrol edin

---

## 📊 EVENT'LER VE CHANNEL'LAR

### Event'ler

| Event | Açıklama | Channel |
|-------|----------|---------|
| `MessageSent` | Yeni mesaj gönderildi | `conversation.{id}` |
| `MessageRead` | Mesaj okundu | `conversation.{id}` |
| `UserOnline` | Kullanıcı çevrimiçi oldu | `online-users` |
| `UserOffline` | Kullanıcı çevrimdışı oldu | `online-users` |
| `TypingStarted` | Kullanıcı yazmaya başladı | `conversation.{id}` |

### Channel Türleri

1. **Public Channel** (`online-users`)
   - Herkes dinleyebilir
   - Authentication gerektirmez

2. **Private Channel** (`user.{userId}`)
   - Sadece ilgili kullanıcı dinleyebilir
   - Authentication gerektirir

3. **Presence Channel** (`conversation.{conversationId}`)
   - Kimler dinliyor görülebilir
   - Authentication gerektirir
   - "Who's here" özelliği

---

## 🎯 GELİŞMİŞ ÖZELLİKLER (Opsiyonel)

### 1. SOKETİ Kullanımı (Self-Hosted WebSocket)

Pusher yerine kendi sunucunuzda WebSocket çalıştırmak isterseniz:

```bash
# Soketi'yi yükleyin
npm install -g @soketi/soketi

# Soketi'yi başlatın
soketi start
```

`.env` dosyasını güncelleyin:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
```

### 2. Bildirim Sesi Ekleme

`public/sounds/notification.mp3` dosyası ekleyin ve ses çalma fonksiyonu otomatik çalışacak!

### 3. Desktop Bildirimleri

Browser notification API'si ile desktop bildirimleri ekleyin:

```javascript
// İzin iste
Notification.requestPermission();

// Bildirim göster
new Notification('Yeni Mesaj', {
    body: 'Ali: Merhaba!',
    icon: '/icon.png'
});
```

---

## 📚 KAYNAKLAR

- **Laravel Broadcasting Docs:** https://laravel.com/docs/broadcasting
- **Laravel Echo Docs:** https://laravel.com/docs/echo
- **Pusher Docs:** https://pusher.com/docs
- **Soketi Docs:** https://docs.soketi.app

---

## 🎉 BAŞARILAR!

WebSocket ve Dosya Yükleme özellikleri artık aktif! 

**Sonraki adımlar için fikirler:**
- 📹 WebRTC ile görüntülü arama
- 🔐 Uçtan uca şifreleme
- 📊 Grup görüntülü konferans
- 🎨 Tema özelleştirme
- 🌐 Çoklu dil desteği

İyi kodlamalar! 🚀
