<?php namespace Illuminate\Cache;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Cache\Store;

class FileStore implements Store {

	/**
	 * The Illuminate Filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The file cache directory
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * Create a new file cache store instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $directory
	 * @return void
	 */
	public function __construct(Filesystem $files, $directory)
	{
		$this->files = $files;
		$this->directory = $directory;
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		return array_get($this->getPayload($key), 'data');
	}

	/**
	 * Retrieve an item and expiry time from the cache by key.
	 *
	 * @param  string  $key
	 * @return array
	 */
	protected function getPayload($key)
	{
		$path = $this->path($key);

		// If the file doesn't exists, we obviously can't return the cache so we will
		// just return null. Otherwise, we'll get the contents of the file and get
		// the expiration UNIX timestamps from the start of the file's contents.
		try
		{
			$expire = substr($contents = $this->files->get($path), 0, 10);
		}
		catch (Exception $e)
		{
			return array('data' => null, 'time' => null);
		}

		// If the current time is greater than expiration timestamps we will delete
		// the file and return null. This helps clean up the old files and keeps
		// this directory much cleaner for us as old files aren't hanging out.
		if (time() >= $expire)
		{
			$this->forget($key);

			return array('data' => null, 'time' => null);
		}

		$data = unserialize(substr($contents, 10));

		// Next, we'll extract the number of minutes that are remaining for a cache
		// so that we can properly retain the time for things like the increment
		// operation that may be performed on the cache. We'll round this out.
		$time = ceil(($expire - time()) / 60);

		return compact('data', 'time');
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
		$value = $this->expiration($minutes).serialize($value);

		$this->createCacheDirectory($path = $this->path($key));

		$this->files->put($path, $value);
	}

	/**
	 * Create the file cache directory if necessary.
	 *
	 * @param  string  $path
	 * @return void
	 */
	protected function createCacheDirectory($path)
	{
		try
		{
			$this->files->makeDirectory(dirname($path), 0777, true, true);
		}
		catch (Exception $e)
		{
			//
		}
	}

	/**
	 * Increment the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return int
	 */
	public function increment($key, $value = 1)
	{
		$raw = $this->getPayload($key);

		$int = ((int) $raw['data']) + $value;

		$this->put($key, $int, (int) $raw['time']);

		return $int;
	}

	/**
	 * Decrement the value of an item in the cache.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return int
	 */
	public function decrement($key, $value = 1)
	{
		return $this->increment($key, $value * -1);
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
		return $this->put($key, $value, 0);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function forget($key)
	{
		$file = $this->path($key);

		if ($this->files->exists($file))
		{
			return $this->files->delete($file);
		}

		return false;
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		if ($this->files->isDirectory($this->directory))
		{
			foreach ($this->files->directories($this->directory) as $directory)
			{
				$this->files->deleteDirectory($directory);
			}
		}
	}

	/**
	 * Get the full path for the given cache key.
	 *
	 * @param  string  $key
	 * @return string
	 */
	protected function path($key)
	{
		$parts = array_slice(str_split($hash = md5($key), 2), 0, 2);

		return $this->directory.'/'.join('/', $parts).'/'.$hash;
	}

	/**
	 * Get the expiration time based on the given minutes.
	 *
	 * @param  int  $minutes
	 * @return int
	 */
	protected function expiration($minutes)
	{
		if ($minutes === 0) return 9999999999;

		return time() + ($minutes * 60);
	}

	/**
	 * Get the Filesystem instance.
	 *
	 * @return \Illuminate\Filesystem\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

	/**
	 * Get the working directory of the cache.
	 *
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Get the cache key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return '';
	}

}
