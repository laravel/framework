<?php namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

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
		return BusDispatcher::class;
	}

}
