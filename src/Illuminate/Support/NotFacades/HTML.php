<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Html\HtmlBuilder
 */
class HTML extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'html'; }

}
