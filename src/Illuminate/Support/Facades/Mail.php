<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Mail\Mailer
 */
final class Mail extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'mailer'; }

}