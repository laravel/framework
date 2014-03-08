<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Routing\Redirector
 */
class Redirect extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'redirect'; }

}