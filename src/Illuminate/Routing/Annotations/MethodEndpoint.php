<?php namespace Illuminate\Routing\Annotations;

use Illuminate\Support\Collection;

class MethodEndpoint implements EndpointInterface {

	/**
	 * The route defintion template.
	 *
	 * @var string
	 */
	protected $template = '$router->%s(\'%s\', [\'uses\' => \'%s\', \'domain\' => %s, \'as\' => %s, \'middleware\' => %s, \'where\' => %s]);';

	/**
	 * The ReflectionClass instance for the controller class.
	 *
	 * @var \ReflectionClass
	 */
	public $reflection;

	/**
	 * The method that handles the route.
	 *
	 * @var string
	 */
	public $method;

	/**
	 * The route paths for the definition.
	 *
	 * @var array[Path]
	 */
	public $paths = [];

	/**
	 * The controller and method that handles the route.
	 *
	 * @var string
	 */
	public $uses;

	/**
	 * All of the class level "inherited" middleware defined for the pathless endpoint.
	 *
	 * @var array
	 */
	public $classMiddleware = [];

	/**
	 * All of the middleware defined for the pathless endpoint.
	 *
	 * @var array
	 */
	public $middleware = [];

	/**
	 * Create a new route definition instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{
		foreach ($attributes as $key => $value)
			$this->{$key} = $value;
	}

	/**
	 * Transform the endpoint into a route definition.
	 *
	 * @return string
	 */
	public function toRouteDefinition()
	{
		$routes = [];

		foreach ($this->paths as $path)
		{
			$routes[] = sprintf(
				$this->template, $path->verb, $path->path, $this->uses, var_export($path->domain, true),
				var_export($path->as, true), var_export($this->getMiddleware($path), true), var_export($path->where, true)
			);
		}

		return implode(PHP_EOL, $routes);
	}

	/**
	 * Get the middleware for the path.
	 *
	 * @param  AbstractPath  $path
	 * @return array
	 */
	protected function getMiddleware(AbstractPath $path)
	{
		$classMiddleware = $this->getClassMiddlewareForPath($path)->all();

		return array_merge($classMiddleware, $path->middleware, $this->middleware);
	}

	/**
	 * Get the class middleware for the given path.
	 *
	 * @param  AbstractPath  $path
	 * @return array
	 */
	protected function getClassMiddlewareForPath(AbstractPath $path)
	{
		return Collection::make($this->classMiddleware)->filter(function($m)
		{
			return $this->middlewareAppliesToMethod($this->method, $m);
		})
		->map(function($m)
		{
			return $m['name'];
		});
	}

	/**
	 * Determine if the middleware applies to a given method.
	 *
	 * @param  string  $method
	 * @param  array  $middleware
	 * @return bool
	 */
	protected function middlewareAppliesToMethod($method, array $middleware)
	{
		if ( ! empty($middleware['only']) && ! in_array($method, $middleware['only']))
		{
			return false;
		}
		elseif ( ! empty($middleware['except']) && in_array($method, $middleware['except']))
		{
			return false;
		}

		return true;
	}

	/**
	 * Determine if the endpoint has any paths.
	 *
	 * @var bool
	 */
	public function hasPaths()
	{
		return count($this->paths) > 0;
	}

	/**
	 * Get the controller method for the given endpoint path.
	 *
	 * @param  AbstractPath  $path
	 * @return string
	 */
	public function getMethodForPath(AbstractPath $path)
	{
		return $this->method;
	}

	/**
	 * Add the given path definition to the endpoint.
	 *
	 * @param  AbstractPath  $path
	 * @return void
	 */
	public function addPath(AbstractPath $path)
	{
		$this->paths[] = $path;
	}

	/**
	 * Get all of the path definitions for an endpoint.
	 *
	 * @return array
	 */
	public function getPaths()
	{
		return $this->paths;
	}

}
