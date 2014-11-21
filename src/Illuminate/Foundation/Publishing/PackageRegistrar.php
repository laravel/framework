<?php namespace Illuminate\Foundation\Publishing;

use Illuminate\Filesystem\Filesystem;

class PackageRegistrar
{
	/**
	* The registered pacakges
	*
	* @var array
	*/
	protected $registeredPackages;

	/**
	* The registered packages' paths
	*
	* @var array
	*/
	protected $packgesPaths;

	/**
	* The registered packages' components' paths
	*
	* @var array
	*/
	protected $componentPaths;

	/**
	 * Register a package
	 * 
	 * @param  string  $package
	 * @param  string  $namespace
	 * @param  string  $basePath
	 * @param  array|null  $componentPaths
	 * @return void
	 */
	public function register($package, $namespace, $basePath, array $componentPaths = null)
	{
		$this->registeredPackages[$package] = $namespace;
		$this->packgesPaths[$package] = $basePath;

		if ($componentPaths === null)
		{
			$componentPaths = [
				'config' => 'src/config',
				'lang' => 'src/lang',
				'views' => 'src/views',
				'assets' => 'public',
				'migrations' => 'src/migrations',
			];
		}

		foreach ($componentPaths as $component => $componentPath)
		{
			$this->registerComponentPath($component, $package, $componentPath);
		}
	}

	/**
	 * Register the component path
	 *
	 * @param  string  $component The component anem
	 * @param  string  $package The package name
	 * @param  string  $componentPath The component relative path
	 * @return void
	 */
	protected function registerComponentPath($component, $package, $componentPath)
	{
		return $this->componentPaths[$component][$package] = $componentPath;
	}

	/**
	 * Get the base path for a package
	 * 
	 * @param  string  $package
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public function getPacakgeBasePath($package)
	{
		if (isset($this->packgesPaths[$package]))
		{
			throw new \InvalidArgumentException("Path for unregistered package requested: ($package)");
		}

		return array_get($this->packgesPaths, $package);
	}

	/**
	 * Get the config path
	 *
	 * @param  string  $package The package name
	 * @return string|null
	 */
	public function getConfigPath($package)
	{
		return $this->getComponentPath('config', $package);
	}

	/**
	 * Get the language path
	 *
	 * @param  string  $package The package name
	 * @return string|null
	 */
	public function getLanguagePath($package)
	{
		return $this->getComponentPath('lang', $package);
	}

	/**
	 * Get the views path
	 *
	 * @param  string  $package The package name
	 * @return string|null
	 */
	public function getViewsPath($package)
	{
		return $this->getComponentPath('views', $package);
	}

	/**
	 * Get the assets path
	 *
	 * @param  string  $package The package name
	 * @return string|null
	 */
	public function getAssetsPath($package)
	{
		return $this->getComponentPath('assets', $package);
	}

	/**
	 * Get the migrations path
	 *
	 * @param  string  $package The package name
	 * @return string|null
	 */
	public function getMigrationsPath($package)
	{
		return $this->getComponentPath('migrations', $package);
	}

	/**
	 * Get the registered component path
	 * 
	 * @param  string  $component The component name
	 * @param  string  $package The package name
	 * @return string|null
	 */
	protected function getComponentPath($component, $package)
	{
		return array_get($this->componentPaths, $component.'.'.$package, null);
	}
}