<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'events'; }

}