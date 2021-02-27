<?php

namespace Illuminate\Cache;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Redis;
use UnexpectedValueException;

class PhpRedisLock extends RedisLock
{
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
            $this->serializedAndCompressedOwner()
        );
    }

    protected function serializedAndCompressedOwner(): string
    {
        $client = $this->redis->client();

        /* If a serialization mode such as "php" or "igbinary" and/or a
         * compression mode such as "lzf" or "zstd" is enabled, the owner
         * must be serialized and/or compressed by us, because phpredis does
         * not do this for the eval command.
         *
         * Name must not be modified!
         */
        $owner = $client->_serialize($this->owner);

        /* Once the phpredis extension exposes a compress function like the
         * above `_serialize()` function, we should switch to it to guarantee
         * consistency in the way the extension serializes and compresses to
         * avoid the need to check each compression option ourselves.
         *
         * @see https://github.com/phpredis/phpredis/issues/1938
         */
        if ($this->compressed()) {
            if ($this->lzfCompressed()) {
                $owner = \lzf_compress($owner);
            } elseif ($this->zstdCompressed()) {
                $owner = \zstd_compress($owner, $client->getOption(Redis::OPT_COMPRESSION_LEVEL));
            } elseif ($this->lz4Compressed()) {
                $owner = \lz4_compress($owner, $client->getOption(Redis::OPT_COMPRESSION_LEVEL));
            } else {
                throw new UnexpectedValueException(sprintf(
                    'Unknown phpredis compression in use (%d). Unable to release lock.',
                    $client->getOption(Redis::OPT_COMPRESSION)
                ));
            }
        }

        return $owner;
    }

    protected function compressed(): bool
    {
        return $this->redis->client()->getOption(Redis::OPT_COMPRESSION) !== Redis::COMPRESSION_NONE;
    }

    protected function lzfCompressed(): bool
    {
        return defined('Redis::COMPRESSION_LZF') &&
            $this->redis->client()->getOption(Redis::OPT_COMPRESSION) === Redis::COMPRESSION_LZF;
    }

    protected function zstdCompressed(): bool
    {
        return defined('Redis::COMPRESSION_ZSTD') &&
            $this->redis->client()->getOption(Redis::OPT_COMPRESSION) === Redis::COMPRESSION_ZSTD;
    }

    protected function lz4Compressed(): bool
    {
        return defined('Redis::COMPRESSION_LZ4') &&
            $this->redis->client()->getOption(Redis::OPT_COMPRESSION) === Redis::COMPRESSION_LZ4;
    }
}
