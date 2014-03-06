<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Remote\RemoteManager
 * @see \Illuminate\Remote\Connection
 */
class SSH extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'remote'; }

}