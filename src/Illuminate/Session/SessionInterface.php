<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface as BaseSessionInterface;

interface SessionInterface extends BaseSessionInterface {

	/**
	 * Get the session handler instance.
	 *
	 * @return \SessionHandlerInterface
	 */
	public function getHandler();

	/**
	 * Determine if the session handler needs a request.
	 *
	 * @return bool
	 */
	public function handlerNeedsRequest();

}