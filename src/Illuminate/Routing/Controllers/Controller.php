<?php namespace Illuminate\Routing\Controllers;

use Closure;
use ReflectionClass;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller {

	/**
	 * The controller filter parser.
	 *
	 * @var \Illuminate\Routing\FilterParser
	 */
	protected $filterParser;

	/**
	 * The filters that have been specified.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * The inline Closure defined filters.
	 *
	 * @var array
	 */
	protected $callbackFilters = array();

	/**
	 * The layout used by the controller.
	 *
	 * @var \Illuminate\View\View
	 */
	protected $layout;

	/**
	 * Register a new "before" filter on the controller.
	 *
	 * @param  string  $filter
	 * @param  array   $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = array())
	{
		$options = $this->prepareFilter($filter, $options);

		$this->filters[] = new Before($options);
	}

	/**
	 * Register a new "after" filter on the controller.
	 *
	 * @param  string  $filter
	 * @param  array   $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = array())
	{
		$options = $this->prepareFilter($filter, $options);

		$this->filters[] = new After($options);
	}

	/**
	 * Prepare a filter and return the options.
	 *
	 * @param  string  $filter
	 * @param  array   $options
	 * @return array
	 */
	protected function prepareFilter($filter, $options)
	{
		// When the given filter is a Closure, we will store it off in an array of the
		// callback filters, keyed off the object hash of these Closures and we can
		// easily retrieve it if a filter is determined to apply to this request.
		if ($filter instanceof Closure)
		{
			$options['run'] = $hash = spl_object_hash($filter);

			$this->callbackFilters[$hash] = $filter;
		}
		else
		{
			$options['run'] = $filter;
		}

		return $options;
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  \Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function callAction(Container $container, Router $router, $method, $parameters)
	{
		$this->filterParser = $container['filter.parser'];

		// If no response was returned from the before filters, we'll call the regular
		// action on the controller and prepare the response. Then we will call the
		// after filters on the controller to wrap up any last minute processing.
		$response = $this->callBeforeFilters($router, $method);

		$this->setupLayout();

		if (is_null($response))
		{
			$response = $this->callMethod($method, $parameters);
		}

		// If no response is returned from the controller action and a layout is being
		// used we will assume we want to just return the layout view as any nested
		// views were probably bound on this view during this controller actions.
		if (is_null($response) and ! is_null($this->layout))
		{
			$response = $this->layout;
		}

		return $this->processResponse($router, $method, $response);
	}

	/**
	 * Call the given action with the given parameters.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function callMethod($method, $parameters)
	{
		return call_user_func_array(array($this, $method), $parameters);
	}

	/**
	 * Process a controller action response.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  mixed   $response
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function processResponse($router, $method, $response)
	{
		$request = $router->getRequest();

		// The after filters give the developers one last chance to do any last minute
		// processing on the response. The response has already been converted to a
		// full Response object and will also be handed off to the after filters.
		$response = $router->prepare($response, $request);

		$this->callAfterFilters($router, $method, $response);

		return $response;
	}

	/**
	 * Call the before filters on the controller.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @return mixed
	 */
	protected function callBeforeFilters($router, $method)
	{
		$response = null;

		$route = $router->getCurrentRoute();

		// When running the before filters we will simply spin through the list of the
		// filters and call each one on the current route objects, which will place
		// the proper parameters on the filter call, including the requests data.
		$request = $router->getRequest();

		$filters = $this->getBeforeFilters($request, $method);

		foreach ($filters as $filter)
		{
			$response = $this->callFilter($route, $filter, $request);

			if ( ! is_null($response)) return $response;
		}
	}

	/**
	 * Get the before filters for the controller.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function getBeforeFilters($request, $method)
	{
		$class = 'Illuminate\Routing\Controllers\Before';

		return $this->filterParser->parse($this, $request, $method, $class);
	}

	/**
	 * Call the after filters on the controller.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	protected function callAfterFilters($router, $method, $response)
	{
		$route = $router->getCurrentRoute();

		// When running the before filters we will simply spin through the list of the
		// filters and call each one on the current route objects, which will place
		// the proper parameters on the filter call, including the requests data.
		$request = $router->getRequest();

		$filters = $this->getAfterFilters($request, $method);

		foreach ($filters as $filter)
		{
			$this->callFilter($route, $filter, $request, array($response));
		}
	}

	/**
	 * Get the after filters for the controller.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function getAfterFilters($request, $method)
	{
		$class = 'Illuminate\Routing\Controllers\After';

		return $this->filterParser->parse($this, $request, $method, $class);
	}

	/**
	 * Call the given route filter.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  string  $filter
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  array  $parameters
	 * @return mixed
	 */
	protected function callFilter($route, $filter, $request, $parameters = array())
	{
		if (isset($this->callbackFilters[$filter]))
		{
			$callback = $this->callbackFilters[$filter];

			return call_user_func_array($callback, array_merge(array($route, $request), $parameters));
		}

		return $route->callFilter($filter, $request, $parameters);
	}

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout() {}

	/**
	 * Get the code registered filters.
	 *
	 * @return array
	 */
	public function getControllerFilters()
	{
		return $this->filters;
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function missingMethod($parameters)
	{
		throw new NotFoundHttpException("Controller method not found.");
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->missingMethod($parameters);
	}

}