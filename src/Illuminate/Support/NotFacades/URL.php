<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Routing\UrlGenerator
 */
class URL extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'url'; }

}