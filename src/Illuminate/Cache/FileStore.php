<?php namespace Illuminate\Cache; use Illuminate\Filesystem;

class FileStore extends Store {

	/**
	 * The Illuminate Filesystem instance.
	 *
	 * @var Illuminate\Filesystem
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
	 * @param  Illuminate\Filesystem  $files
	 * @param  string                 $directory
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
	protected function retrieveItem($key)
	{
		$path = $this->path($key);

		// If the file doesn't exists, we obviously can't return the cache so we will
		// just return null. Otherwise, we'll get the contents of the file and get
		// the expiration UNIX timestamps from the start of the file's contents.
		if ( ! $this->files->exists($path))
		{
			return null;
		}

		$contents = $this->files->get($path);

		$expiration = substr($contents, 0, 10);

		// If the current time is greater than expiration timestamps we will delete
		// the file and return null. This helps clean up the old files and keeps
		// this directory much cleaner for us as old files aren't hanging out.
		if (time() >= $expiration)
		{
			return $this->removeItem($key);
		}

		return unserialize(substr($contents, 10));
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @param  int     $minutes
	 * @return void
	 */
	protected function storeItem($key, $value, $minutes)
	{
		$value = $this->expiration($minutes).serialize($value);

		$this->files->put($this->path($key), $value);
	}

	/**
	 * Store an item in the cache indefinitely.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	protected function storeItemForever($key, $value)
	{
		return $this->storeItem($key, $value, 0);
	}

	/**
	 * Remove an item from the cache.
	 *
	 * @param  string  $key
	 * @return void
	 */
	protected function removeItem($key)
	{
		$this->files->delete($this->path($key));
	}

	/**
	 * Remove all items from the cache.
	 *
	 * @return void
	 */
	protected function flushItems()
	{
		$this->files->cleanDirectory($this->directory);
	}

	/**
	 * Get the full path for the given cache key.
	 *
	 * @param  string  $key
	 * @return string
	 */
	protected function path($key)
	{
		return $this->directory.'/'.$key;
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
	 * @var Illuminate\Filesystem
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

}