<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Config\Repository
 */
class Config extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'config'; }

}