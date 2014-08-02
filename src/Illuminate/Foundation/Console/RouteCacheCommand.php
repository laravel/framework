<?php namespace Illuminate\Foundation\Console;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

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
	protected $description = 'Create a route cache file for faster route registration.';

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
	 * @param  \Illuminate\Routing\Router  $router
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Router $router, Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
		$this->router = $router;
		$this->routes = $router->getRoutes();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if (count($this->routes) == 0)
		{
			return $this->error("Your application doesn't have any routes.");
		}

		if ($this->laravel->routesAreCached())
		{
			return $this->error("Route cache file already exists!");
		}

		foreach ($this->routes as $route)
		{
			$route->prepareForSerialization();
		}

		$this->files->put(
			$this->laravel['path'].'/routing/cache.php', $this->buildRouteCacheFile()
		);

		$this->info('Routes cached successfully!');
	}

	/**
	 * Built the route cache file.
	 *
	 * @return string
	 */
	protected function buildRouteCacheFile()
	{
		$stub = $this->files->get(__DIR__.'/stubs/routes.stub');

		return str_replace('{{routes}}', base64_encode(serialize($this->routes)), $stub);
	}

}