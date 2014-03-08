<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Http\Request
 */
class Request extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'request'; }

}