<?php namespace Illuminate\Foundation\Console;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RoutesCommand extends Command {

    	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'routes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all registered routes';

	/**
	 * The router instance.
	 *
	 * @var \Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * An array of all the registered routes.
	 *
	 * @var \Symfony\Component\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The table helper set.
	 *
	 * @var \Symfony\Component\Console\Helper\TableHelper
	 */
	protected $table;

	/**
	 * Create a new route command instance.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function __construct(Router $router)
	{
		parent::__construct();

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
		$this->table = $this->getHelperSet()->get('table');

		if (count($this->routes) == 0)
		{
			return $this->error("Your application doesn't have any routes.");
		}

		$this->displayRoutes($this->getRoutes());
	}

	/**
	 * Compile the routes into a displayable format.
	 *
	 * @return array
	 */
	protected function getRoutes()
	{
		$results = array();

		foreach($this->routes as $name => $route)
		{
			$results[] = $this->getRouteInformation($name, $route);
		}

		return array_filter($results);
	}

	/**
	 * Get the route information for a given route.
	 *
	 * @param  string  $name
	 * @param  \Symfony\Component\Routing\Route  $route
	 * @return array
	 */
	protected function getRouteInformation($name, Route $route)
	{
		$uri = head($route->getMethods()).' '.$route->getPath();

		$action = $route->getAction() ?: 'Closure';

		return $this->filterRoute(array(
			'host'   => $route->getHost(),
			'uri'    => $uri,
			'name'   => $this->getRouteName($name),
			'action' => $action,
			'before' => $this->getBeforeFilters($route),
			'after'  => $this->getAfterFilters($route)
		));
	}

	/**
	 * Display the route information on the console.
	 *
	 * @param  array  $routes
	 * @return void
	 */
	protected function displayRoutes(array $routes)
	{
		$headers = array('Domain', 'URI', 'Name', 'Action', 'Before Filters', 'After Filters');

		$this->table->setHeaders($headers)->setRows($routes);

		$this->table->render($this->getOutput());
	}

	/**
	 * Get the route name for the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getRouteName($name)
	{
		return str_contains($name, ' ') ? '' : $name;
	}

	/**
	 * Get before filters
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string
	 */
	protected function getBeforeFilters($route)
	{
		$before = $route->getBeforeFilters();

		$before = array_unique(array_merge($before, $this->getPatternFilters($route)));

		return implode(', ', $before);
	}

	/**
	 * Get all of the pattern filters matching the route.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return array
	 */
	protected function getPatternFilters($route)
	{
		$patterns = array();

		foreach ($route->getMethods() as $method)
		{
			$inner = $this->router->findPatternFilters($method, $route->getPath());

			$patterns = array_merge($patterns, $inner);
		}

		return $patterns;
	}

	/**
	 * Get after filters
	 *
	 * @param  Route  $route
	 * @return string
	 */
	protected function getAfterFilters($route)
	{
		return implode(', ',$route->getAfterFilters());
	}

	/**
	 * Filter the route by URI and / or name.
	 *
	 * @param  array  $route
	 * @return array|null
	 */
	protected function filterRoute(array $route)
	{
		if (($this->option('name') and ! str_contains($route['name'], $this->option('name'))) or
			 $this->option('path') and ! str_contains($route['uri'], $this->option('path')))
		{
			return null;
		}
		else
		{
			return $route;
		}
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name.'),

			array('path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path.'),
		);
	}

}