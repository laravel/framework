<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Encryption\Encrypter
 */
class Crypt extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'encrypter'; }

}