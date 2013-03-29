<?php namespace Illuminate\Session;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class FileStore extends Store implements Sweeper {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The path where sessions should be stored.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new file session store instance.
	 *
	 * @param  \Illuminate\Filesystem  $files
	 * @param  string  $path
	 */
	public function __construct(Filesystem $files, $path)
	{
		$this->path = $path;
		$this->files = $files;
	}

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @return array|null
	 */
	public function retrieveSession($id)
	{
		if ($this->files->exists($path = $this->getFilePath($id)))
		{
			return unserialize($this->files->get($path));
		}
	}

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function createSession($id, array $session, Response $response)
	{
		$this->files->put($this->getFilePath($id), serialize($session));
	}

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function updateSession($id, array $session, Response $response)
	{
		$this->createSession($id, $session, $response);
	}

	/**
	 * Remove session records older than a given expiration.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration)
	{
		foreach ($this->files->files($this->path) as $file)
		{
			// If the last modification timestamp is less than the given UNIX expiration
			// timestamp, it indicates the session has expired and should be removed
			// off of the disks so we don't use space for files that have expired.
			if ($this->files->lastModified($file) < $expiration)
			{
				$this->files->delete($file);
			}
		}
	}

	/**
	 * Get the path to the session file.
	 *
	 * @param  string  $id
	 * @return string
	 */
	protected function getFilePath($id)
	{
		return rtrim($this->path, '/').'/'.$id;
	}

}