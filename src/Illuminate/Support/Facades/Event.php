<?php namespace Illuminate\Support\Facades;

class Event extends Facade {

	/**
	 * Get the registered component 'events'.
	 *
	 * @return \Illuminate\Events\Dispatcher
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['events'];
	}

}