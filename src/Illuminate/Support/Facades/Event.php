<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Events\Dispatcher
 */
final class Event extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'events'; }

}