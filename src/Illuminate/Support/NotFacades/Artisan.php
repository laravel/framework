<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Foundation\Artisan
 */
class Artisan extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'artisan'; }

}