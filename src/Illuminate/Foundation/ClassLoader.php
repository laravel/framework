<?php namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;

class ClassLoader {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The registered directories.
	 *
	 * @var array
	 */
	protected $directories = array();

	/**
	 * Indicates if a ClassLoader has been registered.
	 *
	 * @var bool
	 */
	protected static $registered = false;

	/**
	 * Create a new class loader instance.
	 *
	 * @param  array  $directories
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(array $directories, Filesystem $files = null)
	{
		$this->directories = $directories;

		$this->files = $files ?: new Filesystem;
	}

	/**
	 * Load the given class file.
	 *
	 * @param  string  $class
	 * @return void
	 */
	public function load($class)
	{
		$class = $this->normalizeClass($class);

		foreach ($this->directories as $directory)
		{
			if ($this->files->exists($path = $directory.'/'.$class))
			{
				$this->files->requireOnce($path);

				return true;
			}
		}
	}

	/**
	 * Get the normal file name for a class.
	 *
	 * @param  string  $class
	 * @return string
	 */
	protected function normalizeClass($class)
	{
		if ($class[0] == '\\') $class = substr($class, 1);

		return str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	}

	/**
	 * Register the given class loader on the auto-loader stack.
	 *
	 * @return void
	 */
	public static function register(ClassLoader $loader)
	{
		if ( ! static::$registered)
		{
			spl_autoload_register(array($loader, 'load'));

			static::$registered = true;
		}
	}

	/**
	 * Add directories to the class loader.
	 *
	 * @param  array  $directories
	 * @return void
	 */
	public function addDirectories(array $directories)
	{
		$this->directories = array_merge($this->directories, $directories);
		
		$this->directories = array_unique($this->directories);
	}

}