<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Response extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'Illuminate\Contracts\Routing\ResponseFactory'; }

}
