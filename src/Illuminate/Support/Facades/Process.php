<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Contracts\ProcessResult run(iterable|string $command = [])
 * @method static \Illuminate\Console\Process\PendingProcess dd()
 * @method static \Illuminate\Console\Process\PendingProcess dump()
 * @method static \Illuminate\Console\Process\PendingProcess forever()
 * @method static \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method static \Illuminate\Console\Process\PendingProcess timeout(int $seconds)
 * @method static \Illuminate\Console\Process\PendingProcess stub(callable $callback)
 * @method static \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
 * @method static \Illuminate\Support\Collection pool(callable $callback)
 * @method static \Illuminate\Console\Process\Factory assertRan(callable|string $command)
 * @method static \Illuminate\Console\Process\Factory assertRanInOrder(array $commands)
 *
 * @see \Illuminate\Console\Process\Factory
 */
class Process extends Facade
{
    /**
     * Register a stub callable that will intercept processes and be able to return stub processes results.
     *
     * @param  (iterable<string, callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult>)|(callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult)|null  $callback
     * @return \Illuminate\Console\Process\Factory
     */
    public static function fake($callback = null)
    {
        return tap(static::getFacadeRoot(), fn ($fake) => static::swap($fake->fake($callback)));
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
