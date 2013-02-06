<?php namespace Illuminate\Support\Facades;

class Lang extends Facade {

	/**
	 * Get the registered component 'translator'.
	 *
	 * @return \Illuminate\Translation\Translator
	 */
	public static function getCurrent() {
		return \Illuminate\Foundation\Application::getCurrent()['translator'];
	}

}