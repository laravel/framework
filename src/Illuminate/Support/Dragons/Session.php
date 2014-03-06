<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Session\SessionManager
 * @see \Illuminate\Session\Store
 */
class Session extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'session'; }

}