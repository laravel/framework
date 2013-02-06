<?php namespace Illuminate\Support\Facades;

class Cache extends Facade {

	/**
	 * Get the registered component 'cache'.
	 *
	 * @return Illuminate\Cache\CacheManager
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['cache'];
	}

}