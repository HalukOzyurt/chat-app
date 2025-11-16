<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Kullanıcılar tablosunu oluştur
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Birincil anahtar - Otomatik artan benzersiz ID
            $table->id();
            
            // Kullanıcı bilgileri
            $table->string('name'); // Kullanıcı adı
            $table->string('email')->unique(); // E-posta adresi (benzersiz olmalı)
            $table->timestamp('email_verified_at')->nullable(); // E-posta doğrulama zamanı
            $table->string('password'); // Şifrelenmiş parola
            
            // Profil bilgileri
            $table->string('avatar')->nullable(); // Profil resmi yolu
            $table->text('status_message')->nullable(); // Durum mesajı ("Meşgulüm" vb.)
            
            // Çevrimiçi durum bilgileri
            $table->boolean('is_online')->default(false); // Kullanıcı şu an çevrimiçi mi?
            $table->timestamp('last_seen_at')->nullable(); // Son görülme zamanı
            
            // Laravel otomatik token alanı
            $table->rememberToken(); // "Beni hatırla" için token
            
            // Zaman damgaları - created_at ve updated_at
            $table->timestamps();
            
            // İndeksler - Sorgu performansı için
            $table->index('email'); // E-posta ile arama için
            $table->index('is_online'); // Çevrimiçi kullanıcıları listeleme için
        });
    }

    /**
     * Migration'ı geri al - Kullanıcılar tablosunu sil
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
