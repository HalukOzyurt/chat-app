<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Konuşma katılımcıları tablosunu oluştur
     * Bu tablo, hangi kullanıcıların hangi konuşmalarda olduğunu takip eder
     */
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // Yabancı anahtarlar - İlişkili tablolara bağlantılar
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Kullanıcı rolü - 'admin' (grup yöneticisi) veya 'member' (normal üye)
            $table->enum('role', ['admin', 'member'])->default('member');
            
            // Katılım ve ayrılma zamanları
            $table->timestamp('joined_at')->useCurrent(); // Konuşmaya katılma zamanı
            $table->timestamp('left_at')->nullable(); // Konuşmadan ayrılma zamanı (NULL = hala üye)
            
            // Son okunan mesaj zamanı - Okunmamış mesaj sayısı için kullanılır
            $table->timestamp('last_read_at')->nullable();
            
            // Benzersiz kısıtlama - Bir kullanıcı bir konuşmaya yalnızca bir kez katılabilir
            $table->unique(['conversation_id', 'user_id']);
            
            // İndeksler - Hızlı sorgular için
            $table->index('conversation_id'); // Konuşmadaki tüm katılımcıları listeleme
            $table->index('user_id'); // Kullanıcının tüm konuşmalarını listeleme
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
