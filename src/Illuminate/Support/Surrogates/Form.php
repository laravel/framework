<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Html\FormBuilder
 */
class Form extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'form'; }

}