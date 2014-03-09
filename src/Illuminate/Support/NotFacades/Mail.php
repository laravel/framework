<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Mail\Mailer
 */
class Mail extends NotAFacade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor() { return 'mailer'; }

}