<?php namespace Illuminate\Contracts\Auth;

interface Authenticator {

	/**
	 * Determine if the current user is authenticated.
	 *
	 * @return bool
	 */
	public function check();

	/**
	 * Get the currently authenticated user.
	 *
	 * @return \Illuminate\Contracts\Auth\User|null
	 */
	public function user();

	/**
	 * Log a user into the application without sessions or cookies.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function once(array $credentials = array());

	/**
	 * Attempt to authenticate a user using the given credentials.
	 *
	 * @param  array  $credentials
	 * @param  bool   $remember
	 * @param  bool   $login
	 * @return bool
	 */
	public function attempt(array $credentials = array(), $remember = false, $login = true);

	/**
	 * Log a user into the application.
	 *
	 * @param  \Illuminate\Contracts\Auth\User  $user
	 * @param  bool  $remember
	 * @return void
	 */
	public function login(User $user, $remember = false);

	/**
	 * Log the user out of the application.
	 *
	 * @return void
	 */
	public function logout();

}
