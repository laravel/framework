<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\View\Factory
 */
class View extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'view'; }

}