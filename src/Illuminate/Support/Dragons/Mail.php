<?php namespace Illuminate\Support\Dragons;

/**
 * @see \Illuminate\Mail\Mailer
 */
class Mail extends Dragon {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getDragonAccessor() { return 'mailer'; }

}