<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Config\Repository
 */
class Config extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'config'; }

}