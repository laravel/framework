<?php namespace Illuminate\Foundation\Console;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:list';

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
	 * @var \Illuminate\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The table headers for the command.
	 *
	 * @var array
	 */
	protected $headers = array(
		'Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware'
	);

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

		foreach ($this->routes as $route)
		{
			$results[] = $this->getRouteInformation($route);
		}

		return array_filter($results);
	}

	/**
	 * Get the route information for a given route.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return array
	 */
	protected function getRouteInformation(Route $route)
	{
		return $this->filterRoute(array(
			'host'   => $route->domain(),
			'method' => implode('|', $route->methods()),
			'uri'    => $route->uri(),
			'name'   => $route->getName(),
			'action' => $route->getActionName(),
			'middleware' => $this->getMiddleware($route)
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
		$this->table($this->headers, $routes);
	}

	/**
	 * Get before filters
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string
	 */
	protected function getMiddleware($route)
	{
		$middleware = array_values($route->middleware());

		$middleware = array_unique(array_merge($middleware, $this->getPatternFilters($route)));

		return implode(', ', $middleware);
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

		foreach ($route->methods() as $method)
		{
			// For each method supported by the route we will need to gather up the patterned
			// filters for that method. We will then merge these in with the other filters
			// we have already gathered up then return them back out to these consumers.
			$inner = $this->getMethodPatterns($route->uri(), $method);

			$patterns = array_merge($patterns, array_keys($inner));
		}

		return $patterns;
	}

	/**
	 * Get the pattern filters for a given URI and method.
	 *
	 * @param  string  $uri
	 * @param  string  $method
	 * @return array
	 */
	protected function getMethodPatterns($uri, $method)
	{
		return $this->router->findPatternFilters(Request::create($uri, $method));
	}

	/**
	 * Filter the route by URI and / or name.
	 *
	 * @param  array  $route
	 * @return array|null
	 */
	protected function filterRoute(array $route)
	{
		if (($this->option('name') && ! str_contains($route['name'], $this->option('name'))) ||
			 $this->option('path') && ! str_contains($route['uri'], $this->option('path')))
		{
			return;
		}

		return $route;
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
