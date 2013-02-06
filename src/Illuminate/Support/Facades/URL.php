<?php namespace Illuminate\Support\Facades;

class URL extends Facade {

	/**
	 * Get the registered component 'url'.
	 *
	 * @return Illuminate\Routing\UrlGenerator
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['url'];
	}

}