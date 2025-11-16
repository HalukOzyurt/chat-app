<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UserOnline Event
 * 
 * Kullanıcı çevrimiçi olduğunda tetiklenir
 * Tüm kullanıcılara broadcast edilir
 */
class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;  // Çevrimiçi olan kullanıcı

    /**
     * Constructor
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Broadcast channel'ları
     * Public channel - Herkes dinleyebilir
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
        return 'user.online';
    }

    /**
     * Broadcast verisi
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->avatar_url,
            'is_online' => true,
        ];
    }
}
