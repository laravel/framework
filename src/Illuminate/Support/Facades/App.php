<?php namespace Illuminate\Support\Facades;

class App extends Facade {

	/**
	 * Get the Application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent();
	}

}