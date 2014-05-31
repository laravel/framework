<?php namespace Illuminate\Foundation;

use ReflectionClass;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PackageCompiler {

	/**
	 * The filesystem instance.
	 * 
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Relative path to the compiled file.
	 * 
	 * @var string
	 */
	protected $compiledRelativePath;

	/**
	 * Create a new package compiler instance.
	 * 
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $compiledRelativePath
	 * @return void
	 */
	public function __construct(Filesystem $files, $compiledRelativePath = 'bootstrap/compiled.php')
	{
		$this->files = $files;
		$this->compiledRelativePath = $compiledRelativePath;
	}

	/**
	 * Get the files to be compiled for a package.
	 * 
	 * @param  \Illuminate\Support\ServiceProvider  $provider
	 * @return array
	 */
	public function compile(ServiceProvider $provider)
	{
		if ( ! $providerRootPath = $this->getProviderRootPath($provider)) return array();

		$paths = array();

		foreach ($provider->compiles() as $path)
		{
			$paths[] = $this->getProviderPaths($providerRootPath, $path);
		}

		$paths = array_flatten($paths);

		return $this->stripProviderPath($provider, $paths);
	}

	/**
	 * Get paths that should be combined and compiled from a path.
	 * 
	 * @param  string  $providerRootPath
	 * @param  string  $path
	 * @return array
	 */
	protected function getProviderPaths($providerRootPath, $path)
	{
		$fullPath = $providerRootPath.'/'.$path;

		if ($this->files->exists($fullPath))
		{
			return $this->getFileOrDirectoryPaths($fullPath);
		}

		if (ends_with($path, '**/*'))
		{
			$fullPath = substr($fullPath, 0, -4);

			if ($this->files->exists($fullPath))
			{
				return $this->mapFilePaths($this->files->allFiles($fullPath));
			}
		}

		if (ends_with($path, '*'))
		{
			$fullPath = substr($fullPath, 0, -2);

			if ($this->files->exists($fullPath))
			{
				return $this->getFileOrDirectoryPaths($fullPath);
			}
		}

		return array();
	}

	/**
	 * Get path to a file or array of paths to files in a directory.
	 * 
	 * @param  string  $path
	 * @return string|array
	 */
	protected function getFileOrDirectoryPaths($path)
	{
		return $this->files->isDirectory($path) ? $this->files->files($path) : $path;
	}

	/**
	 * Map an array of SplFileInfo objects to file paths.
	 * 
	 * @param  array  $files
	 * @return array
	 */
	protected function mapFilePaths($files)
	{
		return array_map(function($file) { return $file->getPathname(); }, $files);
	}

	/**
	 * Get the root path to the provider.
	 * 
	 * @param  \Illuminate\Support\ServiceProvider  $provider
	 * @return string|null
	 */
	protected function getProviderRootPath(ServiceProvider $provider)
	{
		$path = $this->getProviderPath($provider);

		return ! ends_with($path, $this->compiledRelativePath) ? dirname($path) : null;
	}

	/**
	 * Get the path to the provider.
	 * 
	 * @param  \Illuminate\Support\ServiceProvider  $provider
	 * @return string
	 */
	protected function getProviderPath(ServiceProvider $provider)
	{
		$reflection = new ReflectionClass($provider);

		return $reflection->getFileName();
	}

	/**
	 * Strip provider path so that it's not compiled.
	 * 
	 * @param  \Illuminate\Support\ServiceProvider  $provider
	 * @param  array  $paths
	 * @return array
	 */
	protected function stripProviderPath(ServiceProvider $provider, array $paths)
	{
		$providerPath = $this->getProviderPath($provider);

		return array_filter($paths, function($path) use ($providerPath) { return $path != $providerPath; });
	}

}
