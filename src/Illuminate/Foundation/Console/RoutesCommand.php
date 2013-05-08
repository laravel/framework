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
	 * @param  \Symfony\Component\Routing\Route  $route
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
		$table = $this->getHelperSet()->get('table');

		$table->setHeaders(array('URI', 'Name', 'Action'))->setRows($routes);

		$table->render($this->getOutput());
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