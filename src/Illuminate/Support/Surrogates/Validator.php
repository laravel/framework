<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Validation\Factory
 */
class Validator extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'validator'; }

}