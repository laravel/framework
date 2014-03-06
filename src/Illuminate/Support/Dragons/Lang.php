<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Translation\Translator
 */
class Lang extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'translator'; }

}