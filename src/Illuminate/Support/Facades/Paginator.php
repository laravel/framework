<?php namespace Illuminate\Support\Facades;

class Paginator extends Facade {

	/**
	 * Get the registered component 'paginator'.
	 *
	 * @return \Illuminate\Pagination\Environment
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['paginator'];
	}

}