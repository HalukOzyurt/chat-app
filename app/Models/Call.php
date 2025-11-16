<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Call extends Model
{
    use HasFactory;

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'conversation_id',   // Hangi konuşmada
        'caller_id',         // Kim aradı
        'receiver_id',       // Kime arandı
        'call_type',         // Arama türü (audio/video)
        'status',            // Arama durumu
        'started_at',        // Başlangıç zamanı
        'ended_at',          // Bitiş zamanı
        'duration',          // Süre (saniye)
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Aramanın yapıldığı konuşma
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Aramayı başlatan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Aranan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Arama sesli mi?
     */
    public function isAudio(): bool
    {
        return $this->call_type === 'audio';
    }

    /**
     * Arama görüntülü mü?
     */
    public function isVideo(): bool
    {
        return $this->call_type === 'video';
    }

    /**
     * Arama çalıyor mu? (henüz cevaplanmadı)
     */
    public function isRinging(): bool
    {
        return $this->status === 'ringing';
    }

    /**
     * Arama devam ediyor mu?
     */
    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    /**
     * Arama tamamlandı mı?
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Arama cevapsız mı?
     */
    public function isMissed(): bool
    {
        return $this->status === 'missed';
    }

    /**
     * Arama reddedildi mi?
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Arama başarısız mı?
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Aramayı başlat (cevaplandığında çağrılır)
     */
    public function start(): void
    {
        $this->update([
            'status' => 'ongoing',
            'started_at' => now(),
        ]);
    }

    /**
     * Aramayı sonlandır
     */
    public function end(): void
    {
        $endTime = now();
        
        // Süreyi hesapla (saniye cinsinden)
        $duration = $this->started_at ? $endTime->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'ended_at' => $endTime,
            'duration' => $duration,
        ]);
    }

    /**
     * Aramayı cevapsız olarak işaretle
     */
    public function markAsMissed(): void
    {
        $this->update([
            'status' => 'missed',
            'ended_at' => now(),
        ]);
    }

    /**
     * Aramayı reddedildi olarak işaretle
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
            'ended_at' => now(),
        ]);
    }

    /**
     * Aramayı başarısız olarak işaretle
     */
    public function fail(): void
    {
        $this->update([
            'status' => 'failed',
            'ended_at' => now(),
        ]);
    }

    /**
     * Arama süresini okunabilir formata çevir
     * Örnek: 125 -> "2:05" (2 dakika 5 saniye)
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Arama türü ve durumu için simge getir
     */
    public function getIconAttribute(): string
    {
        // Arama türüne göre simge
        $typeIcon = $this->isVideo() ? '📹' : '📞';
        
        // Durum göstergesi
        $statusIcon = match($this->status) {
            'missed' => '⚠️',
            'rejected' => '❌',
            'failed' => '⚠️',
            'completed' => '✅',
            default => '⏳',
        };

        return $statusIcon . ' ' . $typeIcon;
    }
}
