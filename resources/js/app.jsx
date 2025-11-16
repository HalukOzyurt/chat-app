/**
 * Ana React Uygulama Dosyası
 * 
 * Bu dosya Inertia.js ile Laravel'i React'a bağlar
 * Tüm React component'leri buradan yüklenir
 */

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Bootstrap CSS ve JS import
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Custom CSS import
import '../css/app.css';

// Uygulama adı
const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'ChatConnect';

/**
 * Inertia uygulamasını oluştur
 */
createInertiaApp({
    // Sayfa başlığı formatter
    title: (title) => `${title} - ${appName}`,
    
    /**
     * Component resolver - Sayfa component'lerini dinamik yükle
     * Pages klasöründeki tüm .jsx dosyalarını otomatik import eder
     */
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.jsx`,
        import.meta.glob('./Pages/**/*.jsx')
    ),
    
    /**
     * Setup fonksiyonu - React uygulamasını DOM'a monte et
     */
    setup({ el, App, props }) {
        const root = createRoot(el);  // React 18 createRoot API
        
        root.render(<App {...props} />);  // Uygulamayı render et
    },
    
    /**
     * Progress bar ayarları
     * Sayfa geçişlerinde üstte progress bar göster
     */
    progress: {
        color: '#4F46E5',  // İndigo renk (primary color)
        showSpinner: true,  // Spinner göster
    },
});
