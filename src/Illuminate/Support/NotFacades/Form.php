<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Html\FormBuilder
 */
class Form extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'form'; }

}