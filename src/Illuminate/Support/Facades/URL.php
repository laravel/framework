<?php namespace Illuminate\Support\Facades;

class URL extends Facade {

	/**
	 * Get the registered component 'url'.
	 *
	 * @return Illuminate\Routing\UrlGenerator
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['url'];
	}

}