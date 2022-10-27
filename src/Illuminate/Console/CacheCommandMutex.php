<?php

declare(strict_types=1);

namespace Illuminate\Console;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Factory as Cache;

class CacheCommandMutex implements CommandMutex
{
    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store = null;

    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * Create a new command mutex
     *
     * @param \Illuminate\Contracts\Cache\Factory $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function create($command)
    {
        return $this->cache->store($this->store)->add(
            $this->commandMutexName($command),
            true,
            CarbonInterval::hour(),
        );
    }

    public function exists($command)
    {
        return $this->cache->store($this->store)->has(
            $this->commandMutexName($command)
        );
    }

    /**
     * @param Command $command
     * @return string
     */
    protected function commandMutexName($command): string
    {
        return 'framework'.DIRECTORY_SEPARATOR.'command-'.$command->getName();
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param string|null $store
     * @return $this
     */
    public function useStore($store): static
    {
        $this->store = $store;

        return $this;
    }
}
