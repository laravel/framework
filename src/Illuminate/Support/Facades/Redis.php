<?php namespace Illuminate\Support\Facades;

class Redis extends Facade {

	/**
	 * Get the registered component 'redis'.
	 *
	 * @return \Illuminate\Redis\RedisManager
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['redis'];
	}

}