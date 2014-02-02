<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Remote\RemoteManager
 * @see \Illuminate\Remote\Connection
 */
final class SSH extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'remote'; }

}