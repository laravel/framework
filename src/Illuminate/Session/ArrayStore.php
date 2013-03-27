<?php namespace Illuminate\Session;

use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArrayStore extends CacheDrivenStore {

	/**
	 * Load the session for the request.
	 *
	 * @param  \Illuminate\CookieJar  $cookies
	 * @param  string  $name
	 * @return void
	 */
	public function start(CookieJar $cookies, $name)
	{
		$this->session = $this->createFreshSession();
	}

	/**
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @param  int  $lifetime
	 * @return void
	 */
	public function finish(Response $response, $lifetime)
	{
		// No storage on array sessions...
	}

	/**
	 * Write the session cookie to the response.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookie
	 * @param  string  $name
	 * @param  int     $lifetime
	 * @param  string  $path
	 * @param  string  $domain
	 * @return void
	 */
	public function getCookie(CookieJar $cookie, $name, $lifetime, $path, $domain)
	{
		//
	}

}