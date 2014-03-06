<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Auth\AuthManager
 * @see \Illuminate\Auth\Guard
 */
class Auth extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'auth'; }

}