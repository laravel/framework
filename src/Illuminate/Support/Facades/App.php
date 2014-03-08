<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Foundation\Application
 */
class App extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'app'; }

}