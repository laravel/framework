<?php namespace Illuminate\Contracts\Auth;

use Closure;

interface PasswordReminder {

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
