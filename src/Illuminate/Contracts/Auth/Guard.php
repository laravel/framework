<?php namespace Illuminate\Contracts\Auth;

interface Guard {

	/**
	 * Determine if the current user is authenticated.
	 *
	 * @return bool
	 */
	public function check();

	/**
	 * Determine if the current user is a guest.
	 *
	 * @return bool
	 */
	public function guest();

	/**
	 * Get the currently authenticated user.
	 *
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
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
	 * Attempt to authenticate using HTTP Basic Auth.
	 *
	 * @param  string  $field
	 * @return \Symfony\Component\HttpFoundation\Response|null
	 */
	public function basic($field = 'email');

	/**
	 * Perform a stateless HTTP Basic login attempt.
	 *
	 * @param  string  $field
	 * @return \Symfony\Component\HttpFoundation\Response|null
	 */
	public function onceBasic($field = 'email');

	/**
	 * Validate a user's credentials.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validate(array $credentials = array());

	/**
	 * Log a user into the application.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  bool  $remember
	 * @return void
	 */
	public function login(Authenticatable $user, $remember = false);

	/**
	 * Log the given user ID into the application.
	 *
	 * @param  mixed  $id
	 * @param  bool   $remember
	 * @return \Illuminate\Contracts\Auth\Authenticatable
	 */
	public function loginUsingId($id, $remember = false);

	/**
	 * Determine if the user was authenticated via "remember me" cookie.
	 *
	 * @return bool
	 */
	public function viaRemember();

	/**
	 * Log the user out of the application.
	 *
	 * @return void
	 */
	public function logout();

}
