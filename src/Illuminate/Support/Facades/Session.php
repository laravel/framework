<?php namespace Illuminate\Support\Facades;

class Session extends Facade {

	/**
	 * Get the registered component 'session'.
	 *
	 * @return Illuminate\Session\SessionManager
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['session'];
	}

}