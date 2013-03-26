<?php namespace Illuminate\Foundation\Console;

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
	 * An array o fall registered routes.
	 *
	 * @var array
	 */
	protected $routes;

	/**
	 * Only desired route info
	 *
	 * @var array
	 */
	protected $routesInfo;

	/**
	 * Create a new route command instance.
	 *
	 * @param  Symfony\Component\Routing\RouteCollection  $routes
	 * @return void
	 */
	public function __construct(RouteCollection $routes)
	{
		parent::__construct();

		$this->routes = $routes;
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

		foreach($this->routes as $name => $route)
		{
			$results[] = $this->getRouteInformation($name, $route);
		}

		return $results;
	}

	/**
	 * Get the route information for a given route.
	 *
	 * @param  string  $name
	 * @param  Symfony\Component\Routing\Route  $route
	 * @return array
	 */
	protected function getRouteInformation($name, Route $route)
	{
		$uri = head($route->getMethods()).' '.$route->getPath();

		$action = $route->getAction() ?: 'Closure';

		return array('uri' => $uri, 'name' => $this->getRouteName($name), 'action' => $action);
	}

	/**
	 * Display the route information on the console.
	 *
	 * @param  array  $routes
	 * @return void
	 */
	protected function displayRoutes(array $routes)
	{
		$widths = $this->getCellWidths($routes);

		$this->displayHeadings($widths);

		foreach($routes as $route)
		{
			$this->displayRoute($route, $widths);
		}
	}

	/**
	 * Display the route table headings.
	 *
	 * @param  array  $widths
	 * @return void
	 */
	protected function displayHeadings(array $widths)
	{
		extract($widths);

		$this->comment(str_pad('URI', $uris).str_pad('Name', $names).str_pad('Action', $actions));
	}

	/**
	 * Display a route in the route table.
	 *
	 * @param  array  $route
	 * @param  array  $widths
	 * @return void
	 */
	protected function displayRoute(array $route, array $widths)
	{
		extract($widths);

		$this->info(
			str_pad($route['uri'], $uris).str_pad($route['name'], $names).str_pad($route['action'], $actions)
		);
	}

	/**
	 * Get the correct cell spacing per column.
	 *
	 * @param  array  $routes
	 * @return array
	 */
	protected function getCellWidths($routes, $padding = 10)
	{
		$widths = array();

		foreach($this->getColumns($routes) as $key => $column)
		{
			$widths[$key] = max(array_map('strlen', $column)) + $padding;
		}

		return $widths;
	}

	/**
	 * Get the columns for the route table.
	 *
	 * @param  array  $routes
	 * @return array
	 */
	protected function getColumns(array $routes)
	{
		$columns = array();

		foreach (array('uris', 'actions', 'names') as $key)
		{
			$columns[$key] = array_pluck($routes, str_singular($key));
		}

		return $columns;
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

}