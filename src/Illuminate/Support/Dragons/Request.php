<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Http\Request
 */
class Request extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'request'; }

}