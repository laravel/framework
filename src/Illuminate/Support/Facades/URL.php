<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Routing\UrlGenerator
 */
final class URL extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'url'; }

}