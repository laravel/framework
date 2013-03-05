<?php namespace Illuminate\Events;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class Event extends SymfonyEvent {

	/**
	 * The event payload array.
	 *
	 * @var array
	 */
	protected $payload;

	/**
	 * Create a new event instance.
	 *
	 * @param  mixed  $payload
	 * @return void
	 */
	public function __construct(array $payload = array())
	{
		$this->payload = $payload;
	}

	/**
	 * Stop the propagation of the event to other listeners.
	 *
	 * @return void
	 */
	public function stop()
	{
		return parent::stopPropagation();
	}

	/**
	 * Determine if the event has been stopped from propagating.
	 *
	 * @return bool
	 */
	public function isStopped()
	{
		return parent::isPropagationStopped();
	}

	/**
	 * Dynamically retrieve items from the payload.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->payload[$key];
	}

	/**
	 * Dynamically set items in the payload.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->payload[$key] = $value;
	}

	/**
	 * Determine if payload item is set.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->payload[$key]);
	}

	/**
	 * Unset an item from the payload array.
	 *
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->payload[$key]);
	}

} 