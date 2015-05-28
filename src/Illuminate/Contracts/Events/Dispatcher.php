<?php namespace Illuminate\Contracts\Events;

interface Dispatcher {

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array  $events
	 * @param  mixed   $listener
	 * @param  int     $priority
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
	 * Fire an event until the first non-null response is returned.
	 *
	 * @param  string  $event
	 * @param  array   $payload
	 * @return mixed
	 */
	public function until($event, $payload = array());

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string|object  $event
	 * @param  mixed   $payload
	 * @param  bool    $halt
	 * @return array|null
	 */
	public function fire($event, $payload = array(), $halt = false);

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

}
