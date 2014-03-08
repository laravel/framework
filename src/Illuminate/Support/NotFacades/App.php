<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Foundation\Application
 */
class App extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'app'; }

}