<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Pagination\Environment
 */
class Paginator extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'paginator'; }

}