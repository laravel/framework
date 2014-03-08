<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Validation\Factory
 */
class Validator extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'validator'; }

}