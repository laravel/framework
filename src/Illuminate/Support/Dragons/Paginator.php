<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Pagination\Factory
 */
class Paginator extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'paginator'; }

}