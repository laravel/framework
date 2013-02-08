<?php namespace Illuminate\Support\Facades;

class Validator extends Facade {

	/**
	 * Get the registered component 'validator'.
	 *
	 * @return Illuminate\Validation\Factory
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['validator'];
	}

}