<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Remote\RemoteManager
 * @see \Illuminate\Remote\Connection
 */
class SSH extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'remote'; }

}