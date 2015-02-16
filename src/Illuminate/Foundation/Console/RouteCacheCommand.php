<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;

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
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new route command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

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

		$routes = $this->getFreshApplicationRoutes();

		if (count($routes) == 0)
		{
			return $this->error("Your application doesn't have any routes.");
		}

		foreach ($routes as $route)
		{
			$route->prepareForSerialization();
		}

		$this->files->put(
			$this->laravel->getCachedRoutesPath(), $this->buildRouteCacheFile($routes)
		);

		$this->info('Routes cached successfully!');
	}

	/**
	 * Boot a fresh copy of the application and get the routes.
	 *
	 * @return \Illuminate\Routing\RouteCollection
	 */
	protected function getFreshApplicationRoutes()
	{
		$app = require $this->laravel['path.base'].'/bootstrap/app.php';

		$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

		return $app['router']->getRoutes();
	}

	/**
	 * Build the route cache file.
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
