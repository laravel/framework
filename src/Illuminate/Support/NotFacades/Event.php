<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'events'; }

}