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
	public function save()
	{
		$this->ageFlashData();

		return parent::save();
	}

	/**
	 * Age the flash data for the session.
	 *
	 * @return void
	 */
	protected function ageFlashData()
	{
		foreach ($this->get('flash.old', array()) as $old) { $this->forget($old); }

		$this->put('flash.old', $this->get('flash.new'));

		$this->put('flash.new', array());
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($name, $default = null)
	{
		$value = parent::get($name);

		return is_null($value) ? value($default) : $value;
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
	 * Push a value onto a session array.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function push($key, $value)
	{
		$array = $this->get($key, array());

		$array[] = $value;

		$this->put($key, $array);
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
		$this->put($key, $value);

		$this->push('flash.new', $key);
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