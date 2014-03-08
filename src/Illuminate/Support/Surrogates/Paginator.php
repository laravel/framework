<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Pagination\Environment
 */
class Paginator extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'paginator'; }

}