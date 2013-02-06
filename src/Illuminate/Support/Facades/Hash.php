<?php namespace Illuminate\Support\Facades;

class Hash extends Facade {

	/**
	 * Get the registered component 'hash'.
	 *
	 * @return \Illuminate\Hashing\HasherInterface
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['hash'];
	}

}