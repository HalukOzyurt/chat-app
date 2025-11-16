<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CallInitiated Event
 * 
 * Arama başlatıldığında tetiklenir
 * Aranan kullanıcıya bildirim gönderilir
 */
class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;  // Arama bilgisi

    /**
     * Constructor
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
        $this->call->load(['caller:id,name,avatar', 'receiver:id,name,avatar']);
    }

    /**
     * Broadcast channel'ları
     */
    public function broadcastOn(): array
    {
        return [
            // Aranan kullanıcının private channel'ı
            new PrivateChannel('user.' . $this->call->receiver_id),
        ];
    }

    /**
     * Event ismi
     */
    public function broadcastAs(): string
    {
        return 'call.initiated';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'conversation_id' => $this->call->conversation_id,
            'caller' => [
                'id' => $this->call->caller->id,
                'name' => $this->call->caller->name,
                'avatar' => $this->call->caller->avatar_url,
            ],
            'call_type' => $this->call->call_type,  // audio veya video
            'status' => $this->call->status,
        ];
    }
}
