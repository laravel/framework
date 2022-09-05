<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Contracts\ProcessResult run(iterable|string $arguments)
 * @method static \Illuminate\Console\Process\PendingProcess dd()
 * @method static \Illuminate\Console\Process\PendingProcess dump()
 * @method static \Illuminate\Console\Process\PendingProcess forever()
 * @method static \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method static \Illuminate\Console\Process\PendingProcess timeout(int $seconds)
 * @method static \Illuminate\Console\Process\PendingProcess stub(callable $callback)
 * @method static \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
 *
 * @see \Illuminate\Console\Process\Factory
 */
class Process extends Facade
{
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
