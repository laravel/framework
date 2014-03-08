<?php namespace Illuminate\Support\Surrogates;

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