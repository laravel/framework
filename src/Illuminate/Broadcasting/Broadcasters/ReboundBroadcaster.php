<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Redis\Database as RedisDatabase;

class ReboundBroadcaster extends AbstractBroadcaster implements Broadcaster
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
     * Create a new broadcaster instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Redis\Database  $redis
     * @param  string  $connection
     * @return void
     */
    public function __construct($app, RedisDatabase $redis, $connection = null)
    {
        parent::__construct($app);
        $this->redis = $redis;
        $this->connection = $connection;
    }

    /**
     * Return the valid Rebound authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return ['status' => 'success', 'user_id' => $request->user()->id, 'user_info' => $result];
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
     * Return a response to requests to save socket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function rememberSocket($request)
    {
        if ($request->user()) {
            return ['status' => 'success', 'user_id' => $request->user()->id];
        }

        return ['status' => 'success'];
    }
}
