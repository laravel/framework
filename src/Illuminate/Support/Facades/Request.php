<?php namespace Illuminate\Support\Facades;

class Request extends Facade {

	/**
	 * Get the registered component 'request'.
	 *
	 * @return \Illuminate\Http\Request
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['request'];
	}

}