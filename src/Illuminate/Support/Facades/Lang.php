<?php namespace Illuminate\Support\Facades;

class Lang extends Facade {

	/**
	 * Get the registered component 'translator'.
	 *
	 * @return \Illuminate\Translation\Translator
	 */
	public static function Current() {
		return \Illuminate\Foundation\Application::Current()['translator'];
	}

}