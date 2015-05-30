<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Bus\Dispatcher
 */
class Bus extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'Illuminate\Contracts\Bus\Dispatcher';
	}

}
