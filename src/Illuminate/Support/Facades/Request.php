<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Http\Request
 */
final class Request extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'request'; }

}