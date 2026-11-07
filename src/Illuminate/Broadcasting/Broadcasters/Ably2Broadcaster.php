<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Ably\PubSub\Exceptions\AblyException;
use Ably\PubSub\Models\Message as AblyMessage;
use Ably\PubSub\PubSubHttpClient;
use Illuminate\Broadcasting\BroadcastException;

/**
 * @template TClient of \Ably\PubSub\PubSubHttpClient
 *
 * @extends \Illuminate\Broadcasting\Broadcasters\AblyBroadcaster<TClient>
 */
class Ably2Broadcaster extends AblyBroadcaster
{
    /**
     * Create a new broadcaster instance.
     *
     * @param  TClient  $ably
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
    #[\Override]
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
    #[\Override]
    protected function buildAblyMessage($event, array $payload = [])
    {
        return tap(new AblyMessage, function ($message) use ($event, $payload) {
            $message->name = $event;
            $message->data = $payload;
            $message->connectionKey = data_get($payload, 'socket');
        });
    }
}
