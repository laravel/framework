<?php namespace Illuminate\Support\Facades;

class Validator extends Facade {

	/**
	 * Get the registered component 'validator'.
	 *
	 * @return Illuminate\Validation\Factory
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['validator'];
	}

}