<?php

namespace Illuminate\Support\Facades;

/**
 * @method static static start()
 * @method static static attempts(int $times)
 * @method static static onSuccess(\Closure $callback)
 * @method static static onFailure(\Closure $callback)
 * @method static mixed run(\Closure $callback)
 */
class Transaction extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'transaction.builder';
    }
}
