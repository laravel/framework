<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Foundation\Application
 */
class App extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'app'; }

}