<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Translation\Translator
 */
class Lang extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'translator'; }

}