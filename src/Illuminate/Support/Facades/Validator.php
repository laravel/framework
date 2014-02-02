<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Validation\Factory
 */
final class Validator extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'validator'; }

}