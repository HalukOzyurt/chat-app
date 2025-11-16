<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    use HasFactory;

    /**
     * Tablo adı
     */
    protected $table = 'message_reads';

    /**
     * Kitlesel atanabilir alanlar
     */
    protected $fillable = [
        'message_id',   // Hangi mesaj
        'user_id',      // Kim okudu
        'read_at',      // Ne zaman okudu
    ];

    /**
     * Otomatik tip dönüşümü
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * İLİŞKİLER (RELATIONSHIPS)
     */

    /**
     * Okunan mesaj
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Mesajı okuyan kullanıcı
     * Ters bire-çok ilişki (Inverse One-to-Many)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
