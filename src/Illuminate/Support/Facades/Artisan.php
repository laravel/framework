<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Foundation\Artisan
 */
class Artisan extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'artisan'; }

}