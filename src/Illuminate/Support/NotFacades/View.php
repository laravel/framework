<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\View\Environment
 */
class View extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'view'; }

}