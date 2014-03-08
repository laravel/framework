<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Http\Request
 */
class Request extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'request'; }

}