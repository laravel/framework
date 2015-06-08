<?php

namespace Illuminate\Contracts\Events;

interface Dispatcher
{
    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @param  int  $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0);

    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName);

    /**
     * Register an event and payload to be fired later.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = []);

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber);

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return mixed
     */
    public function until($event, $payload = []);

    /**
     * Flush a set of pushed events.
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event);

    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false);

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName);

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  mixed  $listener
     * @return mixed
     */
    public function makeListener($listener);

    /**
     * Create a class based listener using the IoC container.
     *
     * @param  mixed  $listener
     * @return \Closure
     */
    public function createClassListener($listener);

    /**
     * Get the event that is currently firing.
     *
     * @return string
     */
    public function firing();

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event);

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed();

    /**
     * Set the queue resolver implementation.
     *
     * @param  callable  $resolver
     * @return $this
     */
    public function setQueueResolver(callable $resolver);
}
