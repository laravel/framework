<?php namespace Illuminate\Session;

interface TokenProvider {

	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 */
	public function getToken();

}