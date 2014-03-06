<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Validation\Factory
 */
class Validator extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'validator'; }

}