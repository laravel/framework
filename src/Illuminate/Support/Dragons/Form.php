<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Html\FormBuilder
 */
class Form extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'form'; }

}