<?php namespace Illuminate\Routing;

use Closure;

class RouteGroupStack extends Route {

	/**
	 * The attributes assigned to the route group stack.
	 *
	 * @var array
	 */
	protected $attributes;


	/**
	 * The Closure containing routing instructions.
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * The routes contained within this route group stack.
	 *
	 * @var RouteCollection
	 */
	protected $routes;

	/**
	 * Create a new RouteGroupStack instance.
	 *
	 * @param array $attributes
	 * @param callable $callback
	 */
	public function __construct(array $attributes, Closure $callback)
	{
		$this->attributes = $attributes;
		$this->callback = $callback;
		$this->routes = new RouteCollection;
	}

	/**
	 * Get the attributes of the stack.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Add a route to the stack.
	 *
	 * @param \Illuminate\Routing\Route $route
	 * @return void
	 */
	public function add(Route $route)
	{
		$this->routes->add($route);
	}

	/**
	 * Set a regular expression requirement to the routes contained within this stack.
	 *
	 * @param array|string $name
	 * @param null $expression
	 * @return void
	 */
	public function where($name, $expression = null)
	{
		foreach ($this->routes as $route)
		{
			$route->where($name, $expression);
		}
		return $this;
	}

}