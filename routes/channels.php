<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Burada broadcast channel'larının yetkilendirme kuralları tanımlanır
| Hangi kullanıcı hangi channel'ı dinleyebilir?
*/

/**
 * PRESENCE CHANNEL - Conversation
 * 
 * Bir konuşmanın channel'ına sadece o konuşmanın katılımcıları katılabilir
 * 
 * Channel adı: conversation.{conversationId}
 * Örnek: conversation.1, conversation.2, vb.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Konuşmayı bul
    $conversation = Conversation::find($conversationId);
    
    // Konuşma bulunamazsa erişim reddet
    if (!$conversation) {
        return false;
    }
    
    // Kullanıcı bu konuşmada mı kontrol et
    if (!$conversation->hasParticipant($user->id)) {
        return false;
    }
    
    // Erişim izni ver ve kullanıcı bilgilerini döndür
    // Bu bilgiler presence channel'da diğer kullanıcılara gösterilir
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
        'is_online' => true,
    ];
});

/**
 * PUBLIC CHANNEL - Online Users
 * 
 * Tüm kullanıcılar bu channel'ı dinleyebilir
 * Çevrimiçi kullanıcı durumlarını broadcast eder
 * 
 * Channel adı: online-users
 */
Broadcast::channel('online-users', function ($user) {
    // Kimlik doğrulaması yapılmış herkes dinleyebilir
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
    
    return false;
});

/**
 * PRIVATE CHANNEL - User Notifications
 * 
 * Kullanıcıya özel bildirim channel'ı
 * Sadece ilgili kullanıcı kendi bildirimlerini dinleyebilir
 * 
 * Channel adı: user.{userId}
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Kullanıcı kendi channel'ını mı dinliyor?
    return (int) $user->id === (int) $userId;
});

/**
 * PRESENCE CHANNEL - Active Users in App
 * 
 * Uygulamada aktif olan tüm kullanıcılar
 * Kimler çevrimiçi görmek için kullanılır
 * 
 * Channel adı: app-active-users
 */
Broadcast::channel('app-active-users', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
            'is_online' => true,
            'last_seen' => now()->toISOString(),
        ];
    }
    
    return false;
});
