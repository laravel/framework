<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Process\PendingProcess dd()
 * @method static \Illuminate\Console\Process\PendingProcess dump()
 * @method static \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
 * @method static \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method static \Illuminate\Console\Process\Response run(iterable|string $arguments)
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
