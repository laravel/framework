<?php namespace Illuminate\Support\Facades;

class Password extends Facade {

	/**
	 * Get the registered component 'auth.reminder'.
	 *
	 * @return Illuminate\Auth\PasswordBroker
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['auth.reminder'];
	}

}