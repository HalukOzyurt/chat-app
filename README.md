# 💬 ChatConnect - Sesli & Görüntülü Sohbet Uygulaması

Laravel + React + Inertia.js + Bootstrap 5 ile geliştirilmiş modern bir gerçek zamanlı sohbet uygulaması.

## 📋 İçindekiler

- [Özellikler](#özellikler)
- [Teknolojiler](#teknolojiler)
- [Kurulum](#kurulum)
- [Veritabanı Yapısı](#veritabanı-yapısı)
- [Klasör Yapısı](#klasör-yapısı)
- [Kullanım](#kullanım)
- [Gelecek Özellikler](#gelecek-özellikler)

## ✨ Özellikler

### Temel Özellikler
- ✅ Kullanıcı kaydı ve girişi
- ✅ Birebir mesajlaşma
- ✅ Grup sohbetleri
- ✅ Gerçek zamanlı mesaj gönderme/alma
- ✅ Mesaj okundu bilgisi
- ✅ Çevrimiçi/çevrimdışı durumu
- ✅ Responsive tasarım (mobil uyumlu)
- ✅ Karanlık/Aydınlık tema desteği

### Gelişmiş Özellikler (İleride)
- 📎 Dosya paylaşımı
- 🎥 Görüntülü arama
- 📞 Sesli arama
- 🎤 Sesli mesaj
- ❤️ Mesaj reaksiyonları
- 🔍 Mesaj arama

## 🛠 Teknolojiler

### Backend
- **Laravel 10.x** - PHP Framework
- **MySQL** - Veritabanı
- **Inertia.js** - Server-side rendering

### Frontend
- **React 18.x** - UI Library
- **Bootstrap 5.3** - CSS Framework
- **Vite** - Build Tool
- **Axios** - HTTP Client

## 📦 Kurulum

### Gereksinimler
- PHP >= 8.1
- Composer
- Node.js >= 18.x
- MySQL >= 5.7

### Adım Adım Kurulum

#### 1. Projeyi klonlayın
```bash
git clone https://github.com/your-username/chat-app.git
cd chat-app
```

#### 2. Composer bağımlılıklarını yükleyin
```bash
composer install
```

#### 3. NPM bağımlılıklarını yükleyin
```bash
npm install
```

#### 4. Ortam dosyasını oluşturun
```bash
cp .env.example .env
```

#### 5. Uygulama anahtarı oluşturun
```bash
php artisan key:generate
```

#### 6. Veritabanı ayarlarını yapın
`.env` dosyasını açın ve veritabanı bilgilerinizi girin:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_app
DB_USERNAME=root
DB_PASSWORD=
```

#### 7. Veritabanını oluşturun
```bash
# MySQL'e bağlanın
mysql -u root -p

# Veritabanı oluşturun
CREATE DATABASE chat_app;
exit;
```

#### 8. Migration'ları çalıştırın
```bash
php artisan migrate
```

#### 9. Storage link oluşturun (dosya yüklemeleri için)
```bash
php artisan storage:link
```

#### 10. Uygulamayı başlatın

**Terminal 1 - Laravel Backend:**
```bash
php artisan serve
```

**Terminal 2 - Vite Dev Server (React):**
```bash
npm run dev
```

Tarayıcınızda `http://localhost:8000` adresini açın.

## 📊 Veritabanı Yapısı

### Tablolar

#### 1. `users` - Kullanıcılar
- Kullanıcı bilgileri (ad, email, şifre)
- Profil resmi ve durum mesajı
- Çevrimiçi durumu

#### 2. `conversations` - Konuşmalar
- Konuşma türü (private/group)
- Grup bilgileri (ad, resim)

#### 3. `conversation_participants` - Katılımcılar
- Kullanıcı-konuşma ilişkisi
- Katılım/ayrılma zamanları
- Son okuma zamanı

#### 4. `messages` - Mesajlar
- Mesaj içeriği
- Mesaj türü (text, image, video, vb.)
- Dosya bilgileri

#### 5. `message_reads` - Mesaj Okundu Bilgisi
- Hangi mesajı kim ne zaman okudu

#### 6. `calls` - Aramalar
- Sesli/görüntülü aramalar
- Arama durumu ve süresi

#### 7. `notifications` - Bildirimler
- Kullanıcı bildirimleri
- Okundu durumu

## 📁 Klasör Yapısı

```
chat-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controller dosyaları
│   │   │   ├── Auth/         # Authentication controllers
│   │   │   ├── ConversationController.php
│   │   │   └── MessageController.php
│   │   └── Requests/         # Form validation
│   └── Models/               # Eloquent modeller
│       ├── User.php
│       ├── Conversation.php
│       └── Message.php
├── database/
│   └── migrations/           # Veritabanı migration'ları
├── resources/
│   ├── js/
│   │   ├── Components/      # React component'leri
│   │   │   ├── Auth/       # Giriş/Kayıt
│   │   │   ├── Chat/       # Sohbet bileşenleri
│   │   │   └── Layout/     # Layout bileşenleri
│   │   ├── Pages/          # Sayfa component'leri
│   │   │   ├── Auth/
│   │   │   │   ├── Login.jsx
│   │   │   │   └── Register.jsx
│   │   │   └── Chat/
│   │   │       └── Index.jsx
│   │   └── app.jsx         # Ana React dosyası
│   └── css/
│       └── app.css         # Custom CSS
└── routes/
    └── web.php             # Web routes
```

## 🎯 Kullanım

### Kullanıcı Kaydı
1. `/register` sayfasına gidin
2. Ad, email ve şifre bilgilerinizi girin
3. "Hesap Oluştur" butonuna tıklayın

### Giriş Yapma
1. `/login` sayfasına gidin
2. Email ve şifrenizi girin
3. "Giriş Yap" butonuna tıklayın

### Sohbet Başlatma
1. Ana sayfada sağ üstteki "✏️" butonuna tıklayın
2. Mesajlaşmak istediğiniz kullanıcıyı seçin
3. Mesajınızı yazın ve Enter'a basın

### Mesaj Gönderme
- Metin kutusu aktif konuşmada mesaj yazabilirsiniz
- Enter ile mesaj gönderin (Shift+Enter ile satır atlayın)
- Emojiler direkt yazılabilir 😊

## 🔮 Gelecek Özellikler

### Kısa Vadede
- [x] Dosya/resim gönderme
- [ ] Emoji picker
- [ ] Mesaj arama
- [x] Bildirim sesleri

### Orta Vadede
- [ ] WebRTC ile sesli arama
- [ ] WebRTC ile görüntülü arama
- [ ] Sesli mesaj kaydı
- [ ] Mesaj reaksiyonları

### Uzun Vadede
- [ ] Uçtan uca şifreleme
- [ ] Grup görüntülü konferans
- [ ] Ekran paylaşımı
- [ ] Hikayeler özelliği

## 🐛 Hata Çözümleri

### Migration Hatası
```bash
# Cache'i temizle
php artisan config:clear
php artisan cache:clear

# Migration'ı tekrar dene
php artisan migrate:fresh
```

### NPM Hatası
```bash
# Node modules'ü sil ve tekrar yükle
rm -rf node_modules
npm install
```

### 500 Internal Server Error
```bash
# Log dosyasını kontrol et
tail -f storage/logs/laravel.log

# Storage izinlerini düzelt
chmod -R 775 storage bootstrap/cache
```

## 📝 Notlar

### N+1 Problem Çözümü
Tüm model ilişkilerinde eager loading kullanılmıştır:
```php
// ✅ Doğru - Eager Loading
Message::with('sender', 'conversation')->get();

// ❌ Yanlış - N+1 Problem
Message::all(); // Her mesaj için ayrı sorgu
```

### Güvenlik
- Tüm şifreler bcrypt ile hashlenir
- CSRF koruması aktif
- SQL Injection koruması (Eloquent ORM)
- XSS koruması (React otomatik escape)

## 👨‍💻 Geliştirici

**Adınız**
- GitHub: [@username](https://github.com/username)
- Email: your.email@example.com

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

---

**💡 İpucu:** Herhangi bir sorunla karşılaşırsanız, issue açmaktan çekinmeyin!
