<?php

namespace Illuminate\Support\Testing\Fakes;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Contracts\Events\Dispatcher;

class EventFake implements Dispatcher
{
    /**
     * All of the events that have been dispatched keyed by type.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Assert if an event was dispatched based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertDispatched($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() > 0,
            "The expected [{$event}] event was not dispatched."
        );
    }

    /**
     * Determine if an event was dispatched based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() === 0,
            "The unexpected [{$event}] event was dispatched."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($event, $callback = null)
    {
        if (! $this->hasDispatched($event)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->events[$event])->filter(function ($arguments) use ($callback) {
            return $callback(...$arguments);
        })->flatMap(function ($arguments) {
            return $this->mapEventArguments($arguments);
        });
    }

    /**
     * Determine if the given event has been dispatched.
     *
     * @param  string  $event
     * @return bool
     */
    public function hasDispatched($event)
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
    }

    /**
     * Map the "fire" method arguments for inspection.
     *
     * @param  array  $arguments
     * @return array
     */
    protected function mapEventArguments($arguments)
    {
        // If the fired event was just a simple string event, we will return the event
        // name as the key with the array of arguments as the value. Otherwise this
        // event was an object event and we will return this object as the value.
        if (is_string($arguments[0])) {
            return [$arguments[0] => $arguments[1]];
        } else {
            return [get_class($arguments[0]) => $arguments[0]];
        }
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        //
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        //
    }

    /**
     * Register an event and payload to be dispatched later.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        //
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        //
    }

    /**
     * Flush a set of pushed events.
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        //
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->dispatch($event, $payload, $halt);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $name = is_object($event) ? get_class($event) : (string) $event;

        $this->events[$name][] = func_get_args();
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        //
    }

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
        //
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @return void
     */
    public function until($event, $payload = [])
    {
        //
    }
}
