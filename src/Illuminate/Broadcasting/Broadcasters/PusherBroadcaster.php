<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Pusher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PusherBroadcaster extends AbstractBroadcaster implements Broadcaster
{
    /**
     * The Pusher SDK instance.
     *
     * @var \Pusher
     */
    protected $pusher;

    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Return the valid Pusher authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    protected function validAuthenticationResponse($request, $result)
    {
        if (Str::startsWith($request->channel_name, 'private')) {
            return $this->pusher->socket_auth($request->channel_name, $request->socket_id);
        } else {
            return $this->pusher->presence_auth(
                $request->channel_name, $request->socket_id, $request->user()->id, $result
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $socket = Arr::pull($payload, 'socket');

        $this->pusher->trigger($channels, $event, $payload, $socket);
    }

    /**
     * Get the Pusher SDK instance.
     *
     * @return \Pusher
     */
    public function getPusher()
    {
        return $this->pusher;
    }
}
