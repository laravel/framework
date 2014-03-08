<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Encryption\Encrypter
 */
class Crypt extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'encrypter'; }

}