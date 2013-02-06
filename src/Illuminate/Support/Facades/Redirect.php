<?php namespace Illuminate\Support\Facades;

class Redirect extends Facade {

	/**
	 * Get the registered component 'redirect'.
	 *
	 * @return Illuminate\Routing\Redirector
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['redirect'];
	}

}