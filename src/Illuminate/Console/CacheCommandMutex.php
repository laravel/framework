<?php

namespace Illuminate\Console;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Factory as Cache;

class CacheCommandMutex implements CommandMutex
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store = null;

    /**
     * Create a new command mutex.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function create($command)
    {
        return $this->cache->store($this->store)->add(
            $this->commandMutexName($command),
            true,
            method_exists($command, 'isolationLockExpiresAt')
                    ? $command->isolationLockExpiresAt()
                    : CarbonInterval::hour(),
        );
    }

    /**
     * Determine if a command mutex exists for the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function exists($command)
    {
        return $this->cache->store($this->store)->has(
            $this->commandMutexName($command)
        );
    }

    /**
     * Release the mutex for the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function forget($command)
    {
        return $this->cache->store($this->store)->forget(
            $this->commandMutexName($command)
        );
    }

    /**
     * @param  \Illuminate\Console\Command  $command
     * @return string
     */
    protected function commandMutexName($command)
    {
        return 'framework'.DIRECTORY_SEPARATOR.'command-'.$command->getName();
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param  string|null  $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}
