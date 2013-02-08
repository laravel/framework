<?php namespace Illuminate\Support\Facades;

class Auth extends Facade {

	/**
	 * Get the registered component 'auth'.
	 *
	 * @return \Illuminate\Auth\AuthManager
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['auth'];
	}

}