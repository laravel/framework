<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Process\PendingProcess dd()
 * @method static \Illuminate\Console\Process\PendingProcess dump()
 * @method static \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
 * @method static \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method static \Illuminate\Console\Process\SymfonyProcessResult run(iterable|string $arguments)
 *
 * @see \Illuminate\Console\Process\Factory
 */
class Process extends Facade
{
    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param  \Closure|array  $callback
     * @return \Illuminate\Console\Process\Factory
     */
    public static function fake($callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
