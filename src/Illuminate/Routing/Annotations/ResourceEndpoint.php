<?php namespace Illuminate\Routing\Annotations;

class ResourceEndpoint implements EndpointInterface {

	/**
	 * The route defintion template.
	 *
	 * @var string
	 */
	protected $template = '$router->group([\'middleware\' => %s, \'prefix\' => %s, \'domain\' => %s, \'where\' => %s], function($router) { $router->resource(%s, %s, [\'only\' => %s, \'names\' => %s]); });';

	/**
	 * All of the resource controller methods.
	 *
	 * @var array
	 */
	protected $methods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

	/**
	 * The ReflectionClass instance for the controller class.
	 *
	 * @var \ReflectionClass
	 */
	public $reflection;

	/**
	 * The route paths for the definition.
	 *
	 * This corresponds to a path for each applicable resource method.
	 *
	 * @var array[ResourcePath]
	 */
	public $paths;

	/**
	 * The name of the resource.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The array of route names for the resource.
	 *
	 * @var array
	 */
	public $names = [];

	/**
	 * The only methods that should be included.
	 *
	 * @var array
	 */
	public $only = [];

	/**
	 * The methods that should not be included.
	 *
	 * @var array
	 */
	public $except = [];

	/**
	 * The class level "inherited" middleware that apply to the resource.
	 *
	 * @var array
	 */
	public $classMiddleware = [];

	/**
	 * The middleware that was applied at the method level.
	 *
	 * This array is keyed by resource method name (index, create, etc).
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
		{
			$this->{$key} = $value;
		}

		$this->buildPaths();
	}

	/**
	 * Build all of the paths for the resource endpoint.
	 *
	 * @return void
	 */
	protected function buildPaths()
	{
		foreach ($this->getIncludedMethods() as $method)
		{
			$this->paths[] = new ResourcePath($method);
		}
	}

	/**
	 * Get the methods to be included in the resource.
	 *
	 * @return array
	 */
	protected function getIncludedMethods()
	{
		if ($this->only)
		{
			return $this->only;
		}
		elseif ($this->except)
		{
			return array_diff($this->methods, $this->except);
		}

		return $this->methods;
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
				$this->template, var_export($this->getMiddleware($path), true),
				var_export($path->path, true), var_export($path->domain, true),
				var_export($path->where, true), var_export($this->name, true),
				var_export($this->reflection->name, true), var_export([$path->method], true),
				var_export($this->getNames($path), true)
			);
		}

		return implode(PHP_EOL, $routes);
	}

	/**
	 * Get all of the middleware for the given path.
	 *
	 * This will also merge in any of the middleware applied at the route level.
	 *
	 * @param  ResourcePath  $path
	 * @return array
	 */
	protected function getMiddleware(ResourcePath $path)
	{
		return array_merge($this->classMiddleware, array_get($this->middleware, $path->method, []));
	}

	/**
	 * Get the names for the given path.
	 *
	 * @param  ResourcePath  $path
	 * @return array
	 */
	protected function getNames(ResourcePath $path)
	{
		return isset($this->names[$path->method]) ? [$path->method => $this->names[$path->method]] : [];
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
		return $path->method;
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
	 * @return array[AbstractPath]
	 */
	public function getPaths()
	{
		return $this->paths;
	}

}
