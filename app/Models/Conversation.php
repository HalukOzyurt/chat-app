<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'type',          // Konuşma türü (private/group)
        'name',          // Grup adı
        'avatar',        // Grup resmi
        'created_by',    // Oluşturan kullanıcı ID'si
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Konuşmaya katılan kullanıcılar
     * Çoka-çok ilişki (Many-to-Many) - conversation_participants tablosu üzerinden
     * N+1 problemini önlemek için eager loading ile kullanılmalı: ->with('participants')
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'left_at', 'last_read_at'])
            ->withTimestamps()
            ->wherePivot('left_at', null);  // Sadece aktif katılımcılar
    }

    /**
     * Tüm katılımcı kayıtları (ayrılanlar dahil)
     * Bire-çok ilişki (One-to-Many)
     */
    public function allParticipants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Konuşmadaki mesajlar
     * Bire-çok ilişki (One-to-Many)
     * N+1 problemini önlemek için: ->with('messages.sender')
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->whereNull('deleted_at');
    }

    /**
     * Son mesaj (en son gönderilen mesaj)
     * Bire-bir ilişki (One-to-One)
     * Konuşma listesinde göstermek için kullanılır
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)
            ->whereNull('deleted_at')
            ->latest();  // En son mesajı getir
    }

    /**
     * Konuşmadaki aramalar
     * Bire-çok ilişki (One-to-Many)
     */
    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Konuşmayı oluşturan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * YARDIMCI METODLAR (HELPER METHODS)
     */

    /**
     * Bu bir grup konuşması mı?
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Bu bir özel (birebir) konuşma mı?
     */
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Belirtilen kullanıcı bu konuşmanın katılımcısı mı?
     * 
     * @param int|User $user Kullanıcı ID veya User modeli
     */
    public function hasParticipant($user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Konuşmaya kullanıcı ekle
     * 
     * @param int|User $user Eklenecek kullanıcı
     * @param string $role Rol (admin/member)
     */
    public function addParticipant($user, string $role = 'member'): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $this->participants()->attach($userId, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Konuşmadan kullanıcı çıkar
     * 
     * @param int|User $user Çıkarılacak kullanıcı
     */
    public function removeParticipant($user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Soft delete - kullanıcıyı tamamen silmek yerine left_at'i güncelle
        $this->allParticipants()
            ->where('user_id', $userId)
            ->update(['left_at' => now()]);
    }

    /**
     * Konuşma başlığını getir
     * Grup ise grup adı, özel konuşma ise diğer kullanıcının adı
     * 
     * @param User|null $currentUser Mevcut kullanıcı (özel konuşmalarda gerekli)
     */
    public function getTitle(?User $currentUser = null): string
    {
        if ($this->isGroup()) {
            return $this->name ?? 'İsimsiz Grup';
        }

        // Özel konuşmada diğer kullanıcının adını getir
        if ($currentUser) {
            $otherUser = $this->participants()
                ->where('user_id', '!=', $currentUser->id)
                ->first();
            
            return $otherUser ? $otherUser->name : 'Bilinmeyen Kullanıcı';
        }

        return 'Özel Konuşma';
    }

    /**
     * Konuşma resmini getir
     * Grup ise grup resmi, özel konuşma ise diğer kullanıcının profil resmi
     * 
     * @param User|null $currentUser Mevcut kullanıcı
     */
    public function getAvatarUrl(?User $currentUser = null): string
    {
        if ($this->isGroup() && $this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        if ($this->isPrivate() && $currentUser) {
            $otherUser = $this->participants()
                ->where('user_id', '!=', $currentUser->id)
                ->first();
            
            return $otherUser ? $otherUser->avatar_url : '';
        }

        return 'https://ui-avatars.com/api/?name=Group&background=10B981&color=fff';
    }

    /**
     * Kullanıcının bu konuşmadaki okunmamış mesaj sayısını getir
     * 
     * @param User $user Kullanıcı
     */
    public function unreadMessagesCount(User $user): int
    {
        // Kullanıcının son okuma zamanını al
        $participant = $this->allParticipants()
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return 0;
        }

        // Son okuma zamanından sonraki mesajları say (kullanıcının kendi mesajları hariç)
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->when($participant->last_read_at, function ($query) use ($participant) {
                $query->where('created_at', '>', $participant->last_read_at);
            })
            ->count();
    }
}
