<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Aramalar tablosunu oluştur
     * Sesli ve görüntülü aramaları takip eder
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // İlişkiler
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade'); // Hangi konuşmada arama yapıldı?
            $table->foreignId('caller_id')->constrained('users')->onDelete('cascade'); // Kim aradı?
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade'); // Kime arandı?
            
            // Arama türü
            $table->enum('call_type', ['audio', 'video']); // Sesli mi, görüntülü mü?
            
            // Arama durumu
            // ringing: Çalıyor (henüz cevaplanmadı)
            // ongoing: Devam ediyor (arama başladı)
            // completed: Tamamlandı (normal şekilde bitti)
            // missed: Cevapsız (açılmadı)
            // rejected: Reddedildi (kullanıcı reddet'e bastı)
            // failed: Başarısız (teknik hata)
            $table->enum('status', ['ringing', 'ongoing', 'completed', 'missed', 'rejected', 'failed'])->default('ringing');
            
            // Zaman bilgileri
            $table->timestamp('started_at')->nullable(); // Arama ne zaman başladı? (cevaplanma zamanı)
            $table->timestamp('ended_at')->nullable(); // Arama ne zaman bitti?
            $table->integer('duration')->nullable(); // Arama süresi (saniye cinsinden)
            
            // Zaman damgaları
            $table->timestamps();
            
            // İndeksler - Performans optimizasyonu
            $table->index('conversation_id'); // Konuşmadaki tüm aramalar
            $table->index('caller_id'); // Kullanıcının yaptığı aramalar
            $table->index('receiver_id'); // Kullanıcıya yapılan aramalar
            $table->index('status'); // Durum bazlı sorgular (örn: tüm cevapsız aramalar)
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
