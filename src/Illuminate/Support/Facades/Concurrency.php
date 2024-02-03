<?php

namespace Illuminate\Support\Facades;

use Illuminate\Concurrency\Factory;

/**
 * @see \Illuminate\Concurrency\Factory
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
        return Factory::class;
    }
}
