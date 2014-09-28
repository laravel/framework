<?php namespace Illuminate\Filesystem;

use Illuminate\Support\Manager;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\AdapterInterface as Adapter;
use Illuminate\Contracts\Filesystem\Factory as FactoryContract;
use Illuminate\Filesystem\Adapters\ConnectionFactory as Factory;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class FilesystemManager implements FactoryContract {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * The factory instance.
	 *
	 * @var \Illuminate\Filesystem\Adapters\ConnectionFactory
	 */
	protected $factory;

	/**
	 * The array of resolved filesystem drivers.
	 *
	 * @var array
	 */
	protected $disks = [];

	/**
	 * Create a new filesystem manager instance.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @param  \Illuminate\Filesystem\Adapters\ConnectionFactory  $factory
	 * @return void
	 */
	public function __construct(ApplicationContract $app, Factory $factory)
	{
		$this->app = $app;
		$this->factory = $factory;
	}

	/**
	 * Get an OAuth provider implementation.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	public function disk($name = null)
	{
		$name = $name ?: $this->getDefaultDriver();

		return $this->disks[$name] = $this->get($name);
	}

	/**
	 * Attempt to get the disk from the local cache.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	protected function get($name)
	{
		return isset($this->disks[$name]) ? $this->disks[$name] : $this->resolve($name);
	}

	/**
	 * Resolve the given disk.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	protected function resolve($name)
	{
		$config = $this->getConfig($name);

		return $this->adapt($this->factory->make($config));
	}

	/**
	 * Adapt the filesystem implementation.
	 *
	 * @param  \League\Flysystem\AdapterInterface  $adapter
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	protected function adapt(Adapter $adapter)
	{
		return new FilesystemAdapter(new Flysystem($adapter));
	}

	/**
	 * Get the filesystem connection configuration.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		return $this->app['config']["filesystems.disks.{$name}"];
	}

	/**
	 * Get the default driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['filesystems.default'];
	}

}
