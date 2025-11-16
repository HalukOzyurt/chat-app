<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Burada uygulamanın tüm web route'ları tanımlanır
*/

/**
 * GUEST ROUTES - Misafir (giriş yapmamış) kullanıcılar için
 * middleware('guest') = sadece oturum açmamış kullanıcılar erişebilir
 */
Route::middleware('guest')->group(function () {
    
    // Giriş sayfası - GET /login
    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');
    
    // Giriş işlemi - POST /login
    Route::post('/login', [LoginController::class, 'store']);
    
    // Kayıt sayfası - GET /register
    Route::get('/register', [RegisterController::class, 'show'])
        ->name('register');
    
    // Kayıt işlemi - POST /register
    Route::post('/register', [RegisterController::class, 'store']);
});

/**
 * AUTHENTICATED ROUTES - Giriş yapmış kullanıcılar için
 * middleware('auth') = sadece giriş yapmış kullanıcılar erişebilir
 */
Route::middleware('auth')->group(function () {
    
    // Ana sayfa - giriş yapınca buraya yönlendirilir
    Route::get('/', function () {
        return redirect()->route('chat.index');
    });

    // Çıkış işlemi - POST /logout
    Route::post('/logout', [LogoutController::class, 'destroy'])
        ->name('logout');

    /**
     * CHAT ROUTES - Sohbet işlemleri
     * prefix('chat') = Tüm route'lar /chat ile başlar
     * as('chat.') = Route isimleri chat. ile başlar (örn: chat.index)
     */
    Route::prefix('chat')->as('chat.')->group(function () {
        
        // Sohbet ana sayfası - GET /chat
        Route::get('/', [ConversationController::class, 'index'])
            ->name('index');
        
        // Yeni özel konuşma başlat - POST /chat/conversations
        Route::post('/conversations', [ConversationController::class, 'store'])
            ->name('conversations.store');
        
        // Yeni grup konuşması oluştur - POST /chat/groups
        Route::post('/groups', [ConversationController::class, 'createGroup'])
            ->name('groups.create');
        
        // Konuşma detaylarını getir - GET /chat/conversations/{conversation}
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
            ->name('conversations.show');
        
        // Konuşmadan ayrıl - POST /chat/conversations/{conversation}/leave
        Route::post('/conversations/{conversation}/leave', [ConversationController::class, 'leave'])
            ->name('conversations.leave');
    });

    /**
     * MESSAGE ROUTES - Mesaj işlemleri
     * prefix('messages') = Tüm route'lar /messages ile başlar
     */
    Route::prefix('messages')->as('messages.')->group(function () {
        
        // Konuşmanın mesajlarını listele - GET /messages/conversations/{conversation}
        Route::get('/conversations/{conversation}', [MessageController::class, 'index'])
            ->name('index');
        
        // Yeni mesaj gönder - POST /messages
        Route::post('/', [MessageController::class, 'store'])
            ->name('store');
        
        // Mesajı okundu olarak işaretle - POST /messages/{message}/read
        Route::post('/{message}/read', [MessageController::class, 'markAsRead'])
            ->name('read');
        
        // Mesajı düzenle - PUT/PATCH /messages/{message}
        Route::put('/{message}', [MessageController::class, 'update'])
            ->name('update');
        
        // Mesajı sil - DELETE /messages/{message}
        Route::delete('/{message}', [MessageController::class, 'destroy'])
            ->name('destroy');
    });

    /**
     * CALL ROUTES - Arama işlemleri
     * prefix('calls') = Tüm route'lar /calls ile başlar
     */
    Route::prefix('calls')->as('calls.')->group(function () {
        
        // Arama başlat - POST /calls/initiate
        Route::post('/initiate', [CallController::class, 'initiate'])
            ->name('initiate');
        
        // Aramayı kabul et - POST /calls/{call}/accept
        Route::post('/{call}/accept', [CallController::class, 'accept'])
            ->name('accept');
        
        // Aramayı reddet - POST /calls/{call}/reject
        Route::post('/{call}/reject', [CallController::class, 'reject'])
            ->name('reject');
        
        // Aramayı sonlandır - POST /calls/{call}/end
        Route::post('/{call}/end', [CallController::class, 'end'])
            ->name('end');
        
        // Arama geçmişi - GET /calls/history
        Route::get('/history', [CallController::class, 'history'])
            ->name('history');
    });
});

/**
 * Fallback route - Hiçbir route eşleşmezse
 * 404 sayfasına yönlendirir
 */
Route::fallback(function () {
    return response()->json([
        'message' => 'Sayfa bulunamadı.'
    ], 404);
});
