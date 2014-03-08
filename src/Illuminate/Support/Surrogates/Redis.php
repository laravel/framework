<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Redis\Database
 */
class Redis extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'redis'; }

}