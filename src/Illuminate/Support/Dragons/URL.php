<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Routing\UrlGenerator
 */
class URL extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'url'; }

}