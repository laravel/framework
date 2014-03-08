<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Mail\Mailer
 */
class Mail extends Surrogate {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'mailer'; }

}