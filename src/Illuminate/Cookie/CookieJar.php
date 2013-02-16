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
	 * @var Illuminate\Encryption\Encrypter
	 */
	protected $encrypter;

	/**
	 * The default cookie options.
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * Create a new cookie manager instance.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  Illuminate\Encryption\Encrypter  $encrypter
	 * @param  array   $defaults
	 * @return void
	 */
	public function __construct(Request $request, Encrypter $encrypter, array $defaults)
	{
		$this->request = $request;
		$this->defaults = $defaults;
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
	 * @return Symfony\Component\HttpFoundation\Cookie
	 */
	public function make($name, $value, $minutes = 0)
	{
		extract($this->defaults);

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
	 * @return Symfony\Component\HttpFoundation\Cookie
	 */
	public function forever($name, $value)
	{
		return $this->make($name, $value, 2628000);
	}

	/**
	 * Expire the given cookie.
	 *
	 * @param  string  $name
	 * @return Symfony\Component\HttpFoundation\Cookie
	 */
	public function forget($name)
	{
		return $this->make($name, null, -2628000);
	}

	/**
	 * Set the value of a cookie otpion.
	 *
	 * @param  string  $option
	 * @param  string  $value
	 * @return void
	 */
	public function setDefault($option, $value)
	{
		$this->defaults[$option] = $value;
	}

	/**
	 * Get the request instance.
	 *
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get the encrypter instance.
	 *
	 * @return Illuminate\Encrypter
	 */
	public function getEncrypter()
	{
		return $this->encrypter;
	}

}