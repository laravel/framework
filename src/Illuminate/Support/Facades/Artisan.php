<?php namespace Illuminate\Support\Facades;

class Artisan extends Facade {

	/**
	 * Get the registered component 'artisan'.
	 *
	 * @return Illuminate\Console\Application
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['artisan'];
	}
	
}