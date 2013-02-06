<?php namespace Illuminate\Support\Facades;

class Queue extends Facade {

	/**
	 * Get the registered component 'queue'.
	 *
	 * @return \Illuminate\Queue\QueueManager
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['queue'];
	}

}