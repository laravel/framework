<?php namespace Illuminate\Session;

use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class CookieSessionHandler implements SessionHandlerInterface {

	/**
	 * The cookie jar instance.
	 *
	 * @var \Illuminate\Contracts\Cookie\Factory
	 */
	protected $cookie;

	/**
	 * The request instance.
	 *
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Create a new cookie driven handler instance.
	 *
	 * @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookie
	 * @param  int  $minutes
	 * @return void
	 */
	public function __construct(CookieJar $cookie, $minutes)
	{
		$this->cookie = $cookie;
		$this->minutes = $minutes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		return $this->request->cookies->get($sessionId) ?: '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		$this->cookie->queue($sessionId, $data, $this->minutes);
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->cookie->queue($this->cookie->forget($sessionId));
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		return true;
	}

	/**
	 * Set the request instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

}
