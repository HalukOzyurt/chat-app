<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MessageRead Event
 * 
 * Mesaj okunduğunda tetiklenir
 * Mesajı gönderen kişiye "okundu" bilgisi gönderilir
 */
class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;    // Okunan mesaj
    public User $reader;        // Okuyan kullanıcı

    /**
     * Constructor
     */
    public function __construct(Message $message, User $reader)
    {
        $this->message = $message;
        $this->reader = $reader;
    }

    /**
     * Broadcast channel'ları
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Event ismi
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'read_by_user_id' => $this->reader->id,
            'read_by_user_name' => $this->reader->name,
            'read_at' => now()->toISOString(),
        ];
    }
}
