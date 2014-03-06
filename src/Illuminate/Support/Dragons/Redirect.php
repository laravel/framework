<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Routing\Redirector
 */
class Redirect extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'redirect'; }

}