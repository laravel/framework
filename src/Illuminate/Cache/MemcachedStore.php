<?php namespace Illuminate\Cache; use Memcached;

class MemcachedStore extends Sectionable {

	/**
	 * The Memcached instance.
	 *
	 * @var Memcached
	 */
	protected $memcached;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new Memcached store.
	 *
	 * @param  Memcached  $memcached
	 * @param  string     $prefix
	 * @return void
	 */
	public function __construct(Memcached $memcached, $prefix = '')
	{
		$this->prefix = $prefix.':';
		$this->memcached = $memcached;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ($this->sectionable($key))
		{
			list($section, $key) = $this->parse($key);
		
			return $this->getFromSection($section, $key);
		}
		else 
		{
			$value = $this->memcached->get($this->prefix.$key);
	
			if ($this->memcached->getResultCode() == 0)
			{
				return $value;
			}
		}		
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes)
	{
		if ($this->sectionable($key))
		{
			list($section, $key) = $this->parse($key);
		
			return $this->putInSection($section, $key, $value, $minutes);
		}
		else
		{
			$this->memcached->set($this->prefix.$key, $value, $minutes * 60);
		}		
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function increment($key, $value = 1)
	{
		return $this->memcached->increment($this->prefix.$key, $value);
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function decrement($key, $value = 1)
	{
		return $this->memcached->decrement($this->prefix.$key, $value);
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function forever($key, $value)
	{
		if ($this->sectionable($key))
		{
			list($section, $key) = $this->parse($key);
		
			return $this->foreverInSection($section, $key, $value);
		}
		else
		{
			return $this->put($key, $value, 0);
		}
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		if ($this->sectionable($key))
		{
			list($section, $key) = $this->parse($key);
		
			if ($key == '*')
			{
				$this->forgetSection($section);
			}
			else
			{
				$this->forgetInSection($section, $key);
			}
		}
		else
		{
			$this->memcached->delete($this->prefix.$key);
		}		
	}
	
	/**
	 * Delete an entire section from the cache.
	 *
	 * @param  string    $section
	 * @return int|bool
	 */
	public function forgetSection($section)
	{
		return $this->increment($this->sectionKey($section));
	}
	
	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function sear($key, \Closure $callback)
	{
		// If the item exists in the cache we will just return this immediately
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes. It's easy.
		if (! is_null($this->get($key)))  return $this->get($key);
	
		$this->forever($key, $value = $callback());
	
		return $value;
	}
	
	/**
	 * Get the current section ID for a given section.
	 *
	 * @param  string  $section
	 * @return int
	 */
	protected function sectionId($section)
	{		
		return $this->sear($this->sectionKey($section), function()
		{
			//Set the section id to 1 if we don't have any section id set in the cache section key
			return 1;
		});
	}
	
	/**
	 * Get a section key name for a given section.
	 *
	 * @param  string  $section
	 * @return string
	 */
	protected function sectionKey($section)
	{
		return $section.'_section_key';
	}
	
	/**
	 * Get a section item key for a given section and key.
	 *
	 * @param  string  $section
	 * @param  string  $key
	 * @return string
	 */
	protected function sectionItemKey($section, $key)
	{
		return $section.'#'.$this->sectionId($section).'#'.$key;
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->memcached->flush();
	}

	/**
	 * Get the underlying Memcached connection.
	 *
	 * @return Memcached
	 */
	public function getMemcached()
	{
		return $this->memcached;
	}

}