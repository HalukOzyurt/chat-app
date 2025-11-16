/**
 * WebSocket Service - Laravel Echo Yapılandırması
 * 
 * Bu dosya Laravel Echo'yu yapılandırır ve WebSocket bağlantısını yönetir
 * Pusher kullanarak gerçek zamanlı iletişim sağlar
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Pusher'ı global olarak tanımla (Echo için gerekli)
window.Pusher = Pusher;

/**
 * Echo instance'ını oluştur
 * 
 * Pusher yapılandırması:
 * - broadcaster: 'pusher' (Laravel'in broadcast driver'ı)
 * - key: Pusher API key (ücretsiz plan için test değeri)
 * - cluster: Sunucu konumu (eu için 'eu', us için 'mt1')
 * - wsHost/wsPort: Local development için (Soketi kullanıyorsanız)
 */
const echo = new Echo({
    broadcaster: 'pusher',
    
    // PUSHER KULLANIMI (Production için)
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu',
    forceTLS: true,  // HTTPS kullan
    
    // SOKETİ KULLANIMI (Local development için - yorumları kaldırın)
    // wsHost: window.location.hostname,
    // wsPort: 6001,
    // wssPort: 6001,
    // forceTLS: false,
    // disableStats: true,
    // enabledTransports: ['ws', 'wss'],
    
    // Auth endpoint - Private/Presence channel'lar için
    authEndpoint: '/broadcasting/auth',
    
    // CSRF token ekle (Laravel için gerekli)
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    },
});

/**
 * Echo instance'ını export et
 * Diğer component'lerde kullanmak için
 */
export default echo;

/**
 * KULLANIM ÖRNEKLERİ:
 * 
 * 1. Public Channel Dinleme:
 * ----------------------------
 * import echo from '@/Services/websocket';
 * 
 * echo.channel('online-users')
 *     .listen('.user.online', (data) => {
 *         console.log('User online:', data);
 *     });
 * 
 * 
 * 2. Private Channel Dinleme:
 * ----------------------------
 * echo.private(`user.${userId}`)
 *     .listen('.notification.sent', (data) => {
 *         console.log('New notification:', data);
 *     });
 * 
 * 
 * 3. Presence Channel Dinleme:
 * ----------------------------
 * echo.join(`conversation.${conversationId}`)
 *     .here((users) => {
 *         console.log('Currently online users:', users);
 *     })
 *     .joining((user) => {
 *         console.log('User joined:', user);
 *     })
 *     .leaving((user) => {
 *         console.log('User left:', user);
 *     })
 *     .listen('.message.sent', (data) => {
 *         console.log('New message:', data);
 *     });
 * 
 * 
 * 4. Channel'dan Ayrılma:
 * ----------------------------
 * echo.leave(`conversation.${conversationId}`);
 */
