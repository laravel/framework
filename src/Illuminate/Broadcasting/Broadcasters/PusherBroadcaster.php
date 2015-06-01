<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Pusher;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class PusherBroadcaster implements Broadcaster
{
    /**
     * The Pusher SDK instance.
     *
     * @var \Pusher
     */
    protected $pusher;

    /**
     * Create a new broadcaster instance.
     *
     * @param  Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->pusher->trigger($channels, $event, $payload);
    }
}
