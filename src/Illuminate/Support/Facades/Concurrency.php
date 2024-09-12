<?php

namespace Illuminate\Support\Facades;

use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Foundation\Defer\DeferredCallback;

/**
 *
 * @method static array run(\Closure|array $tasks)
 * @method static DeferredCallback defer(\Closure|array $tasks)
 *
 * @see \Illuminate\Concurrency\ConcurrencyManager
 */
class Concurrency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ConcurrencyManager::class;
    }
}
