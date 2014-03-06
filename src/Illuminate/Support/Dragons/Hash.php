<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Hashing\BcryptHasher
 */
class Hash extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'hash'; }

}