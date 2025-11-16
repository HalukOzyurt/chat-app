<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Kayıt formunu göster
     * React component'ine yönlendir
     */
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Yeni kullanıcı kaydı oluştur
     * 
     * @param RegisterRequest $request Doğrulanmış kayıt verileri
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // Yeni kullanıcı oluştur
        // $request->validated() otomatik olarak doğrulanmış verileri döndürür
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')), // Şifreyi hashle
        ]);

        // Kullanıcıyı otomatik olarak giriş yaptır
        Auth::login($user);

        // Kullanıcıyı çevrimiçi olarak işaretle
        $user->markAsOnline();

        // Ana sayfaya yönlendir
        return redirect()->route('chat.index')
            ->with('success', 'Hoş geldiniz! Kayıt işleminiz başarıyla tamamlandı.');
    }
}
