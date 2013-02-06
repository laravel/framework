<?php namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Application;

class View extends Facade 

	/**
	 * Get the registered component 'view'.
	 *
	 * @return Illuminate\View\Environment
	 */
	public static function Current() {
		return Application::Current()['view'];
	}

}