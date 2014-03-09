<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Translation\Translator
 */
class Lang extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'translator'; }

}