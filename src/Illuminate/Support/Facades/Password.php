<?php namespace Illuminate\Support\Facades;

class Password extends Facade {

	/**
	 * Constant representing a successfully sent reminder.
	 *
	 * @var int
	 */
	const REMINDER_SENT = 'reminder.sent';

	/**
	 * Constant representing a successfully reset password.
	 *
	 * @var int
	 */
	const PASSWORD_RESET = 'reminder.reset';

	/**
	 * Constant representing the user not found response.
	 *
	 * @var int
	 */
	const INVALID_USER = 'reminder.user';

	/**
	 * Constant representing an invalid password.
	 *
	 * @var int
	 */
	const INVALID_PASSWORD = 'reminder.password';

	/**
	 * Constant representing an invalid token.
	 *
	 * @var int
	 */
	const INVALID_TOKEN = 'reminder.token';

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'auth.reminder'; }

}