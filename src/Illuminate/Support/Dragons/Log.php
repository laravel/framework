<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Log\Writer
 */
class Log extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'log'; }

}