<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Cache\Repository as Cache;

class CacheMutex implements Mutex
{
    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    public $cache;

    /**
     * Create a new overlapping strategy.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function create(Event $event)
    {
        return $this->cache->add(
            $event->mutexName(), true, $event->expiresAt
        );
    }

    /**
     * Determine if a mutex exists for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    public function exists(Event $event)
    {
        return $this->cache->has($event->mutexName());
    }

    /**
     * Clear the mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return void
     */
    public function forget(Event $event)
    {
        $this->cache->forget($event->mutexName());
    }
}
