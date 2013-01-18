<?php namespace Illuminate\Routing;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Illuminate\Routing\Controllers\Inspector;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router {

	/**
	 * The route collection instance.
	 *
	 * @var Symfony\Component\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The route filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * The pattern to filter bindings.
	 *
	 * @var array
	 */
	protected $patternFilters = array();

	/**
	 * The global filters for the router.
	 *
	 * @var array
	 */
	protected $globalFilters = array();

	/**
	 * The stack of grouped attributes.
	 *
	 * @var array
	 */
	protected $groupStack = array();

	/**
	 * The inversion of control container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * The controller inspector instance.
	 *
	 * @var Illuminate\Routing\Controllers\Inspector
	 */
	protected $inspector;

	/**
	 * The current request being dispatched.
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $currentRequest;

	/**
	 * The current route being executed.
	 *
	 * @var Illuminate\Routing\Route
	 */
	protected $currentRoute;

	/**
	 * Indicates if filters should be run.
	 *
	 * @var bool
	 */
	protected $runFilters = true;

	/**
	 * Create a new router instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container;

		$this->routes = new RouteCollection;
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function get($pattern, $action)
	{
		return $this->createRoute('get', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function post($pattern, $action)
	{
		return $this->createRoute('post', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function put($pattern, $action)
	{
		return $this->createRoute('put', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function patch($pattern, $action)
	{
		return $this->createRoute('patch', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function delete($pattern, $action)
	{
		return $this->createRoute('delete', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function match($method, $pattern, $action)
	{
		return $this->createRoute($method, $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function any($pattern, $action)
	{
		return $this->createRoute('get|post|put|patch|delete', $pattern, $action);
	}

	/**
	 * Register an array of controllers with wildcard routing.
	 *
	 * @param  array  $controllers
	 * @return void
	 */
	public function controllers(array $controllers)
	{
		foreach ($controllers as $name => $uri)
		{
			$this->controller($name, $uri);
		}
	}

	/**
	 * Route a controller to a URI with wildcard routing.
	 *
	 * @param  string  $controller
	 * @param  string  $uri
	 * @return Illuminate\Routing\Route
	 */
	public function controller($controller, $uri)
	{
		$routable = $this->getInspector()->getRoutable($controller, $uri);

		// When a controller is routed using this method, we use Reflection to parse
		// out all of the routable methods for the controller, then register each
		// route explicitly for the developers, so reverse routing is possible.
		foreach ($routable as $method => $routes)
		{
			foreach ($routes as $route)
			{
				$this->{$route['verb']}($route['uri'], $controller.'@'.$method);
			}
		}
	}

	/**
	 * Route a resource to a controller.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @param  array   $options
	 * @return void
	 */
	public function resource($resource, $controller, array $options = array())
	{
		$defaults = array('index', 'create', 'store', 'show', 'edit', 'update', 'destroy');

		foreach ($this->getResourceMethods($defaults, $options) as $method)
		{
			$this->{'addResource'.ucfirst($method)}($resource, $controller);
		}
	}

	/**
	 * Get the applicable resource methods.
	 *
	 * @param  array  $defaults
	 * @param  array  $options
	 * @return array
	 */
	protected function getResourceMethods($defaults, $options)
	{
		if (isset($options['only']))
		{
			return $options['only'];
		}
		elseif (isset($options['except']))
		{
			return array_diff($defaults, $options['except']);
		}

		return $defaults;
	}

	/**
	 * Add the index method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceIndex($resource, $controller)
	{
		return $this->get($resource, $controller.'@index');
	}

	/**
	 * Add the create method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceCreate($resource, $controller)
	{
		return $this->get($resource.'/create', $controller.'@create');
	}

	/**
	 * Add the store method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceStore($resource, $controller)
	{
		return $this->post($resource, $controller.'@store');
	}

	/**
	 * Add the show method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceShow($resource, $controller)
	{
		return $this->get($resource.'/{id}', $controller.'@show');
	}

	/**
	 * Add the edit method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceEdit($resource, $controller)
	{
		return $this->get($resource.'/{id}/edit', $controller.'@edit');
	}

	/**
	 * Add the update method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceUpdate($resource, $controller)
	{
		$this->put($resource.'/{id}', $controller.'@update');

		return $this->patch($resource.'/{id}', $controller.'@update');
	}

	/**
	 * Add the destroy method for a resourceful route.
	 *
	 * @param  string  $resource
	 * @param  string  $controller
	 * @return void
	 */
	protected function addResourceDestroy($resource, $controller)
	{
		return $this->delete($resource.'/{id}', $controller.'@destroy');
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  array    $attributes
	 * @param  Closure  $callback
	 * @return void
	 */
	public function group(array $attributes, Closure $callback)
	{
		$this->groupStack[] = $attributes;

		call_user_func($callback);

		array_pop($this->groupStack);
	}

	/**
	 * Create a new route instance.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	protected function createRoute($method, $pattern, $action)
	{
		// We will force the action parameters to be an array just for convenience.
		// This will let us examine it for other attributes like middlewares or
		// a specific HTTP schemes the route only responds to, such as HTTPS.
		if ( ! is_array($action))
		{
			$action = $this->parseAction($action);
		}

		$name = $this->getName($method, $pattern, $action);

		// We will create the routes, setting the Closure callbacks on the instance
		// so we can easily access it later. If there are other parameters on a
		// routes we'll also set those requirements as well such as defaults.
		$callback = $this->getCallback($action);

		list($pattern, $optional) = $this->getOptional($pattern);

		$route = new Route($pattern, array('_call' => $callback));

		$route->setRequirement('_method', $method);

		// Once we have created the route, we will add them to our route collection
		// which contains all the other routes and is used to match on incoming
		// URL and their appropriate route destination and on URL generation.
		$this->setAttributes($route, $action, $optional);

		$this->routes->add($name, $route);

		return $route;
	}

	/**
	 * Parse the given route action into array form.
	 *
	 * @param  mixed  $action
	 * @return array
	 */
	protected function parseAction($action)
	{
		// If the action is just a Closure we'll stick it in an array and just send
		// it back out. However if it's a string we'll just assume it's meant to
		// route into a controller action and change it to a controller array.
		if ($action instanceof Closure)
		{
			return array($action);
		}
		elseif (is_string($action))
		{
			return array('uses' => $action);
		}

		throw new \InvalidArgumentException("Unroutable action.");
	}

	/**
	 * Set the attributes and requirements on the route.
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @param  array  $action
	 * @param  array  $optional
	 * @return void
	 */
	protected function setAttributes(Route $route, $action, $optional)
	{
		$groupCount = count($this->groupStack);

		// If there are attributes being grouped across routes we will merge those
		// attributes into the action array so that they will get shared across
		// the routes. The route can override the attribute by specifying it.
		if ($groupCount > 0)
		{
			$index = $groupCount - 1;

			$action = array_merge($this->groupStack[$index], $action);
		}

		// First we will set the requirement for the HTTP schemes. Some routes may
		// only respond to requests using the HTTPS scheme, while others might
		// respond to all, regardless of the scheme, so we'll set that here.
		if (in_array('https', $action))
		{
			$route->setRequirement('_scheme', 'https');
		}

		if (in_array('http', $action))
		{
			$route->setRequirement('_scheme', 'http');
		}

		// Once the scheme requirements have been made, we will set the before and
		// after middleware options, which will be used to run any middlewares
		// by the consuming library, making halting the request cycles easy.
		if (isset($action['before']))
		{
			$route->setBeforeFilters($action['before']);
		}

		if (isset($action['after']))
		{
			$route->setAfterFilters($action['after']);
		}

		// If there is a "uses" key on the route it means it is using a controller
		// instead of a Closures route. So, we'll need to set that as an option
		// on the route so we can easily do reverse routing ot the route URI.
		if (isset($action['uses']))
		{
			$route->setOption('_uses', $action['uses']);
		}

		if (isset($action['domain']))
		{
			$route->setHostname($action['domain']);
		}

		// Finally we will go through and set all of the default variables to null
		// so the developer doesn't have to manually specify one each time they
		// are declared on a route. This is simply for developer convenience.
		foreach ($optional as $key)
		{
			$route->setDefault($key, null);
		}
	}

	/**
	 * Modify the pattern and extract optional parameters.
	 *
	 * @param  string  $pattern
	 * @return array
	 */
	protected function getOptional($pattern)
	{
		$optional = array();

		preg_match_all('#\{(\w+)\?\}#', $pattern, $matches);

		// For each matching value, we will extract the name of the optional values
		// and add it to our array, then we will replace the place-holder to be
		// a valid place-holder minus this optional indicating question mark.
		foreach ($matches[0] as $key => $value)
		{
			$optional[] = $name = $matches[1][$key];

			$pattern = str_replace($value, '{'.$name.'}', $pattern);
		}

		return array($pattern, $optional);
	}

	/**
	 * Get the name of the route.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  array   $action
	 * @return string
	 */
	protected function getName($method, $pattern, array $action)
	{
		if (isset($action['as'])) return $action['as'];

		return $method.' '.$pattern;
	}

	/**
	 * Get the callback from the given action array.
	 *
	 * @param  array    $action
	 * @return Closure
	 */
	protected function getCallback(array $action)
	{
		foreach ($action as $key => $attribute)
		{
			// If the action has a "uses" key, the route is pointing to a controller
			// action instead of using a Closure. So, we'll create a Closure that
			// resolves the controller instances and calls the needed function.
			if ($key === 'uses')
			{
				return $this->createControllerCallback($attribute);
			}
			elseif ($attribute instanceof Closure)
			{
				return $attribute;
			}
		}
	}

	/**
	 * Create the controller callback for a route.
	 *
	 * @param  string   $attribute
	 * @return Closure
	 */
	protected function createControllerCallback($attribute)
	{
		$ioc = $this->container;

		$me = $this;

		// We'll return a Closure that is able to resolve the controller instance and
		// call the appropriate method on the controller, passing in the arguments
		// it receives. Controllers are created with the IoC container instance.
		return function() use ($me, $ioc, $attribute)
		{
			list($controller, $method) = explode('@', $attribute);

			$route = $me->getCurrentRoute();

			// We will extract the passed in parameters off of the route object so we will
			// pass them off to the controller method as arguments. We will not get the
			// defaults so that the controllers will be able to use its own defaults.
			$args = array_values($route->getVariablesWithoutDefaults());

			$instance = $ioc->make($controller);

			return $instance->callAction($ioc, $me, $method, $args);
		};
	}

	/**
	 * Get the response for a given request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function dispatch(Request $request)
	{
		$this->currentRequest = $request;

		// First we will call the "before" global middlware, which we'll give a chance
		// to override the normal requests process when a response is returned by a
		// middleware. Otherwise we'll call the route just like a normal request.
		$response =  $this->callGlobalFilter($request, 'before');

		if ( ! is_null($response))
		{
			return $this->prepare($response, $request);
		}

		$this->currentRoute = $route = $this->findRoute($request);

		// Once we have the route, we can just run it to get the responses, which will
		// always be instances of the Response class. Once we have the responses we
		// will execute the global "after" middlewares to finish off the request.
		$response = $route->run($request);

		$this->callAfterFilter($request, $response);

		return $response;
	}

	/**
	 * Match the given request to a route object.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Illuminate\Routing\Route
	 */
	protected function findRoute(Request $request)
	{
		// We will catch any exceptions thrown during routing and convert it to a
		// HTTP Kernel equivalent exception, since that is a more generic type
		// that's used by the Illuminate foundation framework for responses.
		try
		{
			$path = '/'.ltrim($request->getPathInfo(), '/');

			$parameters = $this->getUrlMatcher($request)->match($path);
		}

		// The Symfony routing component's exceptions implement this interface we
		// can type-hint it to make sure we're only providing special handling
		// for those exceptions, and not other random exceptions that occur.
		catch (ExceptionInterface $e)
		{
			$this->handleRoutingException($e);
		}

		$route = $this->routes->get($parameters['_route']);

		// If we found a route, we will grab the actual route objects out of this
		// route collection and set the matching parameters on the instance so
		// we will easily access them later if the route action is executed.
		$route->setParameters($parameters);

		$route->setRouter($this);

		return $route;
	}

	/**
	 * Register a "before" routing filter.
	 *
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function before($callback)
	{
		$this->globalFilters['before'][] = $this->buildGlobalFilter($callback);
	}

	/**
	 * Register an "after" routing filter.
	 *
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function after($callback)
	{
		$this->globalFilters['after'][] = $this->buildGlobalFilter($callback);
	}

	/**
	 * Register a "close" routing filter.
	 *
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function close($callback)
	{
		$this->globalFilters['close'][] = $this->buildGlobalFilter($callback);
	}

	/**
	 * Register a "finish" routing filters.
	 *
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function finish($callback)
	{
		$this->globalFilters['finish'][] = $this->buildGlobalFilter($callback);
	}

	/**
	 * Build a global filter definition for the router.
	 *
	 * @param  Closure|string  $callback
	 * @return Closure
	 */
	protected function buildGlobalFilter($callback)
	{
		if (is_string($callback))
		{
			$container = $this->container;

			// When the given "callback" is actually a string, we will assume that it is
			// a filter class that we need to resolve out of an IoC container to call
			// the filter method on the instance, passing in the arguments we take.
			return function() use ($callback, $container)
			{
				$callable = array($container->make($callback), 'filter');
				
				return call_user_func_array($callable, func_get_args());			
			};
		}
		else
		{
			return $callback;
		}
	}

	/**
	 * Register a new filter with the application.
	 *
	 * @param  string   $name
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function addFilter($name, $callback)
	{
		$this->filters[$name] = $callback;
	}

	/**
	 * Get a registered filter callback.
	 *
	 * @param  string   $name
	 * @return Closure
	 */
	public function getFilter($name)
	{
		if (array_key_exists($name, $this->filters))
		{
			$filter = $this->filters[$name];

			// If the filter is a string, it means we are using a class based Filter which
			// allows for the easier testing of the filter's methods rather than trying
			// to test a Closure. So, we will resolve the class out of the container.
			if (is_string($filter))
			{
				return array($this->container->make($filter), 'filter');
			}

			return $filter;
		}
	}

	/**
	 * Tie a registered filter to a URI pattern.
	 *
	 * @param  string  $pattern
	 * @param  string|array  $names
	 * @return void
	 */
	public function matchFilter($pattern, $names)
	{
		foreach ((array) $names as $name)
		{
			$this->patternFilters[$pattern][] = $name;
		}
	}

	/**
	 * Find the patterned filters matching a request.
	 *
	 * @param  Illuminate\Foundation\Request  $request
	 * @return array
	 */
	public function findPatternFilters(Request $request)
	{
		$filters = array();

		foreach ($this->patternFilters as $pattern => $values)
		{
			// To find the pattern middlewares for a request, we just need to check the
			// registered patterns against the path info for the current request to
			// the application, and if it matches we'll merge in the middlewares.
			if (str_is('/'.$pattern, $request->getPathInfo()))
			{
				$filters = array_merge($filters, $values);
			}
		}

		return $filters;
	}

	/**
	 * Call the "after" global filters.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	protected function callAfterFilter(Request $request, SymfonyResponse $response)
	{
		$this->callGlobalFilter($request, 'after', array($response));

		$this->callGlobalFilter($request, 'close', array($response));
	}

	/**
	 * Call the "finish" global filter.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	public function callFinishFilter(Request $request, SymfonyResponse $response)
	{
		return $this->callGlobalFilter($request, 'finish', array($response));
	}

	/**
	 * Call a given global filter with the parameters.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function callGlobalFilter(Request $request, $name, array $parameters = array())
	{
		if ( ! $this->filtersEnabled()) return;

		array_unshift($parameters, $request);

		if (isset($this->globalFilters[$name]))
		{
			// There may be multiple handlers registered for a global middleware so we
			// will need to spin through each one and execute each of them and will
			// return back first non-null responses we come across from a filter.
			foreach ($this->globalFilters[$name] as $filter)
			{
				$response = call_user_func_array($filter, $parameters);

				if ( ! is_null($response)) return $response;
			}
		}
	}

	/**
	 * Prepare the given value as a Response object.
	 *
	 * @param  mixed  $value
	 * @param  Illuminate\Foundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function prepare($value, Request $request)
	{
		if ( ! $value instanceof SymfonyResponse) $value = new Response($value);

		return $value->prepare($request);
	}

	/**
	 * Convert routing exception to HttpKernel version.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	protected function handleRoutingException(\Exception $e)
	{
		if ($e instanceof ResourceNotFoundException)
		{
			throw new NotFoundHttpException($e->getMessage());
		}

		// The method not allowed exception is essentially a HTTP 405 error, so we
		// will grab the allowed methods when converting into the HTTP Kernel's
		// version of the exact error. This gives us a good RESTful API site.
		elseif ($e instanceof MethodNotAllowedException)
		{
			$allowed = $e->getAllowedMethods();

			throw new MethodNotAllowedHttpException($allowed, $e->getMessage());
		}
	}

	/**
	 * Determine if the current route has a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public function currentRouteNamed($name)
	{
		$route = $this->routes->get($name);
		
		return ! is_null($route) and $route === $this->currentRoute;	
	}

	/**
	 * Determine if the current route uses a given controller action.
	 *
	 * @param  string  $action
	 * @return bool
	 */
	public function currentRouteUses($action)
	{
		return $this->currentRoute->getOption('_uses') === $action;
	}

	/**
	 * Determine if route filters are enabled.
	 *
	 * @return bool
	 */
	public function filtersEnabled()
	{
		return $this->runFilters;
	}

	/**
	 * Enable the running of filters.
	 *
	 * @return void
	 */
	public function enableFilters()
	{
		$this->runFilters = true;
	}

	/**
	 * Disable the runnning of all filters.
	 *
	 * @return void
	 */
	public function disableFilters()
	{
		$this->runFilters = false;
	}

	/**
	 * Retrieve the entire route collection.
	 * 
	 * @return Symfony\Component\Routing\RouteCollection
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Get the current request being dispatched.
	 *
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->currentRequest;
	}

	/**
	 * Get the current route being executed.
	 *
	 * @return Illuminate\Routing\Route
	 */
	public function getCurrentRoute()
	{
		return $this->currentRoute;
	}

	/**
	 * Set the current route on the router.
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @return void
	 */
	public function setCurrentRoute(Route $route)
	{
		$this->currentRoute = $route;
	}

	/**
	 * Create a new URL matcher instance.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\Routing\Matcher\UrlMatcher
	 */
	protected function getUrlMatcher(Request $request)
	{
		$context = new RequestContext;

		$context->fromRequest($request);

		return new UrlMatcher($this->routes, $context);
	}

	/**
	 * Get the controller inspector instance.
	 *
	 * @return Illuminate\Routing\Controllers\Inspector
	 */
	public function getInspector()
	{
		return $this->inspector ?: new Controllers\Inspector;
	}

	/**
	 * Set the controller inspector instance.
	 *
	 * @param  Illuminate\Routing\Controllers\Inspector  $inspector
	 * @return void
	 */
	public function setInspector(Inspector $inspector)
	{
		$this->inspector = $inspector;
	}

	/**
	 * Get the container used by the router.
	 *
	 * @return Illuminate\Container\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the container instance on the router.
	 *
	 * @param  Illuminate\Container\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}