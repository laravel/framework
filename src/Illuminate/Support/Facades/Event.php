<?php namespace Illuminate\Support\Facades;

class Event extends Facade {

	/**
	 * Get the registered component 'events'.
	 *
	 * @return Illuminate\Events\Dispatcher
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['events'];
	}

}