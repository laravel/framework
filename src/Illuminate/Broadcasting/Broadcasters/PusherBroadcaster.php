<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Pusher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PusherBroadcaster extends Broadcaster
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
     * @param  \Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        if (Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            ! $request->user()) {
            throw new HttpException(403);
        }

        return parent::verifyUserCanAccessChannel(
            $request, str_replace(['private-', 'presence-'], '', $request->channel_name)
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
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
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $socket = Arr::pull($payload, 'socket');

        $this->pusher->trigger($this->formatChannels($channels), $event, $payload, $socket);
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
