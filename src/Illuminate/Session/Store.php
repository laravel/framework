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

		$this->put('flash.old', $this->get('flash.new', array()));

		$this->put('flash.new', array());
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($name)
	{
		return ! is_null($this->get($name));
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($name, $default = null)
	{
		return array_get($this->all(), $name, $default);
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
		$all = $this->all();

		array_set($all, $key, $value);

		$this->replace($all);
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

		$this->removeFromOldFlashData(array($key));
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
	 * Reflash all of the session flash data.
	 *
	 * @return void
	 */
	public function reflash()
	{
		$this->mergeNewFlashes($this->get('flash.old'));

		$this->put('flash.old', array());
	}

	/**
	 * Reflash a subset of the current flash data.
	 *
	 * @param  array|dynamic  $keys
	 * @return void
	 */
	public function keep($keys = null)
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		$this->mergeNewFlashes($keys);

		$this->removeFromOldFlashData($keys);
	}

	/**
	 * Merge new flash keys into the new flash array.
	 *
	 * @param  array  $keys
	 * @return void
	 */
	protected function mergeNewFlashes(array $keys)
	{
		$values = array_unique(array_merge($this->get('flash.new'), $keys));

		$this->put('flash.new', $values);
	}

	/**
	 * Remove the given keys from the old flash data.
	 *
	 * @param  array  $keys
	 * @return void
	 */
	protected function removeFromOldFlashData(array $keys)
	{
		$this->put('flash.old', array_diff($this->get('flash.old', array()), $keys));
	}

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		$all = $this->all();

		array_forget($all, $key);

		$this->replace($all);
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