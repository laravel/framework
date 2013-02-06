<?php namespace Illuminate\Support\Facades;

class Redirect extends Facade {

	/**
	 * Get the registered component 'redirect'.
	 *
	 * @return \Illuminate\Routing\Redirector
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['redirect'];
	}

}