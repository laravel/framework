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
        return $this->command(
            'set',
            $key,
            $value,
            $expireResolution ? [$expireResolution, $flag => $expireTTL] : null
        );
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
        return $this->command('lrem', $key, $value, $count);
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
        return $this->command('spop', $key, $count);
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

        foreach ($membersAndScoresDictionary as $member => $score) {
            $arguments[] = $score;
            $arguments[] = $member;
        }

        return $this->command('zadd', ...$arguments);
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
        return $this->command('evalsha', [$this->script('load', $script), $arguments, $parameters]);
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
     * Pass other method calls down to the underlying client.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'eval') {
            return $this->proxyToEval($parameters);
        }

        $arrayMethods = [
            'hdel', 'hstrlen',
            'lpush', 'rpush',
            'scan', 'hscan', 'sscan', 'zscan',
            'sadd', 'srem', 'sdiff', 'sinter', 'sunion', 'sdiffstore', 'sinterstore', 'sunionstore',
        ];

        if (is_array($parameters) && in_array($method, $arrayMethods)) {
            $this->command($method, ...$parameters);
        }

        return parent::__call($method, $parameters);
    }
}
