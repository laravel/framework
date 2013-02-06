<?php namespace Illuminate\Support\Facades;

class App extends Facade {

	/**
	 * Get the Application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current();
	}

}