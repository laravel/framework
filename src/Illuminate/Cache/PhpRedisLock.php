<?php

namespace Illuminate\Cache;

use Illuminate\Redis\Connections\PhpRedisConnection;

class PhpRedisLock extends RedisLock
{
    /**
     * The phpredis factory implementation.
     *
     * @var \Illuminate\Redis\Connections\PhpredisConnection
     */
    protected $redis;

    /**
     * Create a new phpredis lock instance.
     *
     * @param  \Illuminate\Redis\Connections\PhpRedisConnection  $redis
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct(PhpRedisConnection $redis, string $name, int $seconds, ?string $owner = null)
    {
        parent::__construct($redis, $name, $seconds, $owner);
    }

    /**
     * {@inheritDoc}
     */
    public function release()
    {
        return (bool) $this->redis->eval(
            LuaScripts::releaseLock(),
            1,
            $this->name,
            ...$this->redis->pack([$this->owner])
        );
    }

    /**
     * Get the owner key, serialized and compressed.
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     *
     * @deprecated Will be removed in a later laravel version. Use PhpRedisConnection::pack.
     * @see \Illuminate\Redis\Connections\PhpRedisConnection::pack
     */
    protected function serializedAndCompressedOwner(): string
    {
        return $this->redis->pack([$this->owner])[0];
    }

    /**
     * Determine if compression is enabled.
     *
     * @return bool
     *
     * @deprecated Will be removed in a later laravel version. Use PhpRedisConnection::compressed.
     * @see \Illuminate\Redis\Connections\PhpRedisConnection::compressed
     */
    protected function compressed(): bool
    {
        return $this->redis->compressed();
    }

    /**
     * Determine if LZF compression is enabled.
     *
     * @return bool
     *
     * @deprecated Will be removed in a later laravel version. Use PhpRedisConnection::lzfCompressed.
     * @see \Illuminate\Redis\Connections\PhpRedisConnection::lzfCompressed
     */
    protected function lzfCompressed(): bool
    {
        return $this->redis->lzfCompressed();
    }

    /**
     * Determine if ZSTD compression is enabled.
     *
     * @return bool
     *
     * @deprecated Will be removed in a later laravel version. Use PhpRedisConnection::zstdCompressed.
     * @see \Illuminate\Redis\Connections\PhpRedisConnection::zstdCompressed
     */
    protected function zstdCompressed(): bool
    {
        return $this->redis->zstdCompressed();
    }

    /**
     * Determine if LZ4 compression is enabled.
     *
     * @return bool
     *
     * @deprecated Will be removed in a later laravel version. Use PhpRedisConnection::lz4Compressed.
     * @see \Illuminate\Redis\Connections\PhpRedisConnection::lz4Compressed
     */
    protected function lz4Compressed(): bool
    {
        return $this->redis->lz4Compressed();
    }
}
