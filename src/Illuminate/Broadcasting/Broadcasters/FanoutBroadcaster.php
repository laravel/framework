<?php namespace Illuminate\Broadcasting\Broadcasters;

use Fanout\Fanout;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class FanoutBroadcaster implements Broadcaster
{

    /**
     * The Fanout SDK instance.
     *
     * @var Fanout
     */
    protected $fanout;

    /**
     * Create a new broadcaster instance.
     *
     * @param  Fanout  $fanout
     * @return void
     */
    public function __construct(Fanout $fanout)
    {
        $this->fanout = $fanout;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $payload = ['event' => $event, 'data' => $payload];

        foreach ($channels as $channel) {
            $this->fanout->publish($channel, $payload);
        }
    }
}
