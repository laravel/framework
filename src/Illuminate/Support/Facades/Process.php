<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
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
