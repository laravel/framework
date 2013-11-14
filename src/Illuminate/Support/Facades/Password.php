<?php namespace Illuminate\Support\Facades;

class Password extends Facade {

	/**
	 * Constant representing a successfully sent reminder.
	 *
	 * @var int
	 */
	const REMINDER_SENT = 1;

	/**
	 * Constant representing a successfully reset password.
	 *
	 * @var int
	 */
	const PASSWORD_RESET = 2;

	/**
	 * Constant representing the user not found response.
	 *
	 * @var int
	 */
	const INVALID_USER = 3;

	/**
	 * Constant representing an invalid password.
	 *
	 * @var int
	 */
	const INVALID_PASSWORD = 4;

	/**
	 * Constant representing an invalid token.
	 *
	 * @var int
	 */
	const INVALID_TOKEN = 5;

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'auth.reminder'; }

}