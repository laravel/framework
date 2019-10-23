<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Redis\Factory as Redis;

class RedisStoreIgbinary extends RedisStore
{
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
        if (
            ! function_exists('igbinary_serialize') ||
            ! function_exists('igbinary_unserialize')
        ) {
            throw new \InvalidArgumentException('Igbinary extension not found');
        }

        parent::__construct($redis, $prefix, $connection);
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : igbinary_serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : igbinary_unserialize($value);
    }
}
