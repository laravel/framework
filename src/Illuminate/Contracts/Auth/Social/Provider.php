<?php namespace Illuminate\Contracts\Auth\Social;

interface Provider {

	/**
	 * Redirect the user to the authentication page for the provider.
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function redirect();

	/**
	 * Get the User instance for the authenticated user.
	 *
	 * @return \Illuminate\Contracts\Auth\Social\User
	 */
	public function user();

}
