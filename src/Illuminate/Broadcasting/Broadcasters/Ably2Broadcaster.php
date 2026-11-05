<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Ably\PubSub\Exceptions\AblyException;
use Ably\PubSub\Models\Message as AblyMessage;
use Ably\PubSub\PubSubHttpClient;
use Illuminate\Broadcasting\BroadcastException;

class Ably2Broadcaster extends AblyBroadcaster
{
    /**
     * The Ably Pub/Sub HTTP client instance.
     *
     * @var \Ably\PubSub\PubSubHttpClient
     */
    protected $ably;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Ably\PubSub\PubSubHttpClient  $ably
     */
    public function __construct(PubSubHttpClient $ably)
    {
        $this->ably = $ably;
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     *
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            foreach ($this->formatChannels($channels) as $channel) {
                $this->ably->channels->get($channel)->publish(
                    $this->buildAblyMessage($event, $payload)
                );
            }
        } catch (AblyException $e) {
            throw new BroadcastException(
                sprintf('Ably error: %s', $e->getMessage())
            );
        }
    }

    /**
     * Build an Ably message object for broadcasting.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return \Ably\PubSub\Models\Message
     */
    protected function buildAblyMessage($event, array $payload = [])
    {
        return tap(new AblyMessage, function ($message) use ($event, $payload) {
            $message->name = $event;
            $message->data = $payload;
            $message->connectionKey = data_get($payload, 'socket');
        });
    }

    /**
     * Get the underlying Ably SDK instance.
     *
     * @return \Ably\PubSub\PubSubHttpClient
     */
    public function getAbly()
    {
        return $this->ably;
    }

    /**
     * Set the underlying Ably SDK instance.
     *
     * @param  \Ably\PubSub\PubSubHttpClient  $ably
     * @return void
     */
    public function setAbly($ably)
    {
        $this->ably = $ably;
    }
}
