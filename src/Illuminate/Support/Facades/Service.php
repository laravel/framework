<?php

namespace Illuminate\Support\Facades;

use Illuminate\Services\ServiceManager;

/**
 * @method static \Illuminate\Services\ServiceClient service(string $name)
 * @method static \Illuminate\Http\Client\PendingRequest http(string $name)
 * @method static \Illuminate\Services\ServiceManager fake(array $responses = [])
 * @method static void assertCalled(string $target, callable|null $callback = null)
 *
 * @see \Illuminate\Services\ServiceManager
 */
class Service extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ServiceManager::class;
    }
}
