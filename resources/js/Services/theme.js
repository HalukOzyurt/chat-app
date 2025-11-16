/**
 * Theme Service - Tema Yönetimi
 * 
 * CSS Variables kullanarak dinamik tema değişimi sağlar
 * localStorage'da tema tercihini saklar
 */

class ThemeService {
    constructor() {
        // Mevcut tema
        this.currentTheme = this.loadTheme();
        
        // Önceden tanımlı temalar
        this.themes = {
            light: {
                name: 'Aydınlık',
                colors: {
                    '--primary-color': '#4F46E5',
                    '--secondary-color': '#10B981',
                    '--bg-primary': '#FFFFFF',
                    '--bg-secondary': '#F9FAFB',
                    '--bg-chat': '#F3F4F6',
                    '--bg-hover': '#E5E7EB',
                    '--text-primary': '#1F2937',
                    '--text-secondary': '#6B7280',
                    '--text-muted': '#9CA3AF',
                    '--border-color': '#E5E7EB',
                    '--msg-sent-bg': '#4F46E5',
                    '--msg-received-bg': '#FFFFFF',
                },
            },
            dark: {
                name: 'Karanlık',
                colors: {
                    '--primary-color': '#6366F1',
                    '--secondary-color': '#10B981',
                    '--bg-primary': '#1F2937',
                    '--bg-secondary': '#111827',
                    '--bg-chat': '#0F172A',
                    '--bg-hover': '#374151',
                    '--text-primary': '#F9FAFB',
                    '--text-secondary': '#D1D5DB',
                    '--text-muted': '#9CA3AF',
                    '--border-color': '#374151',
                    '--msg-sent-bg': '#6366F1',
                    '--msg-received-bg': '#374151',
                },
            },
            blue: {
                name: 'Mavi',
                colors: {
                    '--primary-color': '#3B82F6',
                    '--secondary-color': '#0EA5E9',
                    '--bg-primary': '#FFFFFF',
                    '--bg-secondary': '#EFF6FF',
                    '--bg-chat': '#DBEAFE',
                    '--bg-hover': '#BFDBFE',
                    '--text-primary': '#1E3A8A',
                    '--text-secondary': '#1E40AF',
                    '--text-muted': '#60A5FA',
                    '--border-color': '#93C5FD',
                    '--msg-sent-bg': '#3B82F6',
                    '--msg-received-bg': '#FFFFFF',
                },
            },
            green: {
                name: 'Yeşil',
                colors: {
                    '--primary-color': '#10B981',
                    '--secondary-color': '#059669',
                    '--bg-primary': '#FFFFFF',
                    '--bg-secondary': '#ECFDF5',
                    '--bg-chat': '#D1FAE5',
                    '--bg-hover': '#A7F3D0',
                    '--text-primary': '#064E3B',
                    '--text-secondary': '#065F46',
                    '--text-muted': '#34D399',
                    '--border-color': '#6EE7B7',
                    '--msg-sent-bg': '#10B981',
                    '--msg-received-bg': '#FFFFFF',
                },
            },
            purple: {
                name: 'Mor',
                colors: {
                    '--primary-color': '#8B5CF6',
                    '--secondary-color': '#7C3AED',
                    '--bg-primary': '#FFFFFF',
                    '--bg-secondary': '#F5F3FF',
                    '--bg-chat': '#EDE9FE',
                    '--bg-hover': '#DDD6FE',
                    '--text-primary': '#4C1D95',
                    '--text-secondary': '#5B21B6',
                    '--text-muted': '#A78BFA',
                    '--border-color': '#C4B5FD',
                    '--msg-sent-bg': '#8B5CF6',
                    '--msg-received-bg': '#FFFFFF',
                },
            },
        };
    }

    /**
     * Tema yükle (localStorage'dan)
     */
    loadTheme() {
        const saved = localStorage.getItem('app_theme');
        return saved || 'light';
    }

    /**
     * Tema kaydet (localStorage'a)
     */
    saveTheme(themeName) {
        localStorage.setItem('app_theme', themeName);
    }

    /**
     * Tema uygula
     * 
     * @param {string} themeName - Tema adı (light, dark, blue, vb.)
     */
    applyTheme(themeName) {
        const theme = this.themes[themeName];
        if (!theme) {
            console.error('Tema bulunamadı:', themeName);
            return;
        }

        // CSS variables'ları güncelle
        const root = document.documentElement;
        Object.entries(theme.colors).forEach(([key, value]) => {
            root.style.setProperty(key, value);
        });

        // data-theme attribute'unu güncelle
        root.setAttribute('data-theme', themeName);

        // Tema tercihini kaydet
        this.currentTheme = themeName;
        this.saveTheme(themeName);
    }

    /**
     * Mevcut temayı getir
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Tüm temaları listele
     */
    getAvailableThemes() {
        return Object.entries(this.themes).map(([key, value]) => ({
            id: key,
            name: value.name,
        }));
    }

    /**
     * Özel tema oluştur
     * 
     * @param {string} themeName - Tema adı
     * @param {Object} colors - Renk değerleri
     */
    createCustomTheme(themeName, colors) {
        this.themes[themeName] = {
            name: themeName,
            colors: colors,
        };
    }

    /**
     * Tema değiştir (toggle)
     * Light ↔ Dark
     */
    toggleDarkMode() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
    }
}

// Singleton instance
const themeService = new ThemeService();

// Sayfa yüklendiğinde temayı uygula
window.addEventListener('DOMContentLoaded', () => {
    themeService.applyTheme(themeService.getCurrentTheme());
});

export default themeService;
