<?php namespace Illuminate\Contracts\Filesystem;

interface Factory {

	/**
	 * Get an OAuth provider implementation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	public function disk($name = null);

}
