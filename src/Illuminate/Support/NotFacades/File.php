<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Filesystem\Filesystem
 */
class File extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'files'; }

}