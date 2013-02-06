<?php namespace Illuminate\Support\Facades;

class Cookie extends Facade {

	/**
	 * Get the registered component 'cookie'.
	 *
	 * @return \Illuminate\Cookie\CookieJar
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['cookie'];
	}

}