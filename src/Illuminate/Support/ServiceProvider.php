<?php namespace Illuminate\Support;

use ReflectionClass;

abstract class ServiceProvider {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Create a new service provider instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	abstract public function register();

	/**
	 * Register the package's component namespaces.
	 *
	 * @param  string  $package
	 * @param  string  $namespace
	 * @param  string  $path
	 * @return void
	 */
	public function package($package, $namespace = null, $path = null)
	{
		$namespace = $this->getPackageNamespace($package, $namespace);

		// In this method we will register the configuration package for the package
		// so that the configuration options cleanly cascade into the application
		// folder to make the developers lives much easier in maintaining them.
		$path = $path ?: $this->guessPackagePath();

		$config = $path.'/config';

		if ($this->app['files']->isDirectory($config))
		{
			$this->app['config']->package($package, $config, $namespace);
		}

		// Next we will check for any "language" components. If language files exist
		// we will register them with this given package's namespace so that they
		// may be accessed using the translation facilities of the application.
		$lang = $path.'/lang';

		if ($this->app['files']->isDirectory($lang))
		{
			$this->app['translator']->addNamespace($namespace, $lang);
		}

		// Next, we will see if the application view folder contains a folder for the
		// package and namespace. If it does, we'll give that folder precedence on
		// the loader list for the views so the package views can be overridden.
		$appView = $this->getAppViewPath($package, $namespace);

		if ($this->app['files']->isDirectory($appView))
		{
			$this->app['view']->addNamespace($namespace, $appView);
		}

		// Finally we will register the view namespace so that we can access each of
		// the views available in this package. We use a standard convention when
		// registering the paths to every package's views and other components.
		$view = $path.'/views';

		if ($this->app['files']->isDirectory($view))
		{
			$this->app['view']->addNamespace($namespace, $view);
		}
	}

	/**
	 * Guess the package path for the provider.
	 *
	 * @return string
	 */
	public function guessPackagePath()
	{
		$reflect = new ReflectionClass($this);

		// We want to get the class that is closest to this base class in the chain of
		// classes extending it. That should be the original service provider given
		// by the package and should allow us to guess the location of resources.
		$chain = $this->getClassChain($reflect);

		$path = $chain[count($chain) - 2]->getFileName();

		return realpath(dirname($path).'/../../');
	}

	/**
	 * Get a class from the ReflectionClass inheritance chain.
	 *
	 * @param  ReflectionClass  $reflection
	 * @return array
	 */
	protected function getClassChain(ReflectionClass $reflect)
	{
		$classes = array();

		// This loop essentially walks the inheritance chain of the classes starting
		// at the most "childish" class and walks back up to this class. Once we
		// get to the end of the chain we will bail out and return the offset.
		while ($reflect !== false)
		{
			$classes[] = $reflect;

			$reflect = $reflect->getParentClass();
		}

		return $classes;
	}

	/**
	 * Determine the namespace for a package.
	 *
	 * @param  string  $package
	 * @param  string  $namespace
	 * @return string
	 */
	protected function getPackageNamespace($package, $namespace)
	{
		if (is_null($namespace))
		{
			list($vendor, $namespace) = explode('/', $package);
		}

		return $namespace;
	}

	/**
	 * Register the package's custom Artisan commands.
	 *
	 * @param  array  $commands
	 * @return void
	 */
	public function commands($commands)
	{
		$commands = is_array($commands) ? $commands : func_get_args();

		// To register the commands with Artisan, we will grab each of the arguments
		// passed into the method and listen for Artisan "start" event which will
		// give us the Artisan console instance which we will give commands to.
		$events = $this->app['events'];

		$events->listen('artisan.start', function($artisan) use ($commands)
		{
			$artisan->resolveCommands($commands);
		});
	}

	/**
	 * Get the application package view path.
	 *
	 * @param  string  $package
	 * @param  string  $namespace
	 * @return string
	 */
	protected function getAppViewPath($package, $namespace)
	{
		return $this->app['path']."/views/packages/{$package}/{$namespace}";
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred()
	{
		return $this->defer;
	}

}
