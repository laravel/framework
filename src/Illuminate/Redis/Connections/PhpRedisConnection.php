<?php

namespace Illuminate\Redis\Connections;

use Closure;

/**
 * @mixin \Redis
 */
class PhpRedisConnection extends Connection
{
    /**
     * Create a new PhpRedis connection.
     *
     * @param  \Redis  $client
     * @return void
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Returns the value of the given key.
     *
     * @param  string  $key
     * @return string|null
     */
    public function get($key)
    {
        $result = $this->client->get($key);

        return $result !== false ? $result : null;
    }

    /**
     * Get the values of all the given keys.
     *
     * @param  array  $keys
     * @return array
     */
    public function mget(array $keys)
    {
        return array_map(function ($value) {
            return $value !== false ? $value : null;
        }, $this->client->mget($keys));
    }

    /**
     * Set the string value in argument as value of the key.
     *
     * @param string  $key
     * @param mixed  $value
     * @param string|null  $expireResolution
     * @param int|null  $expireTTL
     * @param string|null  $flag
     * @return bool
     */
    public function set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
    {
        return $this->command('set', [
            $key,
            $value,
            $expireResolution ? [$expireResolution, $flag => $expireTTL] : null,
        ]);
    }

    /**
     * Removes the first count occurrences of the value element from the list.
     *
     * @param  string  $key
     * @param  int  $count
     * @param  $value  $value
     * @return int|false
     */
    public function lrem($key, $count, $value)
    {
        return $this->command('lrem', [$key, $value, $count]);
    }

    /**
     * Removes and returns a random element from the set value at key.
     *
     * @param  string  $key
     * @param  int|null  $count
     * @return mixed|false
     */
    public function spop($key, $count = null)
    {
        return $this->command('spop', [$key]);
    }

    /**
     * Add one or more members to a sorted set or update its score if it already exists.
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return int
     */
    public function zadd($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $_dictionary = [];

            foreach ($dictionary[0] as $member => $score) {
                $_dictionary[] = $score;
                $_dictionary[] = $member;
            }

            $dictionary = $_dictionary;
        }

        return $this->client->zadd($key, ...$dictionary);
    }

    /**
     * Execute commands in a pipeline.
     *
     * @param  callable  $callback
     * @return array|\Redis
     */
    public function pipeline(callable $callback = null)
    {
        $pipeline = $this->client()->pipeline();

        return is_null($callback)
            ? $pipeline
            : tap($pipeline, $callback)->exec();
    }

    /**
     * Execute commands in a transaction.
     *
     * @param  callable  $callback
     * @return array|\Redis
     */
    public function transaction(callable $callback = null)
    {
        $transaction = $this->client()->multi();

        return is_null($callback)
            ? $transaction
            : tap($transaction, $callback)->exec();
    }

    /**
     * Evaluate a LUA script serverside, from the SHA1 hash of the script instead of the script itself.
     *
     * @param  string  $script
     * @param  int  $numkeys
     * @param  mixed  $arguments
     * @return mixed
     */
    public function evalsha($script, $numkeys, ...$arguments)
    {
        return $this->command('evalsha', [
            $this->script('load', $script), $arguments, $numkeys,
        ]);
    }

    /**
     * Proxy a call to the eval function of PhpRedis.
     *
     * @param  array  $parameters
     * @return mixed
     */
    protected function proxyToEval(array $parameters)
    {
        return $this->command('eval', [
            isset($parameters[0]) ? $parameters[0] : null,
            array_slice($parameters, 2),
            isset($parameters[1]) ? $parameters[1] : null,
        ]);
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        $this->client->subscribe((array) $channels, function ($redis, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        $this->client->psubscribe((array) $channels, function ($redis, $pattern, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
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
        //
    }

    /**
     * Execute a raw command.
     *
     * @param  array  $parameters
     * @return mixed
     */
    public function executeRaw(array $parameters)
    {
        return $this->command('rawCommand', $parameters);
    }

    /**
     * Disconnects from the Redis instance.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->client->close();
    }

    /**
     * Pass other method calls down to the underlying client.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $method = strtolower($method);

        if ($method == 'eval') {
            return $this->proxyToEval($parameters);
        }

        if ($method == 'zrangebyscore' || $method == 'zrevrangebyscore') {
            $parameters = array_map(function ($parameter) {
                return is_array($parameter) ? array_change_key_case($parameter) : $parameter;
            }, $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
