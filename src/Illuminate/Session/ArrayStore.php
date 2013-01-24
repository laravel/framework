<?php namespace Illuminate\Session;

use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArrayStore extends CacheDrivenStore {

	/**
	 * Load the session for the request.
	 *
	 * @param  Illuminate\CookieJar  $cookies
	 * @return void
	 */
	public function start(CookieJar $cookies)
	{
		$this->session = $this->createFreshSession();
	}

	/**
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @param  Illuminate\CookieJar  $cookie
	 * @param  int  $lifetime
	 * @return void
	 */
	public function finish(Response $response, CookieJar $cookie, $lifetime)
	{
		// No storage on array sessions...
	}

}