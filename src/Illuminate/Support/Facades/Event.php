<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @method static void listen(string | array $events, $listener)
 * @method static bool hasListeners(string $eventName)
 * @method static void subscribe(object | string $subscriber)
 * @method static array|null until(string | object $event, $payload = [])
 * @method static array|null dispatch(string | object $event, $payload = [], bool $halt = false)
 * @method static void push(string $event, array $payload = [])
 * @method static void flush(string $event)
 * @method static void forget(string $event)
 * @method static void forgetPushed()
 *
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
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events';
    }
}
