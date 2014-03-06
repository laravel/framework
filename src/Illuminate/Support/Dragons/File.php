<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Filesystem\Filesystem
 */
class File extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'files'; }

}