<?php namespace Illuminate\Support\Facades;

class DB extends Facade {

	/**
	 * Get the registered component 'db'.
	 *
	 * @return \Illuminate\db\
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['db'];
	}

}