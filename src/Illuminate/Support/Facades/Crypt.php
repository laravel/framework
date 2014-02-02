<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Encryption\Encrypter
 */
final class Crypt extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'encrypter'; }

}