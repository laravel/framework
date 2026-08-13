<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Redis;
use RedisException;
use Throwable;

/**
 * @mixin \Redis
 */
class PhpRedisConnection extends Connection implements ConnectionContract
{
    use PacksPhpRedisValues;

    /**
     * The commands that may be safely retried after reconnecting.
     *
     * @var list<string>
     */
    protected const RETRYABLE_COMMANDS = [
        'bitcount',
        'bitpos',
        'dbsize',
        'dump',
        'exists',
        'geodist',
        'geohash',
        'geopos',
        'geosearch',
        'get',
        'getbit',
        'getrange',
        'hexists',
        'hget',
        'hgetall',
        'hkeys',
        'hlen',
        'hmget',
        'hmset',
        'hscan',
        'hstrlen',
        'hvals',
        'keys',
        'lindex',
        'llen',
        'lpos',
        'lrange',
        'mget',
        'mset',
        'ping',
        'pttl',
        'randomkey',
        'scan',
        'scard',
        'sdiff',
        'sinter',
        'sismember',
        'smembers',
        'smismember',
        'srandmember',
        'sscan',
        'strlen',
        'sunion',
        'time',
        'ttl',
        'type',
        'xinfo',
        'xlen',
        'xpending',
        'xrange',
        'xrevrange',
        'zcard',
        'zcount',
        'zlexcount',
        'zmscore',
        'zrange',
        'zrank',
        'zrevrank',
        'zscan',
        'zscore',
    ];

    /**
     * The error messages that indicate a transient connection failure.
     *
     * @var list<string>
     */
    protected const TRANSIENT_ERROR_MESSAGES = [
        "can't write against a read only replica",
        'connection closed',
        'connection lost',
        'connection refused',
        'connection timed out',
        'error while reading',
        'failed while reconnecting',
        'getaddrinfo',
        'loading',
        'name or service not known',
        'php_network_getaddresses',
        'read error on connection',
        'readonly',
        'socket',
        'went away',
    ];

    /**
     * The connection creation callback.
     *
     * @var callable
     */
    protected $connector;

    /**
     * The connection configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new PhpRedis connection.
     *
     * @param  \Redis  $client
     * @param  callable|null  $connector
     * @param  array  $config
     */
    public function __construct($client, ?callable $connector = null, array $config = [])
    {
        $this->client = $client;
        $this->config = $config;
        $this->connector = $connector;
    }

    /**
     * Returns the value of the given key.
     *
     * @param  string  $key
     * @return string|null
     */
    public function get($key)
    {
        $result = $this->command('get', [$key]);

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
        }, $this->command('mget', [$keys]));
    }

    /**
     * Set the string value in the argument as the value of the key.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $expireResolution
     * @param  int|null  $expireTTL
     * @param  string|null  $flag
     * @return bool
     */
    public function set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
    {
        return $this->command('set', [
            $key,
            $value,
            $expireResolution ? [$flag, $expireResolution => $expireTTL] : null,
        ]);
    }

    /**
     * Set the given key if it doesn't exist.
     *
     * @param  string  $key
     * @param  string  $value
     * @return int
     */
    public function setnx($key, $value)
    {
        return (int) $this->command('setnx', [$key, $value]);
    }

    /**
     * Get the value of the given hash fields.
     *
     * @param  string  $key
     * @param  mixed  ...$dictionary
     * @return array
     */
    public function hmget($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $dictionary = $dictionary[0];
        }

        return array_values($this->command('hmget', [$key, $dictionary]));
    }

    /**
     * Set the given hash fields to their respective values.
     *
     * @param  string  $key
     * @param  mixed  ...$dictionary
     * @return int
     */
    public function hmset($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $dictionary = $dictionary[0];
        } else {
            $input = new Collection($dictionary);

            $dictionary = $input->nth(2)->combine($input->nth(2, 1))->toArray();
        }

        return $this->command('hmset', [$key, $dictionary]);
    }

    /**
     * Set the given hash field if it doesn't exist.
     *
     * @param  string  $hash
     * @param  string  $key
     * @param  string  $value
     * @return int
     */
    public function hsetnx($hash, $key, $value)
    {
        return (int) $this->command('hsetnx', [$hash, $key, $value]);
    }

    /**
     * Removes the first count occurrences of the value element from the list.
     *
     * @param  string  $key
     * @param  int  $count
     * @param  mixed  $value
     * @return int|false
     */
    public function lrem($key, $count, $value)
    {
        return $this->command('lrem', [$key, $value, $count]);
    }

    /**
     * Removes and returns the first element of the list stored at key.
     *
     * @param  mixed  ...$arguments
     * @return array|null
     */
    public function blpop(...$arguments)
    {
        $result = $this->command('blpop', $arguments);

        return empty($result) ? null : $result;
    }

    /**
     * Removes and returns the last element of the list stored at key.
     *
     * @param  mixed  ...$arguments
     * @return array|null
     */
    public function brpop(...$arguments)
    {
        $result = $this->command('brpop', $arguments);

        return empty($result) ? null : $result;
    }

    /**
     * Removes and returns a random element from the set value at key.
     *
     * @param  string  $key
     * @param  int|null  $count
     * @return mixed|false
     */
    public function spop($key, $count = 1)
    {
        return $this->command('spop', func_get_args());
    }

    /**
     * Add one or more members to a sorted set or update its score if it already exists.
     *
     * @param  string  $key
     * @param  mixed  ...$dictionary
     * @return int
     */
    public function zadd($key, ...$dictionary)
    {
        if (is_array(end($dictionary))) {
            foreach (array_pop($dictionary) as $member => $score) {
                $dictionary[] = $score;
                $dictionary[] = $member;
            }
        }

        $options = [];

        foreach (array_slice($dictionary, 0, 3) as $i => $value) {
            if (in_array($value, ['nx', 'xx', 'ch', 'incr', 'gt', 'lt', 'NX', 'XX', 'CH', 'INCR', 'GT', 'LT'], true)) {
                $options[] = $value;

                unset($dictionary[$i]);
            }
        }

        return $this->command('zadd', array_merge([$key], [$options], array_values($dictionary)));
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
    public function zrangebyscore($key, $min, $max, $options = [])
    {
        if (isset($options['limit']) && ! array_is_list($options['limit'])) {
            $options['limit'] = [
                $options['limit']['offset'],
                $options['limit']['count'],
            ];
        }

        return $this->command('zRangeByScore', [$key, $min, $max, $options]);
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
        if (isset($options['limit']) && ! array_is_list($options['limit'])) {
            $options['limit'] = [
                $options['limit']['offset'],
                $options['limit']['count'],
            ];
        }

        return $this->command('zRevRangeByScore', [$key, $min, $max, $options]);
    }

    /**
     * Find the intersection between sets and store in a new set.
     *
     * @param  string  $output
     * @param  array  $keys
     * @param  array  $options
     * @return int
     */
    public function zinterstore($output, $keys, $options = [])
    {
        return $this->command('zinterstore', [$output, $keys,
            $options['weights'] ?? null,
            $options['aggregate'] ?? 'sum',
        ]);
    }

    /**
     * Find the union between sets and store in a new set.
     *
     * @param  string  $output
     * @param  array  $keys
     * @param  array  $options
     * @return int
     */
    public function zunionstore($output, $keys, $options = [])
    {
        return $this->command('zunionstore', [$output, $keys,
            $options['weights'] ?? null,
            $options['aggregate'] ?? 'sum',
        ]);
    }

    /**
     * Scans all keys based on options.
     *
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function scan($cursor, $options = [])
    {
        return $this->retryOnTransientErrors($this->commandRetries('scan'), function () use ($cursor, $options) {
            $result = $this->client->scan($cursor,
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ($result === false) {
                $result = [];
            }

            return $cursor === 0 && empty($result) ? false : [$cursor, $result];
        });
    }

    /**
     * Scans the given set for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function zscan($key, $cursor, $options = [])
    {
        return $this->retryOnTransientErrors($this->commandRetries('zscan'), function () use ($key, $cursor, $options) {
            $result = $this->client->zscan($key, $cursor,
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ($result === false) {
                $result = [];
            }

            return $cursor === 0 && empty($result) ? false : [$cursor, $result];
        });
    }

    /**
     * Scans the given hash for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function hscan($key, $cursor, $options = [])
    {
        return $this->retryOnTransientErrors($this->commandRetries('hscan'), function () use ($key, $cursor, $options) {
            $result = $this->client->hscan($key, $cursor,
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ($result === false) {
                $result = [];
            }

            return $cursor === 0 && empty($result) ? false : [$cursor, $result];
        });
    }

    /**
     * Scans the given set for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return mixed
     */
    public function sscan($key, $cursor, $options = [])
    {
        return $this->retryOnTransientErrors($this->commandRetries('sscan'), function () use ($key, $cursor, $options) {
            $result = $this->client->sscan($key, $cursor,
                $options['match'] ?? '*',
                $options['count'] ?? 10
            );

            if ($result === false) {
                $result = [];
            }

            return $cursor === 0 && empty($result) ? false : [$cursor, $result];
        });
    }

    /**
     * Execute commands in a pipeline.
     *
     * @param  callable|null  $callback
     * @return \Redis|array
     */
    public function pipeline(?callable $callback = null)
    {
        return $this->retryOnTransientErrors((int) ($this->config['command_retries'] ?? 0), function () use ($callback) {
            $pipeline = $this->client()->pipeline();

            return is_null($callback)
                ? $pipeline
                : tap($pipeline, $callback)->exec();
        });
    }

    /**
     * Execute commands in a transaction.
     *
     * @param  callable|null  $callback
     * @return \Redis|array
     */
    public function transaction(?callable $callback = null)
    {
        return $this->retryOnTransientErrors((int) ($this->config['command_retries'] ?? 0), function () use ($callback) {
            $transaction = $this->client()->multi();

            return is_null($callback)
                ? $transaction
                : tap($transaction, $callback)->exec();
        });
    }

    /**
     * Evaluate a LUA script serverside, from the SHA1 hash of the script instead of the script itself.
     *
     * @param  string  $script
     * @param  int  $numkeys
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public function evalsha($script, $numkeys, ...$arguments)
    {
        return $this->command('evalsha', [
            $this->script('load', $script), $arguments, $numkeys,
        ]);
    }

    /**
     * Evaluate a script and return its result.
     *
     * @param  string  $script
     * @param  int  $numberOfKeys
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public function eval($script, $numberOfKeys, ...$arguments)
    {
        return $this->command('eval', [$script, $arguments, $numberOfKeys]);
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
        $this->retryOnTransientErrors((int) ($this->config['command_retries'] ?? 0), function () use ($channels, $callback) {
            $this->client->subscribe((array) $channels, function ($redis, $channel, $message) use ($callback) {
                $callback($message, $channel);
            });
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
        $this->retryOnTransientErrors((int) ($this->config['command_retries'] ?? 0), function () use ($channels, $callback) {
            $this->client->psubscribe((array) $channels, function ($redis, $pattern, $channel, $message) use ($callback) {
                $callback($message, $channel);
            });
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
     * Flush the selected Redis database.
     *
     * @return mixed
     */
    public function flushdb()
    {
        $arguments = func_get_args();

        if (strtoupper((string) ($arguments[0] ?? null)) === 'ASYNC') {
            return $this->command('flushdb', [true]);
        }

        return $this->command('flushdb');
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
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \RedisException
     */
    public function command($method, array $parameters = [])
    {
        return $this->retryOnTransientErrors(
            $this->commandRetries($method, $parameters),
            fn () => parent::command($method, $parameters)
        );
    }

    /**
     * Run the given callback, retrying when a transient connection failure occurs.
     *
     * @param  int  $retries
     * @param  callable  $callback
     * @return mixed
     *
     * @throws \RedisException
     */
    protected function retryOnTransientErrors($retries, callable $callback)
    {
        $attempts = 0;
        $delay = 0;

        while (true) {
            try {
                return $callback();
            } catch (RedisException $e) {
                if (! $this->causedByTransientError($e)) {
                    throw $e;
                }

                $this->reconnect();

                if ($attempts >= $retries) {
                    throw $e;
                }

                $delay = $this->backoff($attempts++, $delay);
            }
        }
    }

    /**
     * Determine the number of times the given command may be retried.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return int
     */
    protected function commandRetries($method, array $parameters = [])
    {
        return max(
            $this->isRetryable($method, $parameters) ? 1 : 0,
            (int) ($this->config['command_retries'] ?? 0),
        );
    }

    /**
     * Determine whether the command may be safely retried.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return bool
     */
    protected function isRetryable($method, array $parameters)
    {
        $method = strtolower($method);

        if ($method === 'set') {
            return ! isset($parameters[2]);
        }

        return in_array($method, static::RETRYABLE_COMMANDS, true);
    }

    /**
     * Determine if the given exception was caused by a transient connection failure.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByTransientError(Throwable $e)
    {
        return Str::contains($e->getMessage(), static::TRANSIENT_ERROR_MESSAGES, true);
    }

    /**
     * Reconnect to the Redis server, replacing the underlying client.
     *
     * @return void
     */
    protected function reconnect()
    {
        if (! $this->connector) {
            return;
        }

        try {
            $this->disconnect();
        } catch (Throwable) {
            //
        }

        try {
            $this->client = call_user_func($this->connector);
        } catch (RedisException) {
            //
        } catch (Throwable $e) {
            if (! $this->causedByTransientError($e)) {
                throw $e;
            }
        }
    }

    /**
     * Pause execution before the next retry attempt.
     *
     * @param  int  $attempt
     * @param  int  $previousDelay
     * @return int
     */
    protected function backoff($attempt, $previousDelay)
    {
        $delay = $this->backoffDelay($attempt, $previousDelay);

        if ($delay > 0) {
            Sleep::usleep($delay * 1000);
        }

        return $delay;
    }

    /**
     * Calculate the backoff delay in milliseconds for the given retry attempt.
     *
     * Mirrors the backoff algorithms provided by PhpRedis using the same
     * "backoff_algorithm", "backoff_base", and "backoff_cap" options.
     *
     * @param  int  $attempt
     * @param  int  $previousDelay
     * @return int
     */
    protected function backoffDelay($attempt, $previousDelay)
    {
        $base = (int) ($this->config['backoff_base'] ?? 0);
        $cap = (int) ($this->config['backoff_cap'] ?? 1000);

        if ($base <= 0) {
            return 0;
        }

        $exponential = min($cap, $base * (1 << min($attempt, 10)));

        return match ($this->backoffAlgorithm()) {
            Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER => min($cap, random_int(min($base, $previousDelay * 3), max($base, $previousDelay * 3))),
            Redis::BACKOFF_ALGORITHM_FULL_JITTER => random_int(0, $exponential),
            Redis::BACKOFF_ALGORITHM_EQUAL_JITTER => intdiv($exponential, 2) + intdiv(random_int(0, $exponential), 2),
            Redis::BACKOFF_ALGORITHM_EXPONENTIAL => $exponential,
            Redis::BACKOFF_ALGORITHM_UNIFORM => min($cap, random_int(0, $base)),
            Redis::BACKOFF_ALGORITHM_CONSTANT => min($cap, $base),
            default => $attempt > 0 ? min($cap, $base) : min($cap, random_int(0, $base)),
        };
    }

    /**
     * Get the backoff algorithm used when retrying commands.
     *
     * @return int
     */
    protected function backoffAlgorithm()
    {
        $algorithm = $this->config['backoff_algorithm'] ?? Redis::BACKOFF_ALGORITHM_DEFAULT;

        if (is_int($algorithm)) {
            return $algorithm;
        }

        return match ($algorithm) {
            'decorrelated_jitter' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
            'full_jitter' => Redis::BACKOFF_ALGORITHM_FULL_JITTER,
            'equal_jitter' => Redis::BACKOFF_ALGORITHM_EQUAL_JITTER,
            'exponential' => Redis::BACKOFF_ALGORITHM_EXPONENTIAL,
            'uniform' => Redis::BACKOFF_ALGORITHM_UNIFORM,
            'constant' => Redis::BACKOFF_ALGORITHM_CONSTANT,
            default => Redis::BACKOFF_ALGORITHM_DEFAULT,
        };
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
        return parent::__call(strtolower($method), $parameters);
    }
}
