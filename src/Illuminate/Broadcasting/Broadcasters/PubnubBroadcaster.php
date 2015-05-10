<?php namespace Illuminate\Broadcasting\Broadcasters;

use Pubnub\Pubnub;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class PubnubBroadcaster implements Broadcaster
{

    /**
     * The PubNub SDK instance.
     *
     * @var Pubnub
     */
    protected $pubnub;

    /**
     * Create a new broadcaster instance.
     *
     * @param  Pubnub  $pubnub
     * @return void
     */
    public function __construct(Pubnub $pubnub)
    {
        $this->pubnub = $pubnub;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $payload = ['event' => $event, 'data' => $payload];

        foreach ($channels as $channel) {
            $this->pubnub->publish($channel, $payload);
        }
    }
}
