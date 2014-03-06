<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Foundation\Artisan
 */
class Artisan extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'artisan'; }

}