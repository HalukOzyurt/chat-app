<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Mesaj okundu bilgisi tablosunu oluştur
     * Bu tablo, hangi kullanıcının hangi mesajı ne zaman okuduğunu takip eder
     */
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // Hangi mesaj okundu?
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            
            // Kim okudu?
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Ne zaman okundu?
            $table->timestamp('read_at')->useCurrent();
            
            // Benzersiz kısıtlama - Bir kullanıcı bir mesajı yalnızca bir kez okuyabilir
            // Bu, aynı mesajın birden fazla kez "okundu" olarak işaretlenmesini engeller
            $table->unique(['message_id', 'user_id']);
            
            // İndeksler - Performans için
            $table->index('message_id'); // Mesajı kimlerin okuduğunu görme
            $table->index('user_id'); // Kullanıcının okuduğu mesajları görme
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};
