<?php namespace Illuminate\Routing;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as BaseRoute;

class Route extends BaseRoute {

	/**
	 * The router instance.
	 *
	 * @var  \Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * The matching parameter array.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * The parsed parameter array.
	 *
	 * @var array
	 */
	protected $parsedParameters;

	/**
	 * Execute the route and return the response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return mixed
	 */	
	public function run(Request $request)
	{
		$this->parsedParameters = null;

		// We will only call the router callable if no "before" middlewares returned
		// a response. If they do, we will consider that the response to requests
		// so that the request "lifecycle" will be easily halted for filtering.
		$response = $this->callBeforeFilters($request);

		if ( ! isset($response))
		{
			$response = $this->callCallable();
		}

		// If the response is from a filter we want to note that so that we can skip
		// the "after" filters which should only run when the route method is run
		// for the incoming request. Otherwise only app level filters will run.
		else
		{
			$fromFilter = true;
		}

		$response = $this->router->prepare($response, $request);

		// Once we have the "prepared" response, we will iterate through every after
		// filter and call each of them with the request and the response so they
		// can perform any final work that needs to be done after a route call.
		if ( ! isset($fromFilter))
		{
			$this->callAfterFilters($request, $response);
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
		$variables = array_values($this->getParametersWithoutDefaults());

		return call_user_func_array($this->getOption('_call'), $variables);
	}

	/**
	 * Call all of the before filters on the route.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request   $request
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
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return array
	 */
	protected function getAllBeforeFilters(Request $request)
	{
		$before = $this->getBeforeFilters();

		$patterns = $this->router->findPatternFilters($request->getMethod(), $request->getPathInfo());

		return array_merge($before, $patterns);	
	}

	/**
	 * Call all of the "after" filters for a route.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	protected function callAfterFilters(Request $request, $response)
	{
		foreach ($this->getAfterFilters() as $filter)
		{
			$this->callFilter($filter, $request, array($response));
		}
	}

	/**
	 * Call a given filter with the parameters.
	 *
	 * @param  string  $name
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  array   $params
	 * @return mixed
	 */
	public function callFilter($name, Request $request, array $params = array())
	{
		if ( ! $this->router->filtersEnabled()) return;

		$merge = array($this->router->getCurrentRoute(), $request);

		$params = array_merge($merge, $params);

		// Next we will parse the filter name to extract out any parameters and adding
		// any parameters specified in a filter name to the end of the lists of our
		// parameters, since the ones at the beginning are typically very static.
		list($name, $params) = $this->parseFilter($name, $params);

		if ( ! is_null($callable = $this->router->getFilter($name)))
		{
			return call_user_func_array($callable, $params);
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

			// We will merge the arguments specified in the filter name into the list
			// of existing parameters. We'll send them at the end since any values
			// at the front are usually static such as request, response, route.
			$arguments = explode(',', $segments[1]);

			$parameters = array_merge($parameters, $arguments);
		}

		return array($name, $parameters);
	}

	/**
	 * Get a parameter by name from the route.
	 *
	 * @param  string  $name
	 * @param  mixed   $default
	 * @return string
	 */
	public function getParameter($name, $default = null)
	{
		return array_get($this->getParameters(), $name, $default);
	}

	/**
	 * Get the parameters to the callback.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		// If we have already parsed the parameters, we will just return the listing
		// that we already parsed as some of these may have been resolved through
		// a binder that uses a database repository and shouldn't be run again.
		if (isset($this->parsedParameters))
		{
			return $this->parsedParameters;
		}

		$variables = $this->compile()->getVariables();

		// To get the parameter array, we need to spin the names of the variables on
		// the compiled route and match them to the parameters that we got when a
		// route is matched by the router, as routes instances don't have them.
		$parameters = array();

		foreach ($variables as $variable)
		{
			$parameters[$variable] = $this->resolveParameter($variable);
		}

		return $this->parsedParameters = $parameters;
	}

	/**
	 * Resolve a parameter value for the route.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function resolveParameter($key)
	{
		$value = $this->parameters[$key];

		// If the parameter has a binder, we will call the binder to resolve the real
		// value for the parameters. The binders could make a database call to get
		// a User object for example or may transform the input in some fashion.
		if ($this->router->hasBinder($key))
		{
			return $this->router->performBinding($key, $value, $this);
		}

		return $value;
	}

	/**
	 * Get the route parameters without missing defaults.
	 *
	 * @return array
	 */
	public function getParametersWithoutDefaults()
	{
		$parameters = $this->getParameters();

		foreach ($parameters as $key => $value)
		{
			// When calling functions using call_user_func_array, we don't want to write
			// over any existing default parameters, so we will remove every optional
			// parameter from the list that did not get a specified value on route.
			if ($this->isMissingDefault($key, $value))
			{
				unset($parameters[$key]);
			}
		}

		return $parameters;
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
		return $this->isOptional($key) and is_null($value);
	}

	/**
	 * Determine if a given key is optional.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function isOptional($key)
	{
		return array_key_exists($key, $this->getDefaults());
	}

	/**
	 * Get the keys of the variables on the route.
	 *
	 * @return array
	 */
	public function getParameterKeys()
	{
		return $this->compile()->getVariables();
	}

	/**
	 * Force a given parameter to match a regular expression.
	 *
	 * @param  string  $name
	 * @param  string  $expression
	 * @return \Illuminate\Routing\Route
	 */
	public function where($name, $expression = null)
	{
		if (is_array($name)) return $this->setArrayOfWheres($name);

		$this->setRequirement($name, $expression);

		return $this;
	}

	/**
	 * Force a given parameters to match the expressions.
	 *
	 * @param  array $wheres
	 * @return \Illuminate\Routing\Route
	 */
	protected function setArrayOfWheres(array $wheres)
	{
		foreach ($wheres as $name => $expression)
		{
			$this->where($name, $expression);
		}

		return $this;
	}

	/**
	 * Set the default value for a parameter.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return \Illuminate\Routing\Route
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
	 * @return \Illuminate\Routing\Route
	 */
	public function before()
	{
		$this->setBeforeFilters(func_get_args());

		return $this;
	}

	/**
	 * Set the after filters on the route.
	 *
	 * @param  dynamic
	 * @return \Illuminate\Routing\Route
	 */
	public function after()
	{
		$this->setAfterFilters(func_get_args());

		return $this;
	}

	/**
	 * Get the name of the action (if any) used by the route.
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->getOption('_uses');
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
		$filters = $this->parseFilterValue($value);

		$this->setOption('_before', array_merge($this->getBeforeFilters(), $filters));
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
		$filters = $this->parseFilterValue($value);

		$this->setOption('_after', array_merge($this->getAfterFilters(), $filters));
	}

	/**
	 * Parse the given filters for setting.
	 *
	 * @param  array|string  $value
	 * @return array
	 */
	protected function parseFilterValue($value)
	{
		$results = array();

		foreach ((array) $value as $filters)
		{
			$results = array_merge($results, explode('|', $filters));
		}

		return $results;
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
	 * @param  \Illuminate\Routing\Router  $router
	 * @return \Illuminate\Routing\Route
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;

		return $this;
	}

}
