<?php

namespace Illuminate\Support\Facades;

use Illuminate\Concurrency\ConcurrencyManager;

/**
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
