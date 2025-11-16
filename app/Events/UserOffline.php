<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UserOffline Event
 * 
 * Kullanıcı çevrimdışı olduğunda tetiklenir
 * Tüm kullanıcılara broadcast edilir
 */
class UserOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;         // Çevrimdışı olan kullanıcı ID
    public string $userName;    // Kullanıcı adı

    /**
     * Constructor
     */
    public function __construct(User $user)
    {
        $this->userId = $user->id;
        $this->userName = $user->name;
    }

    /**
     * Broadcast channel'ları
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('online-users'),
        ];
    }

    /**
     * Event ismi
     */
    public function broadcastAs(): string
    {
        return 'user.offline';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'is_online' => false,
        ];
    }
}
