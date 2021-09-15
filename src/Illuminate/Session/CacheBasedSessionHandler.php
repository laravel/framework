<?php

namespace Illuminate\Session;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use SessionHandlerInterface;

class CacheBasedSessionHandler implements SessionHandlerInterface
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The number of minutes to store the data in the cache.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cache driven handler instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $minutes
     * @return void
     */
    public function __construct(CacheContract $cache, $minutes)
    {
        $this->cache = $cache;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|false
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        return $this->cache->get($sessionId, '');
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        return $this->cache->put($sessionId, $data, $this->minutes * 60);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        return $this->cache->forget($sessionId);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Get the underlying cache repository.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getCache()
    {
        return $this->cache;
    }
}
