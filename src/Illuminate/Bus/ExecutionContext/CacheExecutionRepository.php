<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;

use function Illuminate\Support\enum_value;

class CacheExecutionRepository implements ExecutionRepositoryContract
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
    public function find(mixed $id)
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        return $this->getCache()->get($this->determineCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    #[\Override]
    public function create(mixed $id, $ttl = null)
    {
        $executionState = $id instanceof ExecutionState ? $id : new ExecutionState($id);

        $this->getCache()->put($this->determineCacheKey($id), $executionState, $ttl);

        return $executionState;
    }

    #[\Override]
    public function saveStep($state, string $name, $ttl = null): void
    {
        $this->getCache()->put($this->determineCacheKey($state->id()), $state, $ttl);
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    #[\Override]
    public function delete($id): void
    {
        $this->getCache()->forget($this->determineCacheKey($id));
    }

    protected function getCache(): Repository
    {
        return $this->cache->store($this->store);
    }

    public function setStore($store): static
    {
        $this->store = enum_value($store);

        return $this;
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @return string
     */
    protected function determineCacheKey($id): string
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        return 'execution:'.$id;
    }
}
