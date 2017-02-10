<?php

namespace Illuminate\Redis\Connections;

use Closure;

class PhpRedisConnection extends Connection
{
    /**
     * Create a new Predis connection.
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
     * Removes the first count occurences of the value element from the list.
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
     * @param  array  $membersAndScoresDictionary
     * @return int
     */
    public function zadd($key, array $membersAndScoresDictionary)
    {
        $arguments = [];

        foreach ($membersAndScoresDictionary as $score => $member) {
            $arguments[] = $score;
            $arguments[] = $member;
        }

        return $this->client->zadd($key, ...$arguments);
    }

    /**
     * Evaluate a LUA script from the SHA1 hash of the script.
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
     * Evaluate a script and retunr its result.
     *
     * @param  string  $script
     * @param  int  $numberOfKeys
     * @param  dynamic  $arguments
     * @return mixed
     */
    public function eval($script, $numberOfKeys, ...$arguments)
    {
        return $this->client->eval($script, $arguments, $numberOfKeys);
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
}
