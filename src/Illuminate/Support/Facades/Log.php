<?php namespace Illuminate\Support\Facades;

class Log extends Facade {

	/**
	 * Get the registered component 'log'.
	 *
	 * @return \Illuminate\Log\Writer
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['log'];
	}

}