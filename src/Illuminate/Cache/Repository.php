<?php namespace Illuminate\Cache;

use Closure;
use DateTime;
use ArrayAccess;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class Repository implements CacheContract, ArrayAccess {

	use Macroable {
		__call as macroCall;
	}

	/**
	 * The cache store implementation.
	 *
	 * @var \Illuminate\Contracts\Cache\Store
	 */
	protected $store;

	/**
	 * The event dispatcher implementation.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The default number of minutes to store items.
	 *
	 * @var int
	 */
	protected $default = 60;

	/**
	 * Create a new cache repository instance.
	 *
	 * @param  \Illuminate\Contracts\Cache\Store  $store
	 */
	public function __construct(Store $store)
	{
		$this->store = $store;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher
	 * @return void
	 */
	public function setEventDispatcher(Dispatcher $events)
	{
		$this->events = $events;
	}

	/**
	 * Fire an event for this cache instance.
	 *
	 * @param  string  $event
	 * @param  array  $payload
	 * @return void
	 */
	protected function fireCacheEvent($event, $payload)
	{
		if (isset($this->events))
		{
			$this->events->fire('cache.'.$event, $payload);
		}
	}

	/**
	 * Determine if an item exists in the cache.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$value = $this->store->get($key);

		if (is_null($value))
		{
			$this->fireCacheEvent('missed', [$key]);

			$value = value($default);
		}
		else
		{
			$this->fireCacheEvent('hit', [$key, $value]);
		}

		return $value;
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function pull($key, $default = null)
	{
		$value = $this->get($key, $default);

		$this->forget($key);

		return $value;
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  \DateTime|int  $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes)
	{
		$minutes = $this->getMinutes($minutes);

		if ( ! is_null($minutes))
		{
			$this->store->put($key, $value, $minutes);

			$this->fireCacheEvent('write', [$key, $value, $minutes]);
		}
	}

	/**
	 * Store an item in the cache if the key does not exist.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  \DateTime|int  $minutes
	 * @return bool
	 */
	public function add($key, $value, $minutes)
	{
		if (method_exists($this->store, 'add'))
		{
			return $this->store->add($key, $value, $minutes);
		}

		if (is_null($this->get($key)))
		{
			$this->put($key, $value, $minutes);

			return true;
		}

		return false;
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
		$this->store->forever($key, $value);

		$this->fireCacheEvent('write', [$key, $value, 0]);
	}

	/**
	 * Get an item from the cache, or store the default value.
	 *
	 * @param  string  $key
	 * @param  \DateTime|int  $minutes
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function remember($key, $minutes, Closure $callback)
	{
		// If the item exists in the cache we will just return this immediately
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes in storage.
		if ( ! is_null($value = $this->get($key)))
		{
			return $value;
		}

		$this->put($key, $value = $callback(), $minutes);

		return $value;
	}

	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function sear($key, Closure $callback)
	{
		return $this->rememberForever($key, $callback);
	}

	/**
	 * Get an item from the cache, or store the default value forever.
	 *
	 * @param  string   $key
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function rememberForever($key, Closure $callback)
	{
		// If the item exists in the cache we will just return this immediately
		// otherwise we will execute the given Closure and cache the result
		// of that execution for the given number of minutes. It's easy.
		if ( ! is_null($value = $this->get($key)))
		{
			return $value;
		}

		$this->forever($key, $value = $callback());

		return $value;
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function forget($key)
	{
		$success = $this->store->forget($key);

		$this->fireCacheEvent('delete', [$key]);

		return $success;
	}

	/**
	 * Get the default cache time.
	 *
	 * @return int
	 */
	public function getDefaultCacheTime()
	{
		return $this->default;
	}

	/**
	 * Set the default cache time in minutes.
	 *
	 * @param  int   $minutes
	 * @return void
	 */
	public function setDefaultCacheTime($minutes)
	{
		$this->default = $minutes;
	}

	/**
	 * Get the cache store implementation.
	 *
	 * @return \Illuminate\Contracts\Cache\Store
	 */
	public function getStore()
	{
		return $this->store;
	}

	/**
	 * Determine if a cached value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Store an item in the cache for the default time.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->put($key, $value, $this->default);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		return $this->forget($key);
	}

	/**
	 * Calculate the number of minutes with the given duration.
	 *
	 * @param  \DateTime|int  $duration
	 * @return int|null
	 */
	protected function getMinutes($duration)
	{
		if ($duration instanceof DateTime)
		{
			$fromNow = Carbon::instance($duration)->diffInMinutes();

			return $fromNow > 0 ? $fromNow : null;
		}

		return is_string($duration) ? (int) $duration : $duration;
	}

	/**
	 * Handle dynamic calls into macros or pass missing methods to the store.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (static::hasMacro($method))
		{
			return $this->macroCall($method, $parameters);
		}

		return call_user_func_array(array($this->store, $method), $parameters);
	}

}
