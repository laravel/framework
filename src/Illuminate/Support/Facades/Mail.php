<?php namespace Illuminate\Support\Facades;

class Mail extends Facade {

	/**
	 * Get the registered component 'mailer'.
	 *
	 * @return Illuminate\Mail\Mailer
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['mailer'];
	}

}