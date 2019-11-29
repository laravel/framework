<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;
use Predis\Command\ServerFlushDatabase;
use Predis\Connection\Aggregate\ClusterInterface;

/**
 * @mixin \RedisClient\RedisClient
 */
class RedisClientConnection extends Connection implements ConnectionContract
{
    use Commands;

    /**
     * The RedisClient client.
     *
     * @var \RedisClient\RedisClient
     */
    protected $client;

    /**
     * The Redis prefix.
     *
     * @var string|null
     */
    protected $prefix;

    /**
     * Create a new RedisClient connection.
     *
     * @param  \RedisClient\RedisClient  $client
     * @param  string|null  $prefix
     * @return void
     */
    public function __construct($client, $prefix = null)
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    /**
     * Disconnects from the Redis instance.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->client->quit();
    }

    /**
     * Add one or more members to a sorted set or update its score if it already exists.
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return int
     */
    public function zrem($key, ...$members)
    {
        $key = $this->applyPrefix($key);

        return $this->client->zrem($key, $members);
    }

    /**
     * Return elements with score between $min and $max.
     *
     * @param  string  $key
     * @param  mixed  $min
     * @param  mixed  $max
     * @param  array  $options
     * @return array
     */
    public function zrevrangebyscore($key, $min, $max, $options = [])
    {
        if (isset($options['limit'])) {
            $limit = [
                $options['limit']['offset'],
                $options['limit']['count'],
            ];
        }

        return $this->client->zRevRangeByScore($key, $min, $max, $options['withscores'] ?? false, $limit ?? null);
    }

    /**
     * Execute commands in a pipeline.
     *
     * @param  callable|null  $callback
     * @return \RedisClient\RedisClient|array
     */
    public function pipeline(callable $callback = null)
    {
        return $this->client->pipeline($callback);
    }

    /**
     * Execute commands in a transaction.
     *
     * @param  callable|null  $callback
     * @return \RedisClient\RedisClient|array
     */
    public function transaction(callable $callback = null)
    {
        $this->client->multi();

        return is_null($callback)
            ? $this->client
            : tap($this->client, $callback)->exec();
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        $loop = $this->pubSubLoop();

        call_user_func_array([$loop, $method], (array) $channels);

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                call_user_func($callback, $message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    /**
     * Flush the selected Redis database.
     *
     * @return void
     */
    public function flushdb()
    {
        if (! $this->client->clusters() instanceof ClusterInterface) {
            return $this->command('flushdb');
        }

        foreach ($this->getConnection() as $node) {
            $node->executeCommand(new ServerFlushDatabase);
        }
    }

    /**
     * Execute a raw command.
     *
     * @param  array  $parameters
     * @return mixed
     */
    public function executeRaw(array $parameters)
    {
        return $this->client->executeRaw($parameters);
    }

    /**
     * Apply prefix to the given key if necessary.
     *
     * @param  string  $key
     * @return string
     */
    protected function applyPrefix($key)
    {
        $prefix = (string) $this->prefix;

        return $prefix.$key;
    }
}
