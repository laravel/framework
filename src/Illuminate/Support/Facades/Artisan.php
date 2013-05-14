<?php namespace Illuminate\Support\Facades;

class Artisan extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'artisan'; }

}