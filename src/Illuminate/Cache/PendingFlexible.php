<?php

namespace Illuminate\Cache;

class PendingFlexible
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Cache\Repository
     */
    protected $repository;

    /**
     * The lock configuration.
     *
     * @var array{ seconds: int, owner: string|null }
     */
    protected $lock;

    /**
     * Create a new pending flexible cache instance.
     *
     * @param  \Illuminate\Cache\Repository  $repository
     * @param  array{ seconds: int, owner: string|null }  $lock
     */
    public function __construct(Repository $repository, array $lock)
    {
        $this->repository = $repository;
        $this->lock = $lock;
    }

    /**
     * Retrieve an item from the cache, preventing concurrent cold-cache recomputation
     * and coordinating stale-cache refreshes.
     *
     * @template TCacheValue
     *
     * @param  \UnitEnum|string  $key
     * @param  array{ 0: \DateTimeInterface|\DateInterval|int, 1: \DateTimeInterface|\DateInterval|int }  $ttl
     * @param  (callable(): TCacheValue)  $callback
     * @param  bool  $alwaysDefer
     * @return TCacheValue
     */
    public function flexible($key, $ttl, callable $callback, $alwaysDefer = false)
    {
        return $this->repository->flexible(
            $key,
            $ttl,
            $callback,
            $this->lock,
            $alwaysDefer,
            preventStampede: true,
        );
    }
}
