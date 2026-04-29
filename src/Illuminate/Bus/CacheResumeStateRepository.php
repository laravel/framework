<?php

namespace Illuminate\Bus;

use Illuminate\Bus\Workflow\ResumeState;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;

class CacheResumeStateRepository implements ResumeStateRepository
{
    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    protected $store = null;

    /**
     * @param  Factory  $cache  The cache factory instance
     */
    public function __construct(
        protected Factory $cache,
    ) {
    }

    #[\Override]
    public function getResumeState(string $id): ?ResumeState
    {
        return $this->getCache()->get($id);
    }

    #[\Override]
    public function saveCheckpoint(string $id, $resumeState, $ttl): void
    {
        $this->getCache()->put($id, $resumeState, $ttl);
    }

    #[\Override]
    public function clearResumeState(string $id): void
    {
        $this->getCache()->forget($id);
    }

    protected function getCache(): Repository
    {
        return $this->cache->store($this->store);
    }

    public function setStore($store): static
    {
        $this->store = $store;

        return $this;
    }

    protected function determineCacheKey(string $id): string
    {
        return 'workflow:'.$id;
    }

}
