<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserOffline;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Kullanıcıyı çıkış yaptır
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Kullanıcı bilgisini sakla (çıkıştan önce)
        $user = Auth::user();
        
        // Kullanıcıyı çevrimdışı olarak işaretle
        $user->markAsOffline();
        
        // WebSocket ile çevrimdışı durumunu broadcast et
        broadcast(new UserOffline($user));

        // Laravel Auth sisteminden çıkış yap
        Auth::guard('web')->logout();

        // Session'ı geçersiz kıl (güvenlik için)
        $request->session()->invalidate();

        // CSRF token'ı yeniden oluştur
        $request->session()->regenerateToken();

        // Giriş sayfasına yönlendir
        return redirect()->route('login')
            ->with('success', 'Başarıyla çıkış yaptınız.');
    }
}
