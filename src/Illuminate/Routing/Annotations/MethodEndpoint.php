<?php namespace Illuminate\Routing\Annotations;

class MethodEndpoint implements EndpointInterface {

	/**
	 * The route defintion template.
	 *
	 * @var string
	 */
	protected $template = '$router->%s(\'%s\', [\'uses\' => \'%s\', \'domain\' => %s, \'as\' => %s, \'before\' => %s, \'after\' => %s, \'where\' => %s);';

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
	 * The before filters defined on endpoints without paths.
	 *
	 * @var array
	 */
	public $pathlessBefore = [];

	/**
	 * The after filters defined on endpoints without paths.
	 *
	 * @var array
	 */
	public $pathlessAfter = [];

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
				var_export($path->as, true), var_export($path->before, true),
				var_export($path->after, true), var_export($path->where, true)
			);
		}

		return implode(PHP_EOL, $routes);
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
