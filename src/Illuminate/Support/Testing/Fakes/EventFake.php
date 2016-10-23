<?php

namespace Illuminate\Support\Testing\Fakes;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Contracts\Events\Dispatcher;

class EventFake implements Dispatcher
{
    /**
     * All of the events that have been fired keyed by type.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Assert if an event was fired based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertFired($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->fired($event, $callback)->count() > 0,
            "The expected [{$event}] event was not fired."
        );
    }

    /**
     * Determine if an event was fired based on a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotFired($event, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->fired($event, $callback)->count() === 0,
            "The unexpected [{$event}] event was fired."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function fired($event, $callback = null)
    {
        if (! $this->hasFired($event)) {
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
     * Determine if the given event has been fired.
     *
     * @param  string  $event
     * @return bool
     */
    public function hasFired($event)
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
     * @param  int  $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0)
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
     * Register an event and payload to be fired later.
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
     * Fire an event until the first non-null response is returned.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return mixed
     */
    public function until($event, $payload = [])
    {
        return $this->fire($event, $payload, true);
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
        $name = is_object($event) ? get_class($event) : (string) $event;

        $this->events[$name][] = func_get_args();
    }

    /**
     * Get the event that is currently firing.
     *
     * @return string
     */
    public function firing()
    {
        //
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
}
