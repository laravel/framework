<?php namespace Illuminate\Session;

use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;

class FileSessionHandler implements \SessionHandlerInterface
{

	/**
	 * The current session ID that's open
	 *
	 * @var string
	 */
	private $currentId;

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The session file pointer
	 *
	 * @var resource
	 */
	private $fp;

	/**
	 * The path where sessions should be stored.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new file driven handler instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem $files
	 * @param  string $path
	 * @return void
	 */
	public function __construct(Filesystem $files, $path)
	{
		$this->path = $path;
		$this->files = $files;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		// close any open files before opening something new
		$this->close();

		$path = $this->path . '/' . $sessionName;

		$this->currentId = $sessionName;
		$this->fp = fopen($path, 'c+b');

		// Obtain a write lock - must explicitly perform this because
		// the underlying OS may be advisory as opposed to mandatory
		$locked = flock($this->fp, LOCK_EX);
		if (!$locked) {
			fclose($this->fp);
			$this->fp = null;
			$this->currentId = null;
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		// only close if there is something to close
		if ($this->fp) {
			flock($this->fp, LOCK_UN);
			fclose($this->fp);
			$this->fp = null;
			$this->currentId = null;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		// if the proper session file isn't open, open it
		if ($sessionId != $this->currentId || !$this->fp) {
			if (!$this->open($this->path, $sessionId)) {
				throw new Exception('Could not open session file');
			}
		} else {
			// otherwise make sure we are at the beginning of the file
			rewind($this->fp);
		}

		$data = '';
		while (!feof($this->fp)) {
			$data .= fread($this->fp, 8192);
		}

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		// if the proper session file isn't open, notify us and don't write
		if ($sessionId != $this->currentId || !$this->fp) {
			if (!$this->open($this->path, $sessionId)) {
				throw new Exception('Could not open session file');
			}
		}

		ftruncate($this->fp, 0);
		rewind($this->fp);
		fwrite($this->fp, $data);

		$this->close();
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		$this->files->delete($this->path . '/' . $sessionId);
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		// a race condition exists such that garbage collection will throw a runtime exception if a file in the iterator
		// object returned by the Finder call in the parent function is deleted out of band before the iterator call
		// (foreach) gets to it.  this just catches those exceptions and retries the call (currently set arbitrarily at
		// 5 retries
		$retries = 5;

		for ($i = 0; $i < $retries; $i++) {
			try {
				$files = Finder::create()
					->in($this->path)
					->files()
					->ignoreDotFiles(true)
					->date('<= now - ' . $lifetime . ' seconds');

				foreach ($files as $file) {
					$this->files->delete($file->getRealPath());
				}
			}
			catch (RuntimeException $e) {
				continue;
			}

			break;
		}
	}
}
