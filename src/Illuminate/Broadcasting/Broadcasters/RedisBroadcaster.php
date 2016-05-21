<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Support\Str;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Redis\Database as RedisDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedisBroadcaster implements Broadcaster
{
    /**
     * The Redis instance.
     *
     * @var \Illuminate\Contracts\Redis\Database
     */
    protected $redis;

    /**
     * The Redis connection to use for broadcasting.
     *
     * @var string
     */
    protected $connection;

    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Illuminate\Contracts\Redis\Database  $redis
     * @param  string  $connection
     * @return void
     */
    public function __construct(RedisDatabase $redis, $connection = null)
    {
        $this->redis = $redis;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $connection = $this->redis->connection($this->connection);

        $payload = json_encode(['event' => $event, 'data' => $payload]);

        foreach ($channels as $channel) {
            $connection->publish($channel, $payload);
        }
    }

    /**
     * Register a channel authenticator.
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function auth($channel, callable $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function check($request)
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
        return is_array($result) ? $result : json_encode($result);
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
}
