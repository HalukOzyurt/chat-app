<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    use HasFactory;

    /**
     * Tablo adı (Laravel otomatik çoğul yapar, ama netlik için belirtiyoruz)
     */
    protected $table = 'conversation_participants';

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'conversation_id',   // Konuşma ID
        'user_id',           // Kullanıcı ID
        'role',              // Rol (admin/member)
        'joined_at',         // Katılma zamanı
        'left_at',           // Ayrılma zamanı
        'last_read_at',      // Son okuma zamanı
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_read_at' => 'datetime',
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Bu katılımcının ait olduğu konuşma
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Bu katılımcı olan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Kullanıcı admin mi?
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Kullanıcı hala bu konuşmada mı? (ayrılmamış mı?)
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Son okuma zamanını güncelle (mesaj okundu olarak işaretle)
     */
    public function markAsRead(): void
    {
        $this->update([
            'last_read_at' => now(),
        ]);
    }

    /**
     * Kullanıcıyı konuşmadan çıkar (soft delete)
     */
    public function leave(): void
    {
        $this->update([
            'left_at' => now(),
        ]);
    }

    /**
     * Kullanıcıyı tekrar konuşmaya ekle
     */
    public function rejoin(): void
    {
        $this->update([
            'left_at' => null,
            'joined_at' => now(),
        ]);
    }
}
