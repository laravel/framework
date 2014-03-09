<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Hashing\BcryptHasher
 */
class Hash extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'hash'; }

}