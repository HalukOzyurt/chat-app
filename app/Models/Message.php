<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'conversation_id',       // Hangi konuşmaya ait
        'sender_id',             // Kim gönderdi
        'message_type',          // Mesaj türü (text, image, vb.)
        'content',               // Mesaj içeriği
        'file_path',             // Dosya yolu
        'file_name',             // Dosya adı
        'file_size',             // Dosya boyutu
        'reply_to_message_id',   // Hangi mesaja yanıt
        'is_edited',             // Düzenlendi mi
        'edited_at',             // Düzenlenme zamanı
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Mesajın ait olduğu konuşma
     * Ters bire-çok ilişki (Inverse One-to-Many)
     * N+1 problemini önlemek için: Message::with('conversation')->get()
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Mesajı gönderen kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     * N+1 problemini önlemek için: Message::with('sender')->get()
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Bu mesajı okuyan kullanıcılar
     * Çoka-çok ilişki (Many-to-Many) - message_reads tablosu üzerinden
     * N+1 problemini önlemek için: Message::with('readBy')->get()
     */
    public function readBy()
    {
        return $this->belongsToMany(User::class, 'message_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Mesajın okunma kayıtları
     * Bire-çok ilişki (One-to-Many)
     */
    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Bu mesaja yanıt olarak gönderilen mesajlar
     * Bire-çok ilişki (One-to-Many)
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    /**
     * Bu mesajın yanıt verdiği mesaj
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Mesaj metin mesajı mı?
     */
    public function isText(): bool
    {
        return $this->message_type === 'text';
    }

    /**
     * Mesaj resim mi?
     */
    public function isImage(): bool
    {
        return $this->message_type === 'image';
    }

    /**
     * Mesaj video mu?
     */
    public function isVideo(): bool
    {
        return $this->message_type === 'video';
    }

    /**
     * Mesaj ses dosyası mı?
     */
    public function isAudio(): bool
    {
        return $this->message_type === 'audio';
    }

    /**
     * Mesaj dosya mı?
     */
    public function isFile(): bool
    {
        return $this->message_type === 'file';
    }

    /**
     * Mesaj sistem mesajı mı? (örn: "Ali gruba katıldı")
     */
    public function isSystem(): bool
    {
        return $this->message_type === 'system';
    }

    /**
     * Dosya URL'sini getir
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Belirtilen kullanıcı bu mesajı okudu mu?
     * 
     * @param int|User $user Kullanıcı ID veya User modeli
     */
    public function isReadBy($user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return $this->readBy()->where('user_id', $userId)->exists();
    }

    /**
     * Mesajı belirtilen kullanıcı için okundu olarak işaretle
     * 
     * @param int|User $user Kullanıcı ID veya User modeli
     */
    public function markAsReadBy($user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Zaten okunduysa tekrar ekleme
        if (!$this->isReadBy($userId)) {
            $this->readBy()->attach($userId, [
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mesajı düzenle
     * 
     * @param string $newContent Yeni mesaj içeriği
     */
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Dosya boyutunu okunabilir formata çevir
     * Örnek: 1024 -> "1 KB"
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Mesajın kısa önizlemesini getir (ilk 50 karakter)
     */
    public function getPreviewAttribute(): string
    {
        if ($this->isText()) {
            return strlen($this->content) > 50 
                ? substr($this->content, 0, 50) . '...' 
                : $this->content;
        }

        // Medya mesajları için özel önizlemeler
        return match($this->message_type) {
            'image' => '📷 Fotoğraf',
            'video' => '🎥 Video',
            'audio' => '🎵 Ses Kaydı',
            'file' => '📎 ' . ($this->file_name ?? 'Dosya'),
            'system' => $this->content,
            default => 'Mesaj',
        };
    }
}
