<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MessageSent Event
 * 
 * Yeni mesaj gönderildiğinde bu event tetiklenir
 * WebSocket üzerinden konuşmadaki tüm kullanıcılara broadcast edilir
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Gönderilen mesaj
     */
    public Message $message;

    /**
     * Constructor - Event oluşturulduğunda çalışır
     * 
     * @param Message $message Gönderilen mesaj
     */
    public function __construct(Message $message)
    {
        // Mesajı public property olarak sakla (broadcast edilebilmesi için)
        $this->message = $message;
        
        // Mesajın gönderen bilgisini yükle (N+1 problemi önleme)
        $this->message->load(['sender:id,name,avatar']);
    }

    /**
     * Bu event hangi channel'lara broadcast edilecek?
     * 
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel - Sadece konuşmadaki kullanıcılar dinleyebilir
            new PresenceChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Broadcast edilecek event ismi
     * Frontend'de bu isimle dinlenecek
     * 
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Broadcast edilecek veri
     * Frontend'e bu veri gönderilecek
     * 
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar_url,
            ],
            'message_type' => $this->message->message_type,
            'content' => $this->message->content,
            'file_path' => $this->message->file_url,
            'file_name' => $this->message->file_name,
            'created_at' => $this->message->created_at->toISOString(),
            'is_edited' => $this->message->is_edited,
        ];
    }
}
