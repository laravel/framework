<?php namespace Illuminate\Routing;

use ArrayAccess;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as BaseRoute;

class Route extends BaseRoute implements ArrayAccess {

	/**
	 * The router instance.
	 *
	 * @var  Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * The matching parameter array.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Execute the route and return the response.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return mixed
	 */	
	public function run(Request $request)
	{
		$response = $this->callBeforeFilters($request);

		// We will only call the router callable if no "before" middlewares returned
		// a response. If they do, we will consider that the response to requests
		// so that the request "lifecycle" will be easily halted for filtering.
		if ( ! isset($response))
		{
			$response = $this->callCallable();
		}

		$response = $this->router->prepare($response, $request);

		// Once we have the "prepared" response, we will iterate through every after
		// filter and call each of them with the request and the response so they
		// can perform any final work that needs to be done after a route call.
		foreach ($this->getAfterFilters() as $filter)
		{
			$this->callFilter($filter, $request, array($response));
		}

		return $response;
	}

	/**
	 * Call the callable Closure attached to the route.
	 *
	 * @return mixed
	 */
	protected function callCallable()
	{
		$variables = array_values($this->getVariablesWithoutDefaults());

		return call_user_func_array($this->parameters['_call'], $variables);
	}

	/**
	 * Call all of the before filters on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @return mixed
	 */
	protected function callBeforeFilters(Request $request)
	{
		$before = $this->getAllBeforeFilters($request);

		$response = null;

		// Once we have each middlewares, we will simply iterate through them and call
		// each one of them with the request. We will set the response variable to
		// whatever it may return so that it may override the request processes.
		foreach ($before as $filter)
		{
			$response = $this->callFilter($filter, $request);

			if ( ! is_null($response)) return $response;
		}
	}

	/**
	 * Get all of the before filters to run on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return array
	 */
	protected function getAllBeforeFilters(Request $request)
	{
		$before = $this->getBeforeFilters();

		return array_merge($before, $this->router->findPatternFilters($request));	
	}

	/**
	 * Call a given filter with the parameters.
	 *
	 * @param  string  $name
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function callFilter($name, Request $request, array $parameters = array())
	{
		if ( ! $this->router->filtersEnabled()) return;

		$merge = array($this->router->getCurrentRoute(), $request);

		$parameters = array_merge($merge, $parameters);

		// Next we will parse the filter name to extract out any parameters and adding
		// any parameters specified in a filter name to the end of the lists of our
		// parameters, since the ones at the beginning are typically very static.
		list($name, $parameters) = $this->parseFilter($name, $parameters);

		if ( ! is_null($callable = $this->router->getFilter($name)))
		{
			return call_user_func_array($callable, $parameters);
		}
	}

	/**
	 * Parse a filter name and add any parameters to the array.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return array
	 */
	protected function parseFilter($name, $parameters = array())
	{
		if (str_contains($name, ':'))
		{
			// If the filter name contains a colon, we will assume that the developer
			// is passing along some parameters with the name, and we will explode
			// out the name and paramters, merging the parameters onto the list.
			$segments = explode(':', $name);

			$name = $segments[0];

			$arguments = explode(',', $segments[1]);

			// We will merge the arguments specified in the filter name into the list
			// of existing parameters. We'll send them at the end since any values
			// at the front are usually static such as request, response, route.
			$parameters = array_merge($parameters, $arguments);
		}

		return array($name, $parameters);
	}

	/**
	 * Get a variable by name from the route.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string
	 */
	public function getVariable($name, $default = null)
	{
		return array_get($this->parameters, $name, $default);
	}

	/**
	 * Set the value of a variable.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @return void
	 */
	public function setVariable($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * Get the variables to the callback.
	 *
	 * @return array
	 */
	public function getVariables()
	{
		$variables = $this->compile()->getVariables();

		$parameters = array();

		foreach ($variables as $variable)
		{
			$parameters[$variable] = $this->parameters[$variable];
		}

		return $parameters;
	}

	/**
	 * Get the route variables without missing defaults.
	 *
	 * @return array
	 */
	public function getVariablesWithoutDefaults()
	{
		$variables = $this->getVariables();

		foreach ($variables as $key => $value)
		{
			if ($this->isMissingDefault($key, $value))
			{
				unset($variables[$key]);
			}
		}

		return $variables;
	}

	/**
	 * Determine if a route parameter is really a missing default.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function isMissingDefault($key, $value)
	{
		$defaults = $this->getDefaults();

		return array_key_exists($key, $defaults) and is_null($value);
	}

	/**
	 * Get the keys of the variables on the route.
	 *
	 * @return array
	 */
	public function getVariableKeys()
	{
		return array_keys($this->getVariables());
	}

	/**
	 * Force a given parameter to match a regular expression.
	 *
	 * @param  string  $name
	 * @param  string  $expression
	 * @return Illuminate\Routing\Route
	 */
	public function where($name, $expression)
	{
		$this->setRequirement($name, $expression);

		return $this;
	}

	/**
	 * Set the default value for a parameter.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return Illuminate\Routing\Route
	 */
	public function defaults($key, $value)
	{
		$this->setDefault($key, $value);

		return $this;
	}

	/**
	 * Set the before filters on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function before()
	{
		$current = $this->getBeforeFilters();

		$before = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_before', $before);

		return $this;
	}

	/**
	 * Set the after filters on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function after()
	{
		$current = $this->getAfterFilters();

		$after = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_after', $after);

		return $this;
	}

	/**
	 * Get the before filters on the route.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->getOption('_before') ?: array();
	}

	/**
	 * Set the before filters on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setBeforeFilters($value)
	{
		$this->setOption('_before', explode('|', $value));
	}

	/**
	 * Get the after filters on the route.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->getOption('_after') ?: array();
	}

	/**
	 * Set the after filters on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setAfterFilters($value)
	{
		$this->setOption('_after', explode('|', $value));
	}

	/**
	 * Set the matching parameter array on the route.
	 *
	 * @param  array  $parameters
	 * @return void
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * Set the Router instance on the route.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;
	}

	/**
	 * Check if a given variable exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return ! is_null($this->getVariable($key));
	}

	/**
	 * Get a variable by key.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function offsetGet($key)
	{
		return $this->getVariable($key);
	}

	/**
	 * Set the given variable value.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->setVariable($key, $value);
	}

	/**
	 * Set the given variable to null.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->setVariable($key, null);
	}

}
