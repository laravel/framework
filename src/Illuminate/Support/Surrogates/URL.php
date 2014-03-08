<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Routing\UrlGenerator
 */
class URL extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'url'; }

}