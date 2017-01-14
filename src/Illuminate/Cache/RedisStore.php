<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Redis\Database as Redis;

class RedisStore extends TaggableStore implements Store
{
    /**
     * Default value of first byte of zlib compressed string.
     *
     * @var string
     */
    const COMPRESSION_ENABLED_FILE_HEADER = '78';

    /**
     * The Redis database connection.
     *
     * @var \Illuminate\Redis\Database
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $useCompression;

    /**
     * Create a new Redis store.
     *
     * @param  \Illuminate\Redis\Database  $redis
     * @param  string  $prefix
     * @param  string  $connection
     * @param  bool    $useCompression
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default', $useCompression = false)
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->setConnection($connection);
        $this->useCompression = $useCompression;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        if (! is_null($value = $this->connection()->get($this->prefix.$key))) {
            return $this->unserialize($value);
        }
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
        $return = [];

        $prefixedKeys = array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys);

        $values = $this->connection()->mget($prefixedKeys);

        foreach ($values as $index => $value) {
            $return[$keys[$index]] = $this->unserialize($value);
        }

        return $return;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $value = $this->serialize($value);

        $this->connection()->setex($this->prefix.$key, (int) max(1, $minutes * 60), $value);
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        $this->connection()->multi();

        foreach ($values as $key => $value) {
            $this->put($key, $value, $minutes);
        }

        $this->connection()->exec();
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
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
     * @param  mixed   $value
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
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->connection()->set($this->prefix.$key, $this->serialize($value));
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
     * @return void
     */
    public function flush()
    {
        $this->connection()->flushdb();
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        return new RedisTaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args()));
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Predis\ClientInterface
     */
    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Set the connection name to be used.
     *
     * @param  string  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Redis database instance.
     *
     * @return \Illuminate\Redis\Database
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
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }

    /**
     * Get whether or not compression is enabled.
     *
     * @return bool
     */
    public function getUseCompression()
    {
        return $this->useCompression;
    }

    /**
     * Enable or disable compression.
     *
     * @param bool $useCompression
     */
    public function setUseCompression($useCompression)
    {
        $this->useCompression = $useCompression;
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        if (is_numeric($value) || is_null($value)) {
            return $value;
        }
        return $this->useCompression ? gzcompress(serialize($value)) : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if (is_string($value)) {
            $isValueCompressed = bin2hex(mb_strcut($value, 0, 1)) === self::COMPRESSION_ENABLED_FILE_HEADER;
            return unserialize($isValueCompressed ? gzuncompress($value) : $value);
        }
        return $value;
    }
}
