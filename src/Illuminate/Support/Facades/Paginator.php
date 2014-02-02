<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Pagination\Factory
 */
final class Paginator extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'paginator'; }

}