<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Pusher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PusherBroadcaster implements Broadcaster
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
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        $channel = str_replace(['private-', 'presence-'], '', $request->channel_name);

        foreach ($this->channels as $pattern => $callback) {
            if (! Str::is($pattern, $channel)) {
                continue;
            }

            $parameters = $this->extractAuthParameters($pattern, $channel);

            if ($result = $callback($request->user(), ...$parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new HttpException(403);
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
     * Extract the parameters from the given pattern and channel.
     *
     * @param  string  $pattern
     * @param  string  $channel
     * @return array
     */
    protected function extractAuthParameters($pattern, $channel)
    {
        if (! Str::contains($pattern, '*')) {
            return [];
        }

        $pattern = str_replace('\*', '([^\.]+)', preg_quote($pattern));

        if (preg_match('/^'.$pattern.'/', $channel, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Register a channel authenticator.
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function channel($channel, callable $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
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
