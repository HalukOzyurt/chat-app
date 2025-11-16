<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * TypingStarted Event
 * 
 * Kullanıcı mesaj yazmaya başladığında tetiklenir
 * "... yazıyor" göstergesini diğer kullanıcılara gösterir
 */
class TypingStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;              // Yazan kullanıcı
    public int $conversationId;     // Hangi konuşmada yazıyor

    /**
     * Constructor
     * 
     * @param User $user Yazan kullanıcı
     * @param int $conversationId Konuşma ID
     */
    public function __construct(User $user, int $conversationId)
    {
        $this->user = $user;
        $this->conversationId = $conversationId;
    }

    /**
     * Broadcast channel'ları
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.' . $this->conversationId),
        ];
    }

    /**
     * Event ismi
     */
    public function broadcastAs(): string
    {
        return 'typing.started';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'conversation_id' => $this->conversationId,
        ];
    }
}
