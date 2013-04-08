<?php namespace Illuminate\Cache;

abstract class Sectionable implements StoreInterface {

	/**
	 * Indicates that section caching is implicit based on keys.
	 *
	 * @var bool
	 */
	public $implicit = true;

	/**
	 * The implicit section key delimiter.
	 *
	 * @var string
	 */
	public $delimiter = '::';

	/**
	 * Retrieve a sectioned item from the cache driver.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function getFromSection($section, $key, $default = null)
	{
		return $this->get($this->sectionItemKey($section, $key), $default);
	}

	/**
	 * Write a sectioned item to the cache.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function putInSection($section, $key, $value, $minutes)
	{		
		$this->put($this->sectionItemKey($section, $key), $value, $minutes);
	}

	/**
	 * Write a sectioned item to the cache that lasts forever.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function foreverInSection($section, $key, $value)
	{
		return $this->forever($this->sectionItemKey($section, $key), $value);
	}

	/**
	 * Get a sectioned item from the cache, or cache and return the default value.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @param  mixed   $default
	 * @param  int     $minutes
	 * @param  string  $function
	 * @return mixed
	 */
	public function rememberInSection($section, $key, $default, $minutes, $function = 'put')
	{
		$key = $this->sectionItemKey($section, $key);

		return $this->remember($key, $default, $minutes, $function);
	}

	/**
	 * Get a sectioned item from the cache, or cache the default value forever.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function searInSection($section, $key, $default)
	{
		return $this->sear($this->sectionItemKey($section, $key), $default);
	}

	/**
	 * Delete a sectioned item from the cache.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @return void
	 */
	public function forgetInSection($section, $key)
	{
		return $this->forget($this->sectionItemKey($section, $key));
	}

	/**
	 * Delete an entire section from the cache.
	 *
	 * @param  string    $section
	 * @return int|bool
	 */
	abstract public function forgetSection($section);
	
	/**
	 * Get a section item key for a given section and key.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @return string
	 */
	abstract protected function sectionItemKey($section, $key);

	/**
	 * Indicates if a key is sectionable.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	protected function sectionable($key)
	{
		return $this->implicit and $this->sectioned($key);
	}

	/**
	 * Determine if a key is sectioned.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	protected function sectioned($key)
	{
		return str_contains($key, $this->delimiter);
	}

	/**
	 * Get the section and key from a sectioned key.
	 *
	 * @param  string  $key
	 * @return array
	 */
	protected function parse($key)
	{
		return explode($this->delimiter, $key, 2);
	}

}