<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Bildirimler tablosunu oluştur
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // Bildirimi alan kullanıcı
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Bildirim bilgileri
            $table->string('type'); // Bildirim türü (örn: 'new_message', 'call_missed', 'friend_request')
            $table->string('title'); // Bildirim başlığı (örn: "Yeni Mesaj")
            $table->text('message')->nullable(); // Bildirim içeriği (örn: "Ali size mesaj gönderdi")
            
            // Ek veri - JSON formatında esnek veri saklama
            // Örnek: {"conversation_id": 5, "sender_name": "Ali", "message_preview": "Merhaba!"}
            $table->json('data')->nullable();
            
            // Okunma durumu
            $table->boolean('is_read')->default(false); // Bildirim okundu mu?
            $table->timestamp('read_at')->nullable(); // Ne zaman okundu?
            
            // Oluşturulma zamanı
            $table->timestamp('created_at')->useCurrent();
            
            // İndeksler - Hızlı sorgular için
            $table->index(['user_id', 'is_read']); // Kullanıcının okunmamış bildirimlerini getir
            $table->index('created_at'); // En yeni bildirimleri göster
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
