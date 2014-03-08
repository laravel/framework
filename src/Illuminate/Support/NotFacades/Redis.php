<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Redis\Database
 */
class Redis extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'redis'; }

}