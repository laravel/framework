<?php namespace Illuminate\Support\Facades;

class File extends Facade {

	/**
	 * Get the registered component 'files'.
	 *
	 * @return Illuminate\Filesystem\Filesystem
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['files'];
	}

}