<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;

class RouteCacheCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a route cache file for faster route registration';

	/**
	 * The router instance.
	 *
	 * @var \Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * An array of all the registered routes.
	 *
	 * @var \Illuminate\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new route command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem $files
	 * @param  \Illuminate\Routing\Router  $router
	 */
	public function __construct(Filesystem $files, Router $router)
	{
		parent::__construct();

		$this->router = $router;
		$this->routes = $router->getRoutes();
		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->call('route:clear');

		if (count($this->routes) == 0)
		{
			return $this->error("Your application doesn't have any routes.");
		}

		foreach ($this->routes as $route)
		{
			$route->prepareForSerialization();
		}

		$this->files->put(
			$this->laravel->getCachedRoutesPath(), $this->buildRouteCacheFile($this->routes)
		);

		$this->info('Routes cached successfully!');
	}

	/**
	 * Built the route cache file.
	 *
	 * @param  \Illuminate\Routing\RouteCollection  $routes
	 * @return string
	 */
	protected function buildRouteCacheFile(RouteCollection $routes)
	{
		$stub = $this->files->get(__DIR__.'/stubs/routes.stub');

		return str_replace('{{routes}}', base64_encode(serialize($routes)), $stub);
	}

}
