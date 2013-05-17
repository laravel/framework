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
	 * The table helper set.
	 *
	 * @var \Symfony\Component\Console\Helper\TableHelper
	 */
	protected $table;

	/**
	 * Create a new route command instance.
	 *
	 * @param  \Symfony\Component\Routing\RouteCollection  $routes
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
		$this->table = $this->getHelperSet()->get('table');

		if (count($this->routes) == 0)
		{
			return $this->error("Your application doesn't have any routes.");
		}

		$this->displayRoutes($this->getRoutes($this->option('with-filters')), $this->option('with-filters'));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('with-filters', 'f', InputOption::VALUE_NONE, 'Also display before and after route filters.', null),
		);
	}

	/**
	 * Compile the routes into a displayable format.
	 *
	 * @return array
	 */
	protected function getRoutes($withFilters)
	{
		$results = array();

		foreach($this->routes as $name => $route)
		{
			$results[] = $this->getRouteInformation($name, $route, $withFilters);
		}

		return $results;
	}

	/**
	 * Get the route information for a given route.
	 *
	 * @param  string  $name
	 * @param  \Symfony\Component\Routing\Route  $route
	 * @return array
	 */
	protected function getRouteInformation($name, Route $route, $withFilters)
	{
		$uri = head($route->getMethods()).' '.$route->getPath();

		$action = $route->getAction() ?: 'Closure';

		return array_merge(array('uri' => $uri, 'name' => $this->getRouteName($name), 'action' => $action), ($withFilters ? array('before' => $this->getBeforeFilters($route), 'after' => $this->getAfterFilters($route)) : array()));
	}

	/**
	 * Display the route information on the console.
	 *
	 * @param  array  $routes
	 * @return void
	 */
	protected function displayRoutes(array $routes, $withFilters)
	{
		$this->table->setHeaders(array_merge(array('URI', 'Name', 'Action'), ($withFilters ? array('Filters Before', 'Filters After') : array())))->setRows($routes);

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
	 * @param  Route  $route
	 * @return string
	 */
	protected function getBeforeFilters($route)
	{
		return implode(', ',$route->getBeforeFilters());
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

}