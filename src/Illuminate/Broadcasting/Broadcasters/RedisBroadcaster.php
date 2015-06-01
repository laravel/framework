<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Redis\Database as RedisDatabase;

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
}
