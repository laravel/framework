<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Encryption\Encrypter
 */
class Crypt extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'encrypter'; }

}