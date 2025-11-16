/**
 * i18n Service - Çoklu Dil Desteği
 * 
 * Uygulama metinlerini farklı dillerde gösterir
 * localStorage'da dil tercihini saklar
 */

class I18nService {
    constructor() {
        // Mevcut dil
        this.currentLang = this.loadLanguage();
        
        // Çeviriler
        this.translations = {
            tr: {
                // Genel
                app_name: 'ChatConnect',
                loading: 'Yükleniyor...',
                error: 'Hata',
                success: 'Başarılı',
                cancel: 'İptal',
                save: 'Kaydet',
                delete: 'Sil',
                edit: 'Düzenle',
                send: 'Gönder',
                search: 'Ara',
                
                // Auth
                login: 'Giriş Yap',
                register: 'Kayıt Ol',
                logout: 'Çıkış Yap',
                email: 'E-posta',
                password: 'Şifre',
                name: 'Ad Soyad',
                forgot_password: 'Şifremi Unuttum',
                remember_me: 'Beni Hatırla',
                already_have_account: 'Hesabınız var mı?',
                dont_have_account: 'Hesabınız yok mu?',
                
                // Chat
                conversations: 'Sohbetler',
                new_chat: 'Yeni Sohbet',
                type_message: 'Mesajınızı yazın...',
                search_conversations: 'Konuşmalarda ara...',
                online: 'Çevrimiçi',
                offline: 'Çevrimdışı',
                typing: 'yazıyor...',
                no_messages: 'Henüz mesaj yok',
                
                // Call
                audio_call: 'Sesli Arama',
                video_call: 'Görüntülü Arama',
                calling: 'Aranıyor...',
                ringing: 'Çalıyor...',
                incoming_call: 'Gelen Arama',
                accept_call: 'Kabul Et',
                reject_call: 'Reddet',
                end_call: 'Aramayı Bitir',
                mute: 'Sessiz',
                unmute: 'Sesi Aç',
                camera_on: 'Kamerayı Aç',
                camera_off: 'Kamerayı Kapat',
                screen_share: 'Ekran Paylaş',
                
                // Settings
                settings: 'Ayarlar',
                theme: 'Tema',
                language: 'Dil',
                notifications: 'Bildirimler',
                privacy: 'Gizlilik',
                
                // Errors
                error_network: 'Ağ hatası. Lütfen internet bağlantınızı kontrol edin.',
                error_auth: 'Giriş bilgileri hatalı.',
                error_permission: 'İzin reddedildi.',
                error_unknown: 'Bilinmeyen bir hata oluştu.',
            },
            en: {
                // General
                app_name: 'ChatConnect',
                loading: 'Loading...',
                error: 'Error',
                success: 'Success',
                cancel: 'Cancel',
                save: 'Save',
                delete: 'Delete',
                edit: 'Edit',
                send: 'Send',
                search: 'Search',
                
                // Auth
                login: 'Login',
                register: 'Register',
                logout: 'Logout',
                email: 'Email',
                password: 'Password',
                name: 'Full Name',
                forgot_password: 'Forgot Password',
                remember_me: 'Remember Me',
                already_have_account: 'Already have an account?',
                dont_have_account: 'Don\'t have an account?',
                
                // Chat
                conversations: 'Conversations',
                new_chat: 'New Chat',
                type_message: 'Type your message...',
                search_conversations: 'Search conversations...',
                online: 'Online',
                offline: 'Offline',
                typing: 'typing...',
                no_messages: 'No messages yet',
                
                // Call
                audio_call: 'Audio Call',
                video_call: 'Video Call',
                calling: 'Calling...',
                ringing: 'Ringing...',
                incoming_call: 'Incoming Call',
                accept_call: 'Accept',
                reject_call: 'Reject',
                end_call: 'End Call',
                mute: 'Mute',
                unmute: 'Unmute',
                camera_on: 'Camera On',
                camera_off: 'Camera Off',
                screen_share: 'Share Screen',
                
                // Settings
                settings: 'Settings',
                theme: 'Theme',
                language: 'Language',
                notifications: 'Notifications',
                privacy: 'Privacy',
                
                // Errors
                error_network: 'Network error. Please check your internet connection.',
                error_auth: 'Invalid credentials.',
                error_permission: 'Permission denied.',
                error_unknown: 'An unknown error occurred.',
            },
            de: {
                // General
                app_name: 'ChatConnect',
                loading: 'Wird geladen...',
                error: 'Fehler',
                success: 'Erfolgreich',
                cancel: 'Abbrechen',
                save: 'Speichern',
                delete: 'Löschen',
                edit: 'Bearbeiten',
                send: 'Senden',
                search: 'Suchen',
                
                // Auth
                login: 'Anmelden',
                register: 'Registrieren',
                logout: 'Abmelden',
                email: 'E-Mail',
                password: 'Passwort',
                name: 'Vollständiger Name',
                forgot_password: 'Passwort vergessen',
                remember_me: 'Angemeldet bleiben',
                already_have_account: 'Haben Sie bereits ein Konto?',
                dont_have_account: 'Noch kein Konto?',
                
                // Chat
                conversations: 'Unterhaltungen',
                new_chat: 'Neuer Chat',
                type_message: 'Nachricht eingeben...',
                search_conversations: 'Unterhaltungen durchsuchen...',
                online: 'Online',
                offline: 'Offline',
                typing: 'tippt...',
                no_messages: 'Noch keine Nachrichten',
                
                // Call
                audio_call: 'Audioanruf',
                video_call: 'Videoanruf',
                calling: 'Anrufen...',
                ringing: 'Klingelt...',
                incoming_call: 'Eingehender Anruf',
                accept_call: 'Annehmen',
                reject_call: 'Ablehnen',
                end_call: 'Anruf beenden',
                mute: 'Stumm',
                unmute: 'Stummschaltung aufheben',
                camera_on: 'Kamera ein',
                camera_off: 'Kamera aus',
                screen_share: 'Bildschirm teilen',
                
                // Settings
                settings: 'Einstellungen',
                theme: 'Thema',
                language: 'Sprache',
                notifications: 'Benachrichtigungen',
                privacy: 'Privatsphäre',
                
                // Errors
                error_network: 'Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.',
                error_auth: 'Ungültige Anmeldedaten.',
                error_permission: 'Zugriff verweigert.',
                error_unknown: 'Ein unbekannter Fehler ist aufgetreten.',
            },
        };
    }

    /**
     * Dil yükle (localStorage'dan)
     */
    loadLanguage() {
        const saved = localStorage.getItem('app_language');
        return saved || this.detectBrowserLanguage();
    }

    /**
     * Tarayıcı dilini algıla
     */
    detectBrowserLanguage() {
        const browserLang = navigator.language || navigator.userLanguage;
        const langCode = browserLang.split('-')[0]; // 'tr-TR' -> 'tr'
        
        // Desteklenen diller arasında varsa kullan
        return this.translations[langCode] ? langCode : 'en';
    }

    /**
     * Dil kaydet (localStorage'a)
     */
    saveLanguage(lang) {
        localStorage.setItem('app_language', lang);
    }

    /**
     * Dili değiştir
     * 
     * @param {string} lang - Dil kodu (tr, en, de)
     */
    setLanguage(lang) {
        if (!this.translations[lang]) {
            console.error('Desteklenmeyen dil:', lang);
            return;
        }

        this.currentLang = lang;
        this.saveLanguage(lang);
        
        // Sayfayı yenile (tüm metinler güncellensin)
        window.location.reload();
    }

    /**
     * Çeviri getir
     * 
     * @param {string} key - Çeviri anahtarı
     * @param {Object} params - Parametreler (placeholder değişimi için)
     * @returns {string} - Çevrilmiş metin
     */
    t(key, params = {}) {
        let translation = this.translations[this.currentLang]?.[key];
        
        // Çeviri bulunamazsa İngilizce'yi dene
        if (!translation) {
            translation = this.translations['en']?.[key];
        }
        
        // Hala bulunamazsa key'i döndür
        if (!translation) {
            console.warn('Çeviri bulunamadı:', key);
            return key;
        }

        // Parametreleri yerine koy
        // Örnek: t('welcome_user', { name: 'Ali' }) -> "Hoş geldin, Ali!"
        Object.entries(params).forEach(([key, value]) => {
            translation = translation.replace(`{${key}}`, value);
        });

        return translation;
    }

    /**
     * Mevcut dili getir
     */
    getCurrentLanguage() {
        return this.currentLang;
    }

    /**
     * Desteklenen dilleri listele
     */
    getAvailableLanguages() {
        return [
            { code: 'tr', name: 'Türkçe', flag: '🇹🇷' },
            { code: 'en', name: 'English', flag: '🇬🇧' },
            { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
        ];
    }
}

// Singleton instance
const i18n = new I18nService();

// Global erişim için (React component'lerinde kullanım kolaylığı)
window.t = (key, params) => i18n.t(key, params);

export default i18n;
