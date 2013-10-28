<?php namespace Illuminate\Cookie;

use Closure;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieJar {

	/*
	 * The current request instance.
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * The encrypter instance.
	 *
	 * @var \Illuminate\Encryption\Encrypter
	 */
	protected $encrypter;

	/**
	 * The default path (if specified).
	 *
	 * @var string
	 */
	protected $path = '/';

	/**
	 * The default domain (if specified).
	 *
	 * @var string
	 */
	protected $domain = null;

	/**
	 * All of the cookies queued for sending.
	 *
	 * @var array
	 */
	protected $queued = array();

	/**
	 * Create a new cookie manager instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Illuminate\Encryption\Encrypter  $encrypter
	 * @return void
	 */
	public function __construct(Request $request, Encrypter $encrypter)
	{
		$this->request = $request;
		$this->encrypter = $encrypter;
	}

	/**
	 * Determine if a cookie exists and is not null.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	/**
	 * Get the value of the given cookie.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$value = $this->request->cookies->get($key);

		if ( ! is_null($value))
		{
			return $this->decrypt($value);
		}

		return $default instanceof Closure ? $default() : $default;
	}

	/**
	 * Decrypt the given cookie value.
	 *
	 * @param  string      $value
	 * @return mixed|null
	 */
	protected function decrypt($value)
	{
		try
		{
			return $this->encrypter->decrypt($value);
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Create a new cookie instance.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $minutes
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $httpOnly
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		list($path, $domain) = $this->getPathAndDomain($path, $domain);

		// Once we calculate the time we can encrypt the message. All cookies will be
		// encrypted using the Illuminate encryption component and will have a MAC
		// assigned to them by the encrypter to make sure they remain authentic.
		$time = ($minutes == 0) ? 0 : time() + ($minutes * 60);

		$value = $this->encrypter->encrypt($value);

		return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Create a cookie that lasts "forever" (five years).
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $httpOnly
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		return $this->make($name, $value, 2628000, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Expire the given cookie.
	 *
	 * @param  string  $name
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	public function forget($name)
	{
		return $this->make($name, null, -2628000);
	}

	/**
	 * Get the path and domain, or the default values.
	 *
	 * @param  string  $path
	 * @param  string  $domain
	 * @return array
	 */
	protected function getPathAndDomain($path, $domain)
	{
		return array($path ?: $this->path, $domain ?: $this->domain);
	}

	/**
	 * Set the default path and domain for the jar.
	 *
	 * @param  string  $path
	 * @param  string  $domain
	 * @return void
	 */
	public function setDefaultPathAndDomain($path, $domain)
	{
		list($this->path, $this->domain) = array($path, $domain);

		return $this;
	}

	/**
	 * Get the request instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get the encrypter instance.
	 *
	 * @return \Illuminate\Encryption\Encrypter
	 */
	public function getEncrypter()
	{
		return $this->encrypter;
	}

	/**
	 * Queue a cookie to send with the next response.
	 *
	 * @param  dynamic
	 * @return void
	 */
	public function queue()
	{
		if (head(func_get_args()) instanceof Cookie)
		{
			$cookie = head(func_get_args());
		}
		else
		{
			$cookie = call_user_func_array(array($this, 'make'), func_get_args());
		}

		$this->queued[$cookie->getName()] = $cookie;
	}

	/**
	 * Remove a cookie from the queue.
	 *
	 * @param $cookieName
	 */
	public function unqueue($name) 
	{
		unset($this->queued[$name]);
	}

	/**
	 * Get the cookies which have been queued for the next request
	 *
	 * @return array
	 */
	public function getQueuedCookies()
	{
		return $this->queued;
	}

}