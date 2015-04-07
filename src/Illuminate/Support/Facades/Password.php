<?php namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Auth\Passwords\PasswordBroker
 */
class Password extends Facade {

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
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'auth.password';
	}

}
