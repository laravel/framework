<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\View\Environment
 */
class View extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'view'; }

}