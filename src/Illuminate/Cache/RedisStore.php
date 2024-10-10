<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class RedisStore extends TaggableStore implements LockProvider
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection instance that should be used to manage locks.
     *
     * @var string
     */
    protected $connection;

    /**
     * The name of the connection that should be used for locks.
     *
     * @var string
     */
    protected $lockConnection;

    /**
     * Create a new Redis store.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string  $prefix
     * @param  string  $connection
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->setConnection($connection);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->connection()->get($this->prefix.$key);

        return ! is_null($value) ? $this->unserialize($value) : null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        if (count($keys) === 0) {
            return [];
        }

        $results = [];

        $values = $this->connection()->mget(array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys));

        foreach ($values as $index => $value) {
            $results[$keys[$index]] = ! is_null($value) ? $this->unserialize($value) : null;
        }

        return $results;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return (bool) $this->connection()->setex(
            $this->prefix.$key, (int) max(1, $seconds), $this->serialize($value)
        );
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        $serializedValues = [];

        foreach ($values as $key => $value) {
            $serializedValues[$this->prefix.$key] = $this->serialize($value);
        }

        $this->connection()->multi();

        $manyResult = null;

        foreach ($serializedValues as $key => $value) {
            $result = (bool) $this->connection()->setex(
                $key, (int) max(1, $seconds), $value
            );

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        $this->connection()->exec();

        return $manyResult ?: false;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool) $this->connection()->eval(
            $lua, 1, $this->prefix.$key, $this->serialize($value), (int) max(1, $seconds)
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return $this->connection()->incrby($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->connection()->decrby($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return (bool) $this->connection()->set($this->prefix.$key, $this->serialize($value));
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        $lockName = $this->prefix.$name;

        $lockConnection = $this->lockConnection();

        if ($lockConnection instanceof PhpRedisConnection) {
            return new PhpRedisLock($lockConnection, $lockName, $seconds, $owner);
        }

        return new RedisLock($lockConnection, $lockName, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return (bool) $this->connection()->del($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->connection()->flushdb();

        return true;
    }

    /**
     * Remove all expired tag set entries.
     *
     * @return void
     */
    public function flushStaleTags()
    {
        foreach ($this->currentTags()->chunk(1000) as $tags) {
            $this->tags($tags->all())->flushStale();
        }
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        return new RedisTaggedCache(
            $this, new RedisTagSet($this, is_array($names) ? $names : func_get_args())
        );
    }

    /**
     * Get a collection of all of the cache tags currently being used.
     *
     * @param  int  $chunkSize
     * @return \Illuminate\Support\LazyCollection
     */
    protected function currentTags($chunkSize = 1000)
    {
        $connection = $this->connection();

        // Connections can have a global prefix...
        $connectionPrefix = match (true) {
            $connection instanceof PhpRedisConnection => $connection->_prefix(''),
            $connection instanceof PredisConnection => $connection->getOptions()->prefix ?: '',
            default => '',
        };

        $defaultCursorValue = match (true) {
            $connection instanceof PhpRedisConnection && version_compare(phpversion('redis'), '6.1.0', '>=') => null,
            default => '0',
        };

        $prefix = $connectionPrefix.$this->getPrefix();

        return LazyCollection::make(function () use ($connection, $chunkSize, $prefix, $defaultCursorValue) {
            $cursor = $defaultCursorValue;

            do {
                [$cursor, $tagsChunk] = $connection->scan(
                    $cursor,
                    ['match' => $prefix.'tag:*:entries', 'count' => $chunkSize]
                );

                if (! is_array($tagsChunk)) {
                    break;
                }

                $tagsChunk = array_unique($tagsChunk);

                if (empty($tagsChunk)) {
                    continue;
                }

                foreach ($tagsChunk as $tag) {
                    yield $tag;
                }
            } while (((string) $cursor) !== $defaultCursorValue);
        })->map(fn (string $tagKey) => Str::match('/^'.preg_quote($prefix, '/').'tag:(.*):entries$/', $tagKey));
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the Redis connection instance that should be used to manage locks.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function lockConnection()
    {
        return $this->redis->connection($this->lockConnection ?? $this->connection);
    }

    /**
     * Specify the name of the connection that should be used to store data.
     *
     * @param  string  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Specify the name of the connection that should be used to manage locks.
     *
     * @param  string  $connection
     * @return $this
     */
    public function setLockConnection($connection)
    {
        $this->lockConnection = $connection;

        return $this;
    }

    /**
     * Get the Redis database instance.
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) && ! in_array($value, [INF, -INF]) && ! is_nan($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
