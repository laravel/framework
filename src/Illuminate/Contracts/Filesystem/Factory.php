<?php namespace Illuminate\Contracts\Filesystem;

interface Factory {

	/**
	 * Get a filesystem implementation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	public function disk($name = null);

}
