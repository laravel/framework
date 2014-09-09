<?php namespace Illuminate\Contracts\Auth;

use Closure;

interface PasswordBroker {

	/**
	 * Constant representing a successfully sent reminder.
	 *
	 * @var int
	 */
	const REMINDER_SENT = 'reminders.sent';

	/**
	 * Constant representing a successfully reset password.
	 *
	 * @var int
	 */
	const PASSWORD_RESET = 'reminders.reset';

	/**
	 * Constant representing the user not found response.
	 *
	 * @var int
	 */
	const INVALID_USER = 'reminders.user';

	/**
	 * Constant representing an invalid password.
	 *
	 * @var int
	 */
	const INVALID_PASSWORD = 'reminders.password';

	/**
	 * Constant representing an invalid token.
	 *
	 * @var int
	 */
	const INVALID_TOKEN = 'reminders.token';

	/**
	 * Send a password reminder to a user.
	 *
	 * @param  array     $credentials
	 * @param  \Closure  $callback
	 * @return string
	 */
	public function remind(array $credentials, Closure $callback = null);

	/**
	 * Reset the password for the given token.
	 *
	 * @param  array     $credentials
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function reset(array $credentials, Closure $callback);

}
