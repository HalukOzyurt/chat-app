<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kitlesel atanabilir alanlar (Mass Assignment için)
     * Bu alanlara toplu veri ataması yapılabilir
     */
    protected $fillable = [
        'name',              // Kullanıcı adı
        'email',             // E-posta
        'password',          // Şifre
        'avatar',            // Profil resmi
        'status_message',    // Durum mesajı
        'is_online',         // Çevrimiçi durumu
        'last_seen_at',      // Son görülme
    ];

    /**
     * Gizlenmesi gereken alanlar
     * JSON çıktısında bu alanlar görünmez (güvenlik için)
     */
    protected $hidden = [
        'password',          // Şifre asla görünmemeli
        'remember_token',    // Oturum token'ı da gizli
    ];

    /**
     * Otomatik tip dönüşümü (Type Casting)
     * Veritabanından gelen verileri belirtilen tiplere dönüştürür
     */
    protected $casts = [
        'email_verified_at' => 'datetime',  // String'den datetime'a
        'last_seen_at' => 'datetime',       // String'den datetime'a
        'is_online' => 'boolean',           // 0/1'den true/false'a
        'password' => 'hashed',             // Laravel 10+ otomatik hash
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     * Bu model diğer modellerle nasıl ilişkili?
     */

    /**
     * Kullanıcının katıldığı konuşmalar
     * Çoka-çok ilişki (Many-to-Many) - conversation_participants tablosu üzerinden
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'left_at', 'last_read_at'])  // Ara tablodaki ek alanlar
            ->withTimestamps()  // created_at ve updated_at
            ->wherePivot('left_at', null);  // Sadece aktif konuşmaları getir (ayrılmamış)
    }

    /**
     * Kullanıcının katılımcı olduğu tüm konuşma kayıtları
     * Bire-çok ilişki (One-to-Many)
     */
    public function participations()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Kullanıcının gönderdiği mesajlar
     * Bire-çok ilişki (One-to-Many)
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Kullanıcının okuduğu mesajlar
     * Çoka-çok ilişki (Many-to-Many) - message_reads tablosu üzerinden
     */
    public function readMessages()
    {
        return $this->belongsToMany(Message::class, 'message_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Kullanıcının yaptığı aramalar
     * Bire-çok ilişki (One-to-Many)
     */
    public function calledCalls()
    {
        return $this->hasMany(Call::class, 'caller_id');
    }

    /**
     * Kullanıcıya yapılan aramalar
     * Bire-çok ilişki (One-to-Many)
     */
    public function receivedCalls()
    {
        return $this->hasMany(Call::class, 'receiver_id');
    }

    /**
     * Kullanıcının tüm bildirimleri
     * Bire-çok ilişki (One-to-Many)
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Kullanıcının oluşturduğu grup konuşmaları
     * Bire-çok ilişki (One-to-Many)
     */
    public function createdConversations()
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Kullanıcıyı çevrimiçi olarak işaretle
     */
    public function markAsOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Kullanıcıyı çevrimdışı olarak işaretle
     */
    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Kullanıcının okunmamış mesaj sayısını getir
     */
    public function unreadMessagesCount(): int
    {
        // Kullanıcının katıldığı tüm konuşmalardaki mesajları say
        // Ancak kullanıcının kendisinin gönderdiklerini ve okuduklarını hariç tut
        return Message::whereHas('conversation.participants', function ($query) {
                $query->where('user_id', $this->id)
                    ->whereNull('left_at');
            })
            ->where('sender_id', '!=', $this->id)  // Kendi mesajları değil
            ->whereDoesntHave('readBy', function ($query) {  // Henüz okunmamış
                $query->where('user_id', $this->id);
            })
            ->count();
    }

    /**
     * Profil resmi URL'sini getir
     * Eğer yoksa varsayılan avatar kullan
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=4F46E5&color=fff';
    }
}
