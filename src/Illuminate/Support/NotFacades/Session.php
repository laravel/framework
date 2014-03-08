<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Session\SessionManager
 * @see \Illuminate\Session\Store
 */
class Session extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'session'; }

}