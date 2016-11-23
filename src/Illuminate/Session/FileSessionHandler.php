<?php namespace Illuminate\Session;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Comparator\DateComparator;

class FileSessionHandler implements \SessionHandlerInterface {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path where sessions should be stored.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * A comparator to validate the session against its lifetime.
	 *
	 * @var \Symfony\Component\Finder\Comparator\DateComparator
	 */
	protected $comparator;

	/**
	 * Create a new file driven handler instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $path
	 * @param  string|int $lifetime The session lifetime in minutes
	 * @return void
	 */
	public function __construct(Filesystem $files, $path, $lifetime)
	{
		$this->path = $path;
		$this->files = $files;
		$this->comparator = new DateComparator('> now - '.$lifetime.' minutes');
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		if ($this->files->exists($path = $this->path.'/'.$sessionId) && $this->comparator->test($this->files->lastModified($path)))
		{
			return $this->files->get($path);
		}

		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		$this->files->put($this->path.'/'.$sessionId, $data, true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->files->delete($this->path.'/'.$sessionId);
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		$files = Finder::create()
					->in($this->path)
					->files()
					->ignoreDotFiles(true)
					->date('<= now - '.$lifetime.' seconds');

		foreach ($files as $file)
		{
			$this->files->delete($file->getRealPath());
		}
	}

}
