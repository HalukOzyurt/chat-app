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
 * CallEnded Event
 * 
 * Arama sonlandırıldığında tetiklenir
 */
class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;  // Arama bilgisi

    /**
     * Constructor
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Broadcast channel'ları
     */
    public function broadcastOn(): array
    {
        return [
            // Hem arayan hem aranan kullanıcıya bildir
            new PrivateChannel('user.' . $this->call->caller_id),
            new PrivateChannel('user.' . $this->call->receiver_id),
        ];
    }

    /**
     * Event ismi
     */
    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'status' => $this->call->status,  // completed, rejected, missed
            'duration' => $this->call->duration,  // Süre (saniye)
        ];
    }
}
