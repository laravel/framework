<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class Store extends SymfonySession {

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		parent::start();

		if ( ! $this->has('_token')) $this->put('_token', str_random(40));
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($name)
	{
		return parent::has($name) or $this->hasFlash($name);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($name, $default = null)
	{
		if ( ! is_null($value = parent::get($name))) return $value;
		
		$flash = $this->getFlashBag()->peek($name);

		return count($flash) > 0 ? $flash[0] : value($default);
	}

	/**
	 * Determine if the session has a flash item.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function hasFlash($name)
	{
		return count($this->getFlashBag()->peek($name)) > 0;
	}

	/**
	 * Determine if the session contains old input.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasOldInput($key)
	{
		return ! is_null($this->getOldInput($key));
	}

	/**
	 * Get the requested item from the flashed input array.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function getOldInput($key = null, $default = null)
	{
		$input = $this->get('_old_input', array());

		// Input that is flashed to the session can be easily retrieved by the
		// developer, making repopulating old forms and the like much more
		// convenient, since the request's previous input is available.
		if (is_null($key)) return $input;

		return array_get($input, $key, $default);
	}

	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 */
	public function getToken()
	{
		return $this->token();
	}

	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 */
	public function token()
	{
		return $this->get('_token');
	}

	/**
	 * Put a key / value pair in the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Flash a key / value pair to the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function flash($key, $value)
	{
		$this->getFlashBag()->set($key, $value);
	}

	/**
	 * Flash an input array to the session.
	 *
	 * @param  array  $value
	 * @return void
	 */
	public function flashInput(array $value)
	{
		return $this->flash('_old_input', $value);
	}

	/**
	 * Keep all of the session flash data from expiring.
	 *
	 * @return void
	 */
	public function reflash()
	{
		foreach ($this->getFlashBag()->peekAll() as $key => $value)
		{
			$this->getFlashBag()->set($key, $value);
		}
	}

	/**
	 * Keep a session flash item from expiring.
	 *
	 * @param  string|array  $keys
	 * @return void
	 */
	public function keep($keys)
	{
		foreach (array_only($this->getFlashBag()->peekAll(), $keys) as $key => $value)
		{
			$this->getFlashBag()->set($key, $value);
		}
	}

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		return $this->remove($key);
	}

	/**
	 * Remove all of the items from the session.
	 *
	 * @return void
	 */
	public function flush()
	{
		return $this->clear();
	}

	/**
	 * Generate a new session identifier.
	 *
	 * @return string
	 */
	public function regenerate()
	{
		return $this->migrate();
	}

}