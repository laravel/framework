<?php namespace Illuminate\Support\Surrogates;

/**
 * @see \Illuminate\Filesystem\Filesystem
 */
class File extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'files'; }

}