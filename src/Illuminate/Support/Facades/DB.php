<?php namespace Illuminate\Support\Facades;

class DB extends Facade {

	/**
	 * Get the registered component 'db'.
	 *
	 * @return \Illuminate\db\
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['db'];
	}

}