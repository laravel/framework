<?php

namespace Illuminate\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @method static void listen(string | array $events, mixed $listener) Register an event listener with the dispatcher.
 * @method static bool hasListeners(string $eventName) Determine if a given event has listeners.
 * @method static void push(string $event, array $payload) Register an event and payload to be fired later.
 * @method static void flush(string $event) Flush a set of pushed events.
 * @method static void subscribe(object | string $subscriber) Register an event subscriber with the dispatcher.
 * @method static array|null until(string | object $event, mixed $payload) Fire an event until the first non-null response is returned.
 * @method static array|null fire(string | object $event, mixed $payload, bool $halt) Fire an event and call the listeners.
 * @method static array|null dispatch(string | object $event, mixed $payload, bool $halt) Fire an event and call the listeners.
 * @method static array getListeners(string $eventName) Get all of the listeners for a given event name.
 * @method static \Closure makeListener(\Closure | string $listener, bool $wildcard) Register an event listener with the dispatcher.
 * @method static \Closure createClassListener(string $listener, bool $wildcard) Create a class based listener using the IoC container.
 * @method static void forget(string $event) Remove a set of listeners from the dispatcher.
 * @method static void forgetPushed() Forget all of the pushed listeners.
 * @method static $this setQueueResolver(callable $resolver) Set the queue resolver implementation.
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
