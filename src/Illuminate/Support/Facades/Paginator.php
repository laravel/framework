<?php namespace Illuminate\Support\Facades;

class Paginator extends Facade {

	/**
	 * Get the registered component 'paginator'.
	 *
	 * @return Illuminate\Pagination\Environment
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['paginator'];
	}

}