<?php

namespace Illuminate\Support\Facades;

use Psr\Log\LoggerInterface;
use Illuminate\Support\Testing\Fakes\LogFake;

/**
 * @see \Illuminate\Log\Writer
 */
class Log extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new LogFake);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggerInterface::class;
    }
}
