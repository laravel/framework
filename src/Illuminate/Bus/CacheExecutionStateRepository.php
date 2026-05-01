<?php

namespace Illuminate\Bus;

use Illuminate\Bus\JobSequence\ExecutionState;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;

class CacheExecutionStateRepository implements ExecutionStateRepository
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
    public function getExecutionState(string $id): ?ExecutionState
    {
        return $this->getCache()->get($id);
    }

    #[\Override]
    public function saveExecutionState(string $id, $executionState, $ttl): void
    {
        $this->getCache()->put($id, $executionState, $ttl);
    }

    #[\Override]
    public function clearExecutionState(string $id): void
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
