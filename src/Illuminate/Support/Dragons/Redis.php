<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Redis\Database
 */
class Redis extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'redis'; }

}