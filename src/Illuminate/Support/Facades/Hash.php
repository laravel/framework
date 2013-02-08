<?php namespace Illuminate\Support\Facades;

class Hash extends Facade {

	/**
	 * Get the registered component 'hash'.
	 *
	 * @return \Illuminate\Hashing\HasherInterface
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['hash'];
	}

}