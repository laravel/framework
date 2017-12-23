<?php

namespace Illuminate\Console\Scheduling;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheSchedulingMutex implements SchedulingMutex
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
     * Attempt to obtain a scheduling mutex for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function create(Event $event, DateTimeInterface $time)
    {
        return $this->cache->add($event->mutexName().$time->format('Hi'), true, 60);
    }

    /**
     * Determine if a scheduling mutex exists for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeInterface  $time
     * @return bool
     */
    public function exists(Event $event, DateTimeInterface $time)
    {
        return $this->cache->has($event->mutexName().$time->format('Hi'));
    }
}
