<?php namespace Illuminate\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerNativeFilesystem();

		$this->registerFlysystem();
	}

	/**
	 * Register the native filesystem implementation.
	 *
	 * @return void
	 */
	protected function registerNativeFilesystem()
	{
		$this->app->singleton('files', function() { return new Filesystem; });

		$this->app->alias('files', 'Illuminate\Filesystem\Filesystem');
	}

	/**
	 * Register the driver based filesystem.
	 *
	 * @return void
	 */
	protected function registerFlysystem()
	{
		$this->registerManager();

		$this->app->singleton('filesystem.disk', function()
		{
			return $this->app['filesystem']->disk($this->getDefaultDriver());
		});

		$this->app->alias('filesystem.disk', 'Illuminate\Contracts\Filesystem\Filesystem');

		$this->app->singleton('filesystem.cloud', function()
		{
			return $this->app['filesystem']->disk($this->getCloudDriver());
		});

		$this->app->alias('filesystem.cloud', 'Illuminate\Contracts\Filesystem\Cloud');
	}

	/**
	 * Register the filesystem manager.
	 *
	 * @return void
	 */
	protected function registerManager()
	{
		$this->app->singleton('filesystem', function()
		{
			return new FilesystemManager($this->app);
		});

		$this->app->alias('filesystem', 'Illuminate\Contracts\Filesystem\Factory');
	}

	/**
	 * Get the default file driver.
	 *
	 * @return string
	 */
	protected function getDefaultDriver()
	{
		return $this->app['config']['filesystems.default'];
	}

	/**
	 * Get the default cloud based file driver.
	 *
	 * @return string
	 */
	protected function getCloudDriver()
	{
		return $this->app['config']['filesystems.cloud'];
	}

}
