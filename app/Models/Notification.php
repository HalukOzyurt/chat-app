<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'user_id',      // Bildirimi alan kullanıcı
        'type',         // Bildirim türü
        'title',        // Başlık
        'message',      // Mesaj
        'data',         // Ek veri (JSON)
        'is_read',      // Okundu mu
        'read_at',      // Okunma zamanı
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'data' => 'array',           // JSON'u otomatik array'e çevir
        'is_read' => 'boolean',      // 0/1'i true/false'a çevir
        'read_at' => 'datetime',     // Tarih formatına çevir
        'created_at' => 'datetime',
    ];

    /**
     * timestamps - Sadece created_at kullanıyoruz
     */
    public $timestamps = false;

    /**
     * Model oluşturulduğunda çalışacak event
     */
    protected static function boot()
    {
        parent::boot();

        // Her yeni bildirim oluşturulduğunda created_at'i otomatik ayarla
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Bildirimi alan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * SCOPE'LAR (QUERY HELPERS)
     */

    /**
     * Sadece okunmamış bildirimleri getir
     * Kullanım: Notification::unread()->get()
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Sadece okunmuş bildirimleri getir
     * Kullanım: Notification::read()->get()
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Belirli bir kullanıcının bildirimlerini getir
     * Kullanım: Notification::forUser($userId)->get()
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Son bildirimleri getir (en yeniden en eskiye)
     * Kullanım: Notification::recent()->get()
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Bildirimi okunmadı olarak işaretle
     */
    public function markAsUnread(): void
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }

    /**
     * Bildirim okunmamış mı?
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Bildirim türüne göre simge getir
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'new_message' => '💬',
            'missed_call' => '📞',
            'video_call' => '📹',
            'friend_request' => '👥',
            'mention' => '@',
            'reaction' => '❤️',
            default => '🔔',
        };
    }

    /**
     * Bildirim rengini getir (Bootstrap renk sınıfı)
     */
    public function getColorClassAttribute(): string
    {
        return match($this->type) {
            'new_message' => 'primary',
            'missed_call' => 'warning',
            'video_call' => 'info',
            'friend_request' => 'success',
            'mention' => 'secondary',
            default => 'light',
        };
    }

    /**
     * Bildirimin göreceli zamanını getir (örn: "2 saat önce")
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
