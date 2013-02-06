<?php namespace Illuminate\Support\Facades;

class Config extends Facade {

	/**
	 * Get the registered component 'config'.
	 *
	 * @return Illuminate\Config\
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['config'];
	}

}