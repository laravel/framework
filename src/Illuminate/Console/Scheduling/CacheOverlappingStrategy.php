<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Cache\Repository as Cache;

class CacheOverlappingStrategy implements OverlappingStrategy
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function prevent(Event $event)
    {
        return $this->cache->add($event->mutexName(), true, 1440);
    }

    public function overlaps(Event $event)
    {
        return $this->cache->has($event->mutexName());
    }

    public function reset(Event $event)
    {
        $this->cache->forget($event->mutexName());
    }
}
