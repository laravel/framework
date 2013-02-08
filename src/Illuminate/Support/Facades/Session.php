<?php namespace Illuminate\Support\Facades;

class Session extends Facade {

	/**
	 * Get the registered component 'session'.
	 *
	 * @return Illuminate\Session\SessionManager
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['session'];
	}

}