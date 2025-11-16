<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration'ı çalıştır - Konuşmalar tablosunu oluştur
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            // Birincil anahtar
            $table->id();
            
            // Konuşma türü - 'private' (birebir) veya 'group' (grup)
            $table->enum('type', ['private', 'group'])->default('private');
            
            // Grup bilgileri (sadece grup sohbetleri için)
            $table->string('name')->nullable(); // Grup adı (örn: "Laravel Ekibi")
            $table->string('avatar')->nullable(); // Grup profil resmi
            
            // Grubu oluşturan kullanıcı (NULL olabilir - kullanıcı silinirse)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Zaman damgaları
            $table->timestamps();
            
            // İndeksler
            $table->index('type'); // Konuşma türüne göre filtreleme için
        });
    }

    /**
     * Migration'ı geri al
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
