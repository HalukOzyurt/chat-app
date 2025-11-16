<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserOnline;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Giriş formunu göster
     * React Login component'ini render et
     */
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Kullanıcı girişi işle
     * 
     * @param LoginRequest $request Doğrulanmış giriş verileri
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // LoginRequest içindeki authenticate() metodunu çağır
        // Bu metod giriş kontrolü yapar ve hata varsa exception fırlatır
        $request->authenticate();

        // Session'ı yeniden oluştur (session fixation saldırılarına karşı)
        $request->session()->regenerate();

        // Kullanıcıyı çevrimiçi olarak işaretle
        Auth::user()->markAsOnline();
        
        // WebSocket ile çevrimiçi durumunu broadcast et
        broadcast(new UserOnline(Auth::user()));

        // Chat sayfasına yönlendir
        return redirect()->intended(route('chat.index'))
            ->with('success', 'Hoş geldiniz, ' . Auth::user()->name . '!');
    }
}
