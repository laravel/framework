<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Html\HtmlBuilder
 */
class HTML extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'html'; }

}
