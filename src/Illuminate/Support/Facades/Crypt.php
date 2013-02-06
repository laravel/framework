<?php namespace Illuminate\Support\Facades;

class Crypt extends Facade {

	/**
	 * Get the registered component 'encrypter'.
	 *
	 * @return Illuminate\Encryption\
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['encrypter'];
	}

}