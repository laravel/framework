<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Filesystem\FilesystemInterface
 */
class File extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'files'; }

}
