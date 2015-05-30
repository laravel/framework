<?php namespace Illuminate\Contracts\Auth;

use Closure;

interface PasswordBroker {

	/**
	 * Constant representing a successfully sent reminder.
	 *
	 * @var string
	 */
	const RESET_LINK_SENT = 'passwords.sent';

	/**
	 * Constant representing a successfully reset password.
	 *
	 * @var string
	 */
	const PASSWORD_RESET = 'passwords.reset';

	/**
	 * Constant representing the user not found response.
	 *
	 * @var string
	 */
	const INVALID_USER = 'passwords.user';

	/**
	 * Constant representing an invalid password.
	 *
	 * @var string
	 */
	const INVALID_PASSWORD = 'passwords.password';

	/**
	 * Constant representing an invalid token.
	 *
	 * @var string
	 */
	const INVALID_TOKEN = 'passwords.token';

	/**
	 * Send a password reset link to a user.
	 *
	 * @param  array  $credentials
	 * @param  \Closure|null  $callback
	 * @return string
	 */
	public function sendResetLink(array $credentials, Closure $callback = null);

	/**
	 * Reset the password for the given token.
	 *
	 * @param  array     $credentials
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function reset(array $credentials, Closure $callback);

	/**
	 * Set a custom password validator.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function validator(Closure $callback);

	/**
	 * Determine if the passwords match for the request.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validateNewPassword(array $credentials);

}
