<?php namespace Illuminate\Support\Facades;

class Log extends Facade {

	/**
	 * Get the registered component 'log'.
	 *
	 * @return \Illuminate\Log\Writer
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['log'];
	}

}