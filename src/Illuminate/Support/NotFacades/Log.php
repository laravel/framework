<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Log\Writer
 */
class Log extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'log'; }

}