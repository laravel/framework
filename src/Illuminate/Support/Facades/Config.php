<?php namespace Illuminate\Support\Facades;

class Config extends Facade {

	/**
	 * Get the registered component 'config'.
	 *
	 * @return \Illuminate\Config\
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['config'];
	}

}