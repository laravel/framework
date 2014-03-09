<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Routing\Redirector
 */
class Redirect extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'redirect'; }

}