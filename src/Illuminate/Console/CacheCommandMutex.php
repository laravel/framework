<?php

declare(strict_types=1);

namespace Illuminate\Console;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Factory as Cache;

class CacheCommandMutex implements CommandMutex
{
    public string|null $store = null;

    public Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function create(Command $command): bool
    {
        return $this->cache->store($this->store)->add(
            $this->commandMutexName($command),
            true,
            CarbonInterval::hour(),
        );
    }

    public function exists(Command $command): bool
    {
        return $this->cache->store($this->store)->has(
            $this->commandMutexName($command)
        );
    }

    protected function commandMutexName(Command $command): string
    {
        return 'framework'.DIRECTORY_SEPARATOR.'command-'.$command->getName();
    }
    public function useStore(string|null $store): static
    {
        $this->store = $store;

        return $this;
    }
}
