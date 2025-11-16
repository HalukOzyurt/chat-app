<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Mesajlar tablosunu oluştur
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // İlişkiler - Bu mesaj hangi konuşmaya ait ve kim tarafından gönderildi?
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            // Mesaj türü - text (metin), image (resim), video, audio (ses), file (dosya), system (sistem mesajı)
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'file', 'system'])->default('text');
            
            // Mesaj içeriği
            $table->text('content')->nullable(); // Metin mesajlar için
            
            // Dosya bilgileri (medya mesajları için)
            $table->string('file_path')->nullable(); // Sunucudaki dosya yolu
            $table->string('file_name')->nullable(); // Orijinal dosya adı
            $table->integer('file_size')->nullable(); // Dosya boyutu (byte cinsinden)
            
            // Mesaj özellikleri
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->onDelete('set null'); // Hangi mesaja yanıt verildi?
            $table->boolean('is_edited')->default(false); // Mesaj düzenlendi mi?
            $table->timestamp('edited_at')->nullable(); // Ne zaman düzenlendi?
            
            // Soft delete - Mesaj silindiğinde tamamen yok olmasın
            $table->softDeletes(); // deleted_at sütunu ekler
            
            // Zaman damgaları
            $table->timestamps();
            
            // İndeksler - N+1 problemini önlemek için
            $table->index(['conversation_id', 'created_at']); // Konuşmadaki mesajları tarihe göre sıralama
            $table->index('sender_id'); // Kullanıcının gönderdiği tüm mesajlar
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
