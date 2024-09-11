<?php

namespace Illuminate\Support\Facades;

use Illuminate\Concurrency\ConcurrencyManager;

/**
 * @method static \Illuminate\Foundation\Defer\DeferredCallback defer(\Closure|array $tasks)
 * @method static \Illuminate\Contracts\Concurrency\Driver driver(string|null $name = null)
 * @method static string getDefaultInstance()
 * @method static void getInstanceConfig(string $name)
 * @method static void setDefaultInstance(string $name)
 * @method static array run(\Closure|array $tasks)
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
