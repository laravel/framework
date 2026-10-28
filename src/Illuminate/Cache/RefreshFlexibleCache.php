<?php

namespace Illuminate\Cache;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\SerializableClosure\SerializableClosure;

class RefreshFlexibleCache implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * The callback that resolves the fresh value.
     *
     * @var callable|\Laravel\SerializableClosure\SerializableClosure
     */
    public $callback;

    /**
     * Create a new job instance.
     *
     * @param  array{ 0: \DateTimeInterface|\DateInterval|int, 1: \DateTimeInterface|\DateInterval|int }  $ttl
     * @param  array{ seconds?: int, owner?: string }|null  $lock
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public string $store,
        public string $key,
        public array $ttl,
        callable $callback,
        public ?array $lock,
        public mixed $created,
        public array $tags = [],
        public int $uniqueFor = 0,
    ) {
        $this->callback = $callback instanceof Closure ? new SerializableClosure($callback) : $callback;
    }

    /**
     * Refresh the stale flexible cache value.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function handle(Factory $cache)
    {
        /** @var \Illuminate\Cache\Repository $repository */
        $repository = $cache->store($this->store);

        if ($this->tags !== []) {
            $repository = $repository->tags($this->tags);
        }

        $repository->refreshFlexible(
            $this->key,
            $this->ttl,
            $this->callback instanceof SerializableClosure ? $this->callback->getClosure() : $this->callback,
            $this->lock,
            $this->created,
        );
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->store.':'.implode(',', $this->tags).':'.$this->key;
    }

    /**
     * Get the cache repository that should hold the unique job lock.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return Container::getInstance()->make(Factory::class)->store($this->store);
    }
}
