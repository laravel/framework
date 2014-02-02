<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\View\Factory
 */
final class View extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'view'; }

}