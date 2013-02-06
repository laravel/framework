<?php namespace Illuminate\Support\Facades;

class Redis extends Facade {

	/**
	 * Get the registered component 'redis'.
	 *
	 * @return \Illuminate\Redis\RedisManager
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['redis'];
	}

}