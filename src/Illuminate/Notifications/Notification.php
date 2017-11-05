<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Events\ReflectOnEvent;
use Illuminate\Queue\SerializesModels;

class Notification implements ReflectOnEvent
{
    use HandlesEvents, SerializesModels;

    /**
     * The unique identifier for the notification.
     *
     * @var string
     */
    public $id;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
