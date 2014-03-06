<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Config\Repository
 */
class Config extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'config'; }

}