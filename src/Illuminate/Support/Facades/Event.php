<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return void
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
    }

    /**
     * Replace the bound instance with a fake for the given callable only.
     *
     * @param  callable  $callable
     * @param  array|string  $eventsToFake
     * @return callable
     */
    public static function fakeFor(callable $callable, array $eventsToFake = [])
    {
        $initialDispatcher = static::getFacadeRoot();

        static::fake($eventsToFake);

        return tap($callable(), function () use ($initialDispatcher) {
            Model::setEventDispatcher($initialDispatcher);

            static::swap($initialDispatcher);
        });
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events';
    }
}
